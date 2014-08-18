<?php

namespace App\Components;

use App,
	App\Database\Entities\GalleryPhotoEntity;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class GalleryPhotosList extends Component
{
	use TRecordHandlers;


	private $galleryId;


	public function __construct($galleryId, GalleryPhotoEntity $galleryPhotoEntity)
	{
		$this->entity = $galleryPhotoEntity;
		$this->galleryId = $galleryId;
	}


	public function render($code = null)
	{
		$template = $this->createTemplate();

		$template->rows = $this->entity->findAll()->where("gallery_id", $this->galleryId);
		$template->inactiveCnt = $this->entity->getInactiveCnt($template->rows);

		$template->render();
	}
}
