<?php

namespace App\Database\DTO;

use App,
	Nette;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class Thumbnail extends Nette\Object implements App\Database\IDataTransferObject
{
	use App\Database\TDefaultDataTransferObject,
		App\Database\TDefaultDataTransferObjectProperties;


	/** @column type_id */
	public $typeId;

	/** @column photo_id */
	public $photoId;

	/** @column filename */
	public $filename;

	/** @column path */
	public $path;

	/** @column width */
	public $width;

	/** @column height */
	public $height;
}
