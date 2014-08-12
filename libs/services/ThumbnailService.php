<?php

namespace App\Services;

use Nette,
	Nette\Database\Table\ActiveRow,
	App\Database\DTO,
	Nette\Http\Request as HttpRequest,
	App\Database\Entities\LstThumbnailTpEntity,
	App\Database\Entities\ThumbnailEntity;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class ThumbnailService extends Nette\Object
{
	/** @var App\Database\Entities\ThumbnailEntity */
	private $thumbnailEntity;

	/** @var App\Database\Entities\LstThumbnailTpEntity */
	private $thumbnailTpEntity;

	/** @var Nette\Http\Request */
	private $httpRequest;


	public function __construct(LstThumbnailTpEntity $thumbnailTpEntity, ThumbnailEntity $thumbnailEntity, HttpRequest $httpRequest)
	{
		$this->thumbnailTpEntity = $thumbnailTpEntity;
		$this->thumbnailEntity = $thumbnailEntity;
		$this->httpRequest = $httpRequest;
	}


	public function getThumbnailPath($photoRow, $type = null)
	{
		if (!$photoRow instanceof ActiveRow) {
			$thumbType = $this->thumbnailTpEntity->findType($type);
			$thumbWidth = (isset($thumbType->width)? $thumbType->width : 100);
			$thumbHeight = (isset($thumbType->height)? $thumbType->height : 100);
			return "http://placehold.it/".$thumbWidth."x".$thumbHeight;
		}

		if (!$type) {
			return $this->getBaseUri() . "/$photoRow->path/$photoRow->filename";
		}

		try {
			$thumb = $this->thumbnailEntity->findThumb($type, $photoRow->id);

			if (!$thumb || !is_file("$thumb->path/$thumb->filename")) {
				$thumb = $this->generateThumbnail($photoRow, $type);
			}

			return $this->getBaseUri() . "/$thumb->path/$thumb->filename";
		} catch (\Exception $e) {
			$thumbType = $this->thumbnailTpEntity->findType($type);
			$thumbWidth = (isset($thumbType->width)? $thumbType->width : 100);
			$thumbHeight = (isset($thumbType->height)? $thumbType->height : 100);
			return "http://placehold.it/".$thumbWidth."x".$thumbHeight;
		}
	}


	public function generateThumbnail(ActiveRow $photoRow, $type)
	{
		$thumbType = $this->thumbnailTpEntity->findType($type);
		if (!$thumbType) {
			throw new Nette\InvalidArgumentException("Invalid thumbnail type $type!");
		}

		$image = Nette\Image::fromFile($photoRow->path . "/" . $photoRow->filename);

		$thumbPath = "$photoRow->path/$type";
		if (!is_dir($thumbPath)) {
			mkdir($thumbPath, 0755, true);
		}

		$image->resize($thumbType->width, $thumbType->height, ( ($thumbType->width && $thumbType->height ? $image::EXACT : $image::FILL ) | $image::SHRINK_ONLY));
		$image->save("$thumbPath/$photoRow->filename", 95);

		$this->thumbnailEntity->deactivateOldThumbnails($thumbType->id, $photoRow->id);

		$dto = new DTO\Thumbnail;
		$dto->typeId = $thumbType->id;
		$dto->photoId = $photoRow->id;
		$dto->filename = $photoRow->filename;
		$dto->path = $thumbPath;
		$dto->width = $image->width;
		$dto->height = $image->height;
		$dto->activeFlag = 1;
		$dto->insProcess = __METHOD__;

		return $this->thumbnailEntity->insert($dto);
	}


	//////////////////////
	// PRIVATE FUNCTION //
	//////////////////////

	private function getBaseUri()
	{
		return rtrim($this->httpRequest->getUrl()->getBaseUrl(), '/');
	}

}
