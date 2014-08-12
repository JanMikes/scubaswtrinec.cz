<?php

namespace App\Database\Entities;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class LstThumbnailTpEntity extends Entity
{
	public function findType($code)
	{
		return $this->findOneBy(array(
			"code" => $code,
		));
	}
}
