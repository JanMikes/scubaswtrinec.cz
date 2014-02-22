<?php

namespace App\Database;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
interface IDataTransferObject
{
	const COLUMN_ANNOTATION_NAME = "column";

	
	public function getValues();
}