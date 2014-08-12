<?php

namespace App\Database\Entities;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class ThumbnailEntity extends Entity
{
	public function deactivateOldThumbnails($thumbTypeId, $photoId)
	{
		return $this->findBy(array(
			"type_id" => $thumbTypeId,
			"photo_id" => $photoId,
		))->update(array(
			"del_flag" => 1,
			"upd_process" => __METHOD__,
		));
	}


	public function findThumb($type, $photoId)
	{
		return $this->findOneBy(array(
			"type.code" => $type,
			"photo_id" => $photoId,
		));
	}


	public function findThumbnails($photoId)
	{
		return $this->findBy(array(
			"photo_id" => $photoId,
		));
	}
}
