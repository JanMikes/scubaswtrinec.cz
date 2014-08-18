<?php

namespace App\BackendModule;

use App,
	App\Factories\IArticlePhotosListFactory,
	App\Factories\ManageArticlePhotoFormFactory;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class ArticlePhotoPresenter extends SecuredPresenter
{
	/** @persistent int */
	public $id;

	/** @persistent int */
	public $articleId;

	/** @var App\Database\Entities\ArticleEntity @autowire */
	protected $articleEntity;

	/** @var App\Database\Entities\ArticlePhotoEntity @autowire */
	protected $articlePhotoEntity;


	public function startup()
	{
		parent::startup();

		if ($this->view != "edit") {
			$this->id = null;
		}

		$this->template->article = $this->articleEntity->find($this->articleId);
		if (!$this->articleId || !$this->template->article) {
			$this->redirect("Article:");
		}
	}


	public function renderDefault($articleId)
	{
	}


	public function actionEdit($id)
	{
		$this->template->articlePhoto = $this->articlePhotoEntity->find($id);
		if (!$this->template->articlePhoto) {
			$this->redirect("default");
		}

		$defaults = $this->template->articlePhoto->toArray();

		$this["manageArticlePhotoForm"]->setDefaults($defaults);
	}


	protected function createComponentArticlePhotosList(IArticlePhotosListFactory $factory)
	{
		return $factory->create($this->articleId);
	}


	protected function createComponentManageArticlePhotoForm(ManageArticlePhotoFormFactory $factory)
	{
		return $factory->create($this->articleId, $this->id);
	}
}
