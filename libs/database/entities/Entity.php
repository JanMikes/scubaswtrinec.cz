<?php

namespace App\Database\Entities;

use Nette,
	App,
	App\Database,
	Nette\Database as NDB;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
abstract class Entity extends Nette\Object implements Database\IEntity
{
	/** @var string */
	public $connectionName = "default";

	/** @var boolean */
	public $isOrderable = false;

	/** @var App\Database\ContextPool */
	private $contextPool;

	/** @var App\Factories\SelectionFactory */
	private $selectionFactory;


	public function __construct(Database\ContextPool $contextPool, App\Factories\SelectionFactory $selectionFactory)
	{
		$this->contextPool = $contextPool;
		$this->selectionFactory = $selectionFactory;
	}


	/**
	 * @return Nette\Database\Table\Selection
	 */
	final public function getTable()
	{
		$tableName = $this->getTableNameFromClassName();
		return $this->selectionFactory->create($tableName, $this->contextPool->getContext($this->connectionName));
	}


	/**
	 * @param  int $id
	 * @return int
	 */
	public function delete($id)
	{
		$row = $this->find($id);

		if (!$row) {
			throw new Nette\InvalidArgumentException("Row with id '$id' does not exist!");
		}

		return $row->update(array(
			"del_flag" => 1,
			"upd_process" => __METHOD__,
		));
	}


	/**
	 * @param  int $id
	 * @param  bool $includeDdeleted
	 * @return Nette\Database\Table\ActiveRow|FALSE
	 */
	public function find($id, $includeDeleted = false)
	{
		$selection = $this->getTable()->wherePrimary($id);
		if (!$includeDeleted) {
			$selection->where("del_flag", 0);
		}
		return $selection->fetch();
	}


	/**
	 * @param  int $id
	 * @return int
	 */
	public function activate($id)
	{
		$row = $this->find($id);

		if (!$row) {
			throw new Nette\InvalidArgumentException("Row with id '$id' does not exist!");
		}

		return $row->update(array(
			"active_flag" => 1,
			"upd_process" => __METHOD__,
		));
	}


	/**
	 * @param  int $id
	 * @return int
	 */
	public function deactivate($id)
	{
		$row = $this->find($id);

		if (!$row) {
			throw new Nette\InvalidArgumentException("Row with id '$id' does not exist!");
		}

		return $row->update(array(
			"active_flag" => 0,
			"upd_process" => __METHOD__,
		));
	}


	public function getLast()
	{
		return $this->findAll()->order("id DESC")->fetch();
	}


	/**
	 * @param  int $id
	 * @return bool
	 */
	public function changeRowOrder($id, $direction, $additionalConditions = array())
	{
		$row = $this->findBy($additionalConditions)
			->wherePrimary($id)
			->limit(1)
			->fetch();

		if ($row) {
			if (empty($row->order)) {
				$row->update(array(
					"order" => $row->id * 10,
				));
				return $this->changeRowOrder($id, $direction, $additionalConditions);
			}

			$rowSwap = $this->findBy(array(
					"id != ?" => $row->id,
				))
				->where($additionalConditions)
				->limit(1);

			if ($direction == self::MOVE_RECORD_UP) {
				$rowSwap->where("order >= ?", $row->order)
					->order("order ASC");
			} elseif ($direction == self::MOVE_RECORD_DOWN) {
				$rowSwap->where("order <= ?", $row->order)
					->order("order DESC");
			} else {
				return false;
			}

			$rowSwap = $rowSwap->fetch();

			if ($rowSwap) {
				$newOrder = $rowSwap->order;
				if ($newOrder == $row->order) {
					if ($direction == self::MOVE_RECORD_UP) {
						$newOrder++;
					} else {
						$newOrder--;
					}
				}

				$rowSwap->update(array(
					"order" => $row->order,
				));

				$row->update(array(
					"order" => $newOrder,
				));

				return true;
			}
		}
		return false;
	}


	/**
	 * @param  int 										$id
	 * @param  array|App\Database\IDataTransferObject  	$values
	 * @return int
	 */
	public function update($id, $data)
	{
		if ($data instanceof Database\IDataTransferObject) {
			$data = $data->getValues();
		}
		return $this->find($id)->update($data);
	}


	/**
	 * @param  bool $includeDdeleted
	 * @return Nette\Database\Table\Selection
	 */
	public function findAll($includeDeleted = false)
	{
		$tableName = $this->getTableNameFromClassName();
		$result = $this->getTable();

		if (!$includeDeleted) {
			$result->where($tableName . '.' . 'del_flag', 0);
		}
		return $result;
	}


	/**
	 * @param  array $by
	 * @param  bool  $includeDdeleted
	 * @return \Nette\Database\Table\Selection
	 */
	public function findBy(array $by, $includeDeleted = false)
	{
		return $this->findAll($includeDeleted)->where($by);
	}


	/**
	 * @param  array $by
	 * @param  bool  $includeDdeleted
	 * @return \Nette\Database\Table\Selection
	 */
	public function findOneBy(array $by, $includeDeleted = false)
	{
		return $this->findBy($by, $includeDeleted)->limit(1)->fetch();
	}


	/**
	 * @param  string 		$key
	 * @param  string|NULL 	$value
	 * @param  string|NULL 	$order
	 * @param  bool 		$includeDdeleted
	 * @return array
	 */
	public function fetchPairs($key = "id", $value = "name", $order = "name DESC", $includeDeleted = false)
	{
		$result = $this->findAll($includeDeleted);
		if (!empty($order)) {
			$result->order($order);
		}
		return $result->fetchPairs($key, $value);
	}


	/**
	 * Shortcut for $this->getTable()->insert()
	 * @param  array|App\Database\IDataTransferObject  	$values
	 * @return Nette\Database\Table\ActiveRow
	 */
	public function insert($data)
	{
		if ($data instanceof Database\IDataTransferObject) {
			$data = $data->getValues();
		}

		if (empty($data["ins_dt"])) {
			$data["ins_dt"] = new \DateTime;
		}

		foreach ($data as $key=>$val) {
			if (!is_object($val)) {
				$data[$key] = trim($val);
			}
			if (empty($val)) {
				unset ($data[$key]);
			}
		}

		$row = $this->getTable()->insert($data);

		if ($this->isOrderable) {
			$row->update(array(
				"order" => $row->id * 10,
			));
		}
		
		return $row;
	}


	/**
	 * Sets active_flag for all non-active records
	 * @return int
	 */
	public function activateAll()
	{
		return $this->findInactive()->update(array(
			"upd_process" => __METHOD__,
			"active_flag" => 1,
		));
	}


	/**
	 * Returns only active and not deleted records
	 * @return Nette\Database\Table\Selection
	 */
	public function findActive($id = null)
	{
		$tableName = $this->getTableNameFromClassName();

		$selection = $this->findBy(array(
			"$tableName.active_flag" => 1,
		));

		if ($id) {
			return $selection->wherePrimary($id)->fetch();
		}

		return $selection;
	}


	/**
	 * Returns only inactive and not deleted records
	 * @return Nette\Database\Table\Selection
	 */
	public function findInactive()
	{
		$tableName = $this->getTableNameFromClassName();

		return $this->findBy(array(
			"$tableName.active_flag" => 0,
		));
	}


	/**
	 * @param  Nette\Database\Table\Selection $selection
	 * @return int
	 */
	public function getInactiveCnt(Nette\Database\Table\Selection $selection)
	{
		$tableName = $this->getTableNameFromClassName();

		$clone = clone $selection;
		return $clone->where("$tableName.active_flag", 0)->count("id");
	}


	/////////////////////
	// PRIVATE METHODS //
	/////////////////////

	/**
	 * @return string
	 */
	private function getTableNameFromClassName()
	{
		$className = get_class($this);
		$tableNameCamelCase = (strrchr($className, "\\")? substr(strrchr($className, "\\"), 1) : $className);
		$splitTableName = preg_split('/(?=[A-Z])/', $tableNameCamelCase, -1, PREG_SPLIT_NO_EMPTY);
		$tableName = strtolower(implode('_', $splitTableName));
		$tableName = str_replace("_entity", "", $tableName);
		return $tableName;
	}

}
