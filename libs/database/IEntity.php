<?php

namespace App\Database;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
interface IEntity
{
	const INCLUDE_DELETED = true;

	const MOVE_RECORD_UP = "UP";

	const MOVE_RECORD_DOWN = "DOWN";


	public function findAll($includeDeleted);

	public function find($id);

	public function insert($data);

	public function update($id, $data);
	
	public function delete($id);
}