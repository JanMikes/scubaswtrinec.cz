<?php

namespace App\BackendModule;

use App,
	App\Factories\ILinksListFactory,
	App\Factories\ManageLinkFormFactory;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class LinkPresenter extends SecuredPresenter
{
	/** @persistent int */
	public $id;

	/** @var App\Database\Entities\LinkEntity @autowire */
	protected $linkEntity;


	public function startup()
	{
		parent::startup();

		if ($this->view != "edit") {
			$this->id = null;
		}
	}


	public function actionEdit($id)
	{
		$this->template->link = $this->linkEntity->find($id);
		if (!$this->template->link) {
			$this->redirect("default");
		}

		$defaults = $this->template->link->toArray();

		$this["manageLinkForm"]->setDefaults($defaults);
	}


	protected function createComponentLinksList(ILinksListFactory $factory)
	{
		return $factory->create();
	}


	protected function createComponentManageLinkForm(ManageLinkFormFactory $factory)
	{
		return $factory->create($this->id);
	}
}
