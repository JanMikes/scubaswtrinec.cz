<?php

namespace App\Components;

use App,
	App\Database\Entities\ActualityPhotoEntity;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class ActualityPhotosList extends Component
{
	use TRecordHandlers;


	private $actualityId;


	public function __construct($actualityId, ActualityPhotoEntity $actualityPhotoEntity)
	{
		$this->entity = $actualityPhotoEntity;
		$this->actualityId = $actualityId;
	}


	public function render($code = null)
	{
		$template = $this->createTemplate();

		$template->rows = $this->entity->findAll()->where("actuality_id", $this->actualityId);
		$template->inactiveCnt = $this->entity->getInactiveCnt($template->rows);

		$template->render();
	}
}
