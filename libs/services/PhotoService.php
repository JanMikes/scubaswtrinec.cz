<?php

namespace App\Services;

use Nette,
	App\Database\Entities\PhotoEntity,
	App\Database\Entities\ThumbnailEntity;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class PhotoService extends Nette\Object
{
	/* @var App\Database\Entities\PhotoEntity */
	private $photoEntity;

	/* @var App\Database\Entities\ThumbnailEntity */
	private $thumbnailEntity;


	public function __construct(PhotoEntity $photoEntity, ThumbnailEntity $thumbnailEntity)
	{
		$this->photoEntity = $photoEntity;
		$this->thumbnailEntity = $thumbnailEntity;
	}


	public function deletePhoto($photoId)
	{
		$this->deleteThumbnails($photoId);

		$photo = $this->photoEntity->find($photoId);
		if ($photo) {
			$path = $photo->path;
			$name = $photo->filename;
			if (file_exists("$path/$name")) {
				@unlink("$path/$name");
			}
			$this->photoEntity->delete($photoId);
		}
	}


	public function deleteThumbnails($photoId)
	{
		foreach ($this->thumbnailEntity->findThumbnails($photoId) as $photoThumbnail) {
			$path = $photoThumbnail->path;
			$name = $photoThumbnail->filename;
			if (file_exists("$path/$name")) {
				@unlink("$path/$name");
			}
			$this->thumbnailEntity->delete($photoThumbnail->id);
		}
	}
}
