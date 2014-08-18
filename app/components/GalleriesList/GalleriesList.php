<?php

namespace App\Components;

use App,
	App\Database\Entities\GalleryEntity;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class GalleriesList extends Component
{
	use TRecordHandlers;


	public function __construct(GalleryEntity $galleryEntity)
	{
		$this->entity = $galleryEntity;
	}


	public function render($code = null)
	{
		$template = $this->createTemplate();

		$template->rows = $this->entity->findAll();
		$template->inactiveCnt = $this->entity->getInactiveCnt($template->rows);

		$template->render();
	}
}
