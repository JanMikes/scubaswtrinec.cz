<?php

namespace App\BackendModule;

use App,
	App\Factories\IGalleryPhotosListFactory,
	App\Factories\ManageGalleryPhotoFormFactory;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class GalleryPhotoPresenter extends SecuredPresenter
{
	/** @persistent int */
	public $id;

	/** @persistent int */
	public $galleryId;

	/** @var App\Database\Entities\GalleryEntity @autowire */
	protected $galleryEntity;

	/** @var App\Database\Entities\GalleryPhotoEntity @autowire */
	protected $galleryPhotoEntity;


	public function startup()
	{
		parent::startup();

		if ($this->view != "edit") {
			$this->id = null;
		}

		$this->template->gallery = $this->galleryEntity->find($this->galleryId);
		if (!$this->galleryId || !$this->template->gallery) {
			$this->redirect("Gallery:");
		}
	}


	public function renderDefault($galleryId)
	{
	}


	public function actionEdit($id)
	{
		$this->template->galleryPhoto = $this->galleryPhotoEntity->find($id);
		if (!$this->template->galleryPhoto) {
			$this->redirect("default");
		}

		$defaults = $this->template->galleryPhoto->toArray();

		$this["manageGalleryPhotoForm"]->setDefaults($defaults);
	}


	protected function createComponentGalleryPhotosList(IGalleryPhotosListFactory $factory)
	{
		return $factory->create($this->galleryId);
	}


	protected function createComponentManageGalleryPhotoForm(ManageGalleryPhotoFormFactory $factory)
	{
		return $factory->create($this->galleryId, $this->id);
	}
}
