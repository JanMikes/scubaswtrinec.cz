<?php

namespace App\BackendModule;

use App,
	App\Factories\IArticlesListFactory,
	App\Factories\ManageArticleFormFactory;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class ArticlePresenter extends SecuredPresenter
{
	/** @persistent int */
	public $id;

	/** @var App\Database\Entities\ArticleEntity @autowire */
	protected $articleEntity;


	public function startup()
	{
		parent::startup();

		if ($this->view != "edit") {
			$this->id = null;
		}
	}


	public function actionEdit($id)
	{
		$this->template->article = $this->articleEntity->find($id);
		if (!$this->template->article) {
			$this->redirect("default");
		}

		$defaults = $this->template->article->toArray();
		$defaults["date"] = $defaults["date"]->format("d.m.Y");

		$this["manageArticleForm"]->setDefaults($defaults);
	}


	protected function createComponentArticlesList(IArticlesListFactory $factory)
	{
		return $factory->create();
	}


	protected function createComponentManageArticleForm(ManageArticleFormFactory $factory)
	{
		return $factory->create($this->id);
	}
}
