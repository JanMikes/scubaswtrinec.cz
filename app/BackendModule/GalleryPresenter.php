<?php

namespace App\BackendModule;

use App,
	App\Factories\IGalleriesListFactory,
	App\Factories\ManageGalleryFormFactory;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class GalleryPresenter extends SecuredPresenter
{
	/** @persistent int */
	public $id;

	/** @var App\Database\Entities\GalleryEntity @autowire */
	protected $galleryEntity;


	public function startup()
	{
		parent::startup();

		if ($this->view != "edit") {
			$this->id = null;
		}
	}


	public function actionEdit($id)
	{
		$this->template->gallery = $this->galleryEntity->find($id);
		if (!$this->template->gallery) {
			$this->redirect("default");
		}

		$defaults = $this->template->gallery->toArray();

		$this["manageGalleryForm"]->setDefaults($defaults);
	}


	protected function createComponentGalleriesList(IGalleriesListFactory $factory)
	{
		return $factory->create();
	}


	protected function createComponentManageGalleryForm(ManageGalleryFormFactory $factory)
	{
		return $factory->create($this->id);
	}
}
