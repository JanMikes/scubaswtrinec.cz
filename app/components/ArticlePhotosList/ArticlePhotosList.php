<?php

namespace App\Components;

use App,
	App\Database\Entities\ArticlePhotoEntity;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class ArticlePhotosList extends Component
{
	use TRecordHandlers;


	private $articleId;


	public function __construct($articleId, ArticlePhotoEntity $articlePhotoEntity)
	{
		$this->entity = $articlePhotoEntity;
		$this->articleId = $articleId;
	}


	public function render($code = null)
	{
		$template = $this->createTemplate();

		$template->rows = $this->entity->findAll()->where("article_id", $this->articleId);
		$template->inactiveCnt = $this->entity->getInactiveCnt($template->rows);

		$template->render();
	}
}
