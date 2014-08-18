<?php

namespace App\Components;

use App,
	App\Database\Entities\ArticleEntity;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class ArticlesList extends Component
{
	use TRecordHandlers;


	public function __construct(ArticleEntity $articleEntity)
	{
		$this->entity = $articleEntity;
	}


	public function render($code = null)
	{
		$template = $this->createTemplate();

		$template->rows = $this->entity->findAll();
		$template->inactiveCnt = $this->entity->getInactiveCnt($template->rows);

		$template->render();
	}
}
