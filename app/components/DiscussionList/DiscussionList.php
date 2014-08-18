<?php

namespace App\Components;

use App,
	App\Database\Entities\DiscussionEntity;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class DiscussionList extends Component
{
	use TRecordHandlers;


	public function __construct(DiscussionEntity $discussionEntity)
	{
		$this->entity = $discussionEntity;
	}


	public function render($code = null)
	{
		$template = $this->createTemplate();

		$template->rows = $this->entity->findAll();
		$template->inactiveCnt = $this->entity->getInactiveCnt($template->rows);

		$template->render();
	}
}
