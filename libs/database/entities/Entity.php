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
abstract class Entity extends Nette\Object
{
	const INCLUDE_DELETED = true;
	const DIRECTION_UP = "UP";
	const DIRECTION_DOWN = "DOWN";

	/** @var App\Database\ContextPool */
	private $contextPool;

	/** @var App\Factories\SelectionFactory */
	private $selectionFactory;

	/** @var string */
	private $connectionName = "default";


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
				
			if ($direction == self::DIRECTION_UP) {
				$rowSwap->where("order >= ?", $row->order)
					->order("order ASC");
			} elseif ($direction == self::DIRECTION_DOWN) {
				$rowSwap->where("order <= ?", $row->order)
					->order("order DESC");
			} else {
				return false;
			}
				
			$rowSwap = $rowSwap->fetch();

			if ($rowSwap) {
				$newOrder = $rowSwap->order;
				if ($newOrder == $row->order) {
					if ($direction == self::DIRECTION_UP) {
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
	 * @param  int 		$id
	 * @param  array  	$values
	 * @return int
	 */
	public function update($id, array $values)
	{
		return $this->find($id)->update($values);
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
	 * @param  array  $data
	 * @return Nette\Database\Table\ActiveRow
	 */
	public function insert(array $data)
	{
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

		try {
			$row->update(array(
				"order" => $row->id * 10,
			));
		} catch (\Exception $e) {
			// Dont do anything, everything is okay!
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
	public function findActive()
	{
		return $this->findBy(array(
			"active_flag" => 1,
		));
	}


	/**
	 * Returns only inactive and not deleted records
	 * @return Nette\Database\Table\Selection
	 */
	public function findInactive()
	{
		return $this->findBy(array(
			"active_flag" => 0,
		));
	}


	/**
	 * @param  Nette\Database\Table\Selection $selection
	 * @return int
	 */
	public function getInactiveCnt(Nette\Database\Table\Selection $selection)
	{
		$clone = clone $selection;
		return $clone->where("active_flag", 0)->count("id");
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
