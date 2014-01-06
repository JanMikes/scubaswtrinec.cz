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

	/** @var Nette\Database\Context */
	private $dbContext;

	/** @var App\Factories\SelectionFactory */
	private $selectionFactory;


	public function __construct(NDB\Context $dbContext, App\Factories\SelectionFactory $selectionFactory)
	{
		$this->connection = $dbContext;
		$this->selectionFactory = $selectionFactory;
	}


	final public function getTable()
	{
		$tableName = $this->getTableNameFromClassName();
		return $this->selectionFactory->create($tableName, $this->dbContext);
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
	public function changeRowOrder($id, $direction)
	{
		$row = $this->find($id);

		if ($row) {
			if (empty($row->order)) {
				$row->update(array(
					"order" => $row->id * 10,
				));
				return $this->changeRowOrder($id, $direction);
			}

			$rowSwap = $this->getTable()
				->where("id != ?", $row->id)
				->where("del_flag", 0)
				->limit(1);
				
			if ($direction == self::DIRECTION_UP) {
				$rowSwap->where("order <= ?", $row->order)
					->order("order DESC");
			} elseif ($direction == self::DIRECTION_DOWN) {
				$rowSwap->where("order >= ?", $row->order)
					->order("order ASC");
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
				$row->update(array(
					"order" => $newOrder,
				));

				$rowSwap->update(array(
					"order" => $row->order,
				));
				return true;
			}
		}
		return false;
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
