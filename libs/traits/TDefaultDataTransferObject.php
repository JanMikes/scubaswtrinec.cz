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

				if (!$property->hasAnnotation(IDataTransferObject::DEFAULT_VALUE_ANNOTATION_NAME) || !empty($this->{$property->name})) {
					$values[$column] = $this->{$property->name};

					if (is_array($values[$column])) {
						$values[$column] = Nette\Utils\Json::encode($values[$column]);
					}
				}
			}
		}

		return $values;
	}


	public static function fromArray(array $values)
	{
		$object = new self;

		$class = Nette\Reflection\ClassType::from($object);
		
		foreach ($class->getProperties() as $property) {
			if ($property->hasAnnotation(IDataTransferObject::COLUMN_ANNOTATION_NAME)) {
				$column = $property->getAnnotation(IDataTransferObject::COLUMN_ANNOTATION_NAME);

				if (isset($values[$column])) {
					if (!$property->isPublic()) {
						$methodName = "set" . ucwords($property->getName());
						call_user_func(array($object, $methodName), $values[$column]);
					} else {
						$object->{$property->name} = $values[$column];
					}
				}

			}
		}

		return $object;
	}
}