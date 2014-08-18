<?php

namespace App\BackendModule;

use App,
	App\Factories\IActualityPhotosListFactory,
	App\Factories\ManageActualityPhotoFormFactory;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class ActualityPhotoPresenter extends SecuredPresenter
{
	/** @persistent int */
	public $id;

	/** @persistent int */
	public $actualityId;

	/** @var App\Database\Entities\ActualityEntity @autowire */
	protected $actualityEntity;

	/** @var App\Database\Entities\ActualityPhotoEntity @autowire */
	protected $actualityPhotoEntity;


	public function startup()
	{
		parent::startup();

		if ($this->view != "edit") {
			$this->id = null;
		}

		$this->template->actuality = $this->actualityEntity->find($this->actualityId);
		if (!$this->actualityId || !$this->template->actuality) {
			$this->redirect("Actuality:");
		}
	}


	public function renderDefault($actualityId)
	{
	}


	public function actionEdit($id)
	{
		$this->template->actualityPhoto = $this->actualityPhotoEntity->find($id);
		if (!$this->template->actualityPhoto) {
			$this->redirect("default");
		}

		$defaults = $this->template->actualityPhoto->toArray();

		$this["manageActualityPhotoForm"]->setDefaults($defaults);
	}


	protected function createComponentActualityPhotosList(IActualityPhotosListFactory $factory)
	{
		return $factory->create($this->actualityId);
	}


	protected function createComponentManageActualityPhotoForm(ManageActualityPhotoFormFactory $factory)
	{
		return $factory->create($this->actualityId, $this->id);
	}
}
