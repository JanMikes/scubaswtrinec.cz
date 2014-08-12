<?php

namespace App\Components;

use App,
	App\Database\Entities\ActualityEntity;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class ActualitiesList extends Component
{
	use TRecordHandlers;


	public function __construct(ActualityEntity $actualityEntity)
	{
		$this->entity = $actualityEntity;
	}


	public function render($code = null)
	{
		$template = $this->createTemplate();

		$template->rows = $this->entity->findAll();
		$template->inactiveCnt = $this->entity->getInactiveCnt($template->rows);

		$template->render();
	}
}
