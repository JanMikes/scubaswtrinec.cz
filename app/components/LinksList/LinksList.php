<?php

namespace App\Components;

use App,
	App\Database\Entities\LinkEntity;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class LinksList extends Component
{
	use TRecordHandlers;


	public function __construct(LinkEntity $linkEntity)
	{
		$this->entity = $linkEntity;
	}


	public function render($code = null)
	{
		$template = $this->createTemplate();

		$template->rows = $this->entity->findAll();
		$template->inactiveCnt = $this->entity->getInactiveCnt($template->rows);

		$template->render();
	}
}
