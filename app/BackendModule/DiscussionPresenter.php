<?php

namespace App\BackendModule;

use App,
	App\Factories\IDiscussionListFactory,
	App\Factories\ManageDiscussionFormFactory;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class DiscussionPresenter extends SecuredPresenter
{
	/** @persistent int */
	public $id;

	/** @var App\Database\Entities\DiscussionEntity @autowire */
	protected $discussionEntity;


	public function startup()
	{
		parent::startup();

		if ($this->view != "edit") {
			$this->id = null;
		}
	}


	public function actionEdit($id)
	{
		$this->template->discussion = $this->discussionEntity->find($id);
		if (!$this->template->discussion) {
			$this->redirect("default");
		}

		$defaults = $this->template->discussion->toArray();

		$this["manageDiscussionForm"]->setDefaults($defaults);
	}


	protected function createComponentDiscussionList(IDiscussionListFactory $factory)
	{
		return $factory->create();
	}


	protected function createComponentManageDiscussionForm(ManageDiscussionFormFactory $factory)
	{
		return $factory->create($this->id);
	}
}
