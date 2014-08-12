<?php

namespace App\Services;

use Nette,
	Nette\Http\FileUpload,
	App\Database\DTO,
	App\Database\Entities\PhotoEntity;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class ImageUploadService extends Nette\Object
{
	/** @var App\Services\ThumbnailService */
	private $thumbnailService;

	/** @var App\Database\Entities\PhotoEntity */
	private $photoEntity;


	public function __construct(PhotoEntity $photoEntity, ThumbnailService $thumbnailService)
	{
		$this->photoEntity = $photoEntity;
		$this->thumbnailService = $thumbnailService;
	}

	public function upload(FileUpload $file, $path, $filename, array $thumbs)
	{
		if (!is_dir($path)) {
			mkdir($path, 0755, true);
		}

		$image = $file->toImage();

		$name = $filename . "." . strtolower(pathinfo($file->name, PATHINFO_EXTENSION));

		$image->save("$path/$name", 100);

		$dto = DTO\Photo::fromArray(array(
			"filename" => $name,
			"path" => $path,
			"width" => $image->width,
			"height" => $image->height,
			"ins_process" => __METHOD__,
		));

		$photoRow = $this->photoEntity->insert($dto);

		foreach ($thumbs as $thumbType) {
			$this->thumbnailService->generateThumbnail($photoRow, $thumbType);
		}

		return $photoRow;
	}
}
