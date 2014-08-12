<?php

namespace App\Database\DTO;

use App,
	Nette;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class Photo extends Nette\Object implements App\Database\IDataTransferObject
{
	use App\Database\TDefaultDataTransferObject,
		App\Database\TDefaultDataTransferObjectProperties;


	/** @column filename */
	public $filename;

	/** @column path */
	public $path;

	/** @column width */
	public $width;

	/** @column height */
	public $height;
}
