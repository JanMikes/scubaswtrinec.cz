<?php

namespace App\Services;

use Nette;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class ImageService extends Nette\Object
{
	public function dimensionsOk(Nette\Image $image, $minWidth, $minHeight)
	{
		return ($image->width >= $minWidth && $image->height >= $minHeight);
	}
}
