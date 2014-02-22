<?php

namespace App\Database;

use Nette;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
trait TDefaultDataTransferObject 
{
	public function getValues()
	{
		$values = array();

		$class = Nette\Reflection\ClassType::from($this);
		foreach ($class->getProperties() as $property) {
			if ($property->hasAnnotation(IDataTransferObject::COLUMN_ANNOTATION_NAME)) {
				$column = $property->getAnnotation(IDataTransferObject::COLUMN_ANNOTATION_NAME);

				if (!empty($values[$column])) {
					throw new DuplicatedColumnException("Column '$column' has already its value set, please check your annotations!");
				}

				$values[$column] = $this->{$property->name};
			}
		}

		return $values;
	}
}