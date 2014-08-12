<?php

namespace App\BackendModule;

use App,
	App\Factories\IActualitiesListFactory,
	App\Factories\ManageActualityFormFactory;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class ActualityPhotoPresenter extends SecuredPresenter
{
	/** @persistent int */
	public $id;

	/** @var App\Database\Entities\ActualityEntity @autowire */
	protected $actualityEntity;


	public function startup()
	{
		parent::startup();

		if ($this->view != "edit") {
			$this->id = null;
		}
	}


	public function actionEdit($id)
	{
		$this->template->actuality = $this->actualityEntity->find($id);
		if (!$this->template->actuality) {
			$this->redirect("default");
		}

		$defaults = $this->template->actuality->toArray();
		$defaults["date"] = $defaults["date"]->format("d.m.Y");

		$this["manageActualityForm"]->setDefaults($defaults);
	}


	protected function createComponentActualitiesList(IActualitiesListFactory $factory)
	{
		return $factory->create();
	}


	protected function createComponentManageActualityForm(ManageActualityFormFactory $factory)
	{
		return $factory->create($this->id);
	}
}
