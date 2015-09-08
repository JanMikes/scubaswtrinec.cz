<?php

namespace App\Factories;

use Nette,
	App,
	App\Services\ThumbnailService,
	App\Services\PhotoService,
	App\Services\ImageService,
	App\Services\ImageUploadService,
	App\Database\Entities\LinkEntity;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class ManageLinkFormFactory extends Nette\Object
{
	use TFormSaveHandlers;


	const UPLOAD_PATH = "img/link";


	/** @var App\Factories\FormFactory */
	private $formFactory;

	/** @var App\Services\ImageService */
	private $imageService;

	/** @var App\Services\ThumbnailService */
	private $thumbnailService;

	/** @var App\Services\PhotoService */
	private $photoService;

	/** @var App\Services\ImageUploadService */
	private $imageUploadService;

	/** @var Nette\Database\Table\ActiveRow */
	private $row;

	/** @var App\Database\Entities\LinkEntity */
	private $linkEntity;


	public function __construct(
		FormFactory $formFactory,
		ImageService $imageService,
		ImageUploadService $imageUploadService,
		ThumbnailService $thumbnailService,
		PhotoService $photoService,
		LinkEntity $linkEntity
	) {
		$this->formFactory = $formFactory;
		$this->imageService = $imageService;
		$this->photoService = $photoService;
		$this->thumbnailService = $thumbnailService;
		$this->imageUploadService = $imageUploadService;
		$this->linkEntity = $linkEntity;
	}


	public function create($id)
	{
		if ($id) {
			$this->row = $this->linkEntity->find($id);
		}

		$form = $this->formFactory->create();


		$form->addText("name", "Název", 60, 60)
			->setRequired("Název je povinný!");

		$form->addText("url", "Odkaz", 200, 200)
			->setRequired("Odkaz je povinný!");

		$form->addText("description", "Popis", 200, 200);

		$form->addUpload("image", "Ikonka")
			->addCondition($form::FILLED)
				->addRule($form::IMAGE, "Obrázek musí být ve formátu JPG, GIF nebo PNG!");

		if ($this->row) {
			$origSrc = $this->thumbnailService->getThumbnailPath($this->row->photo);
			$thumbSrc = $this->thumbnailService->getThumbnailPath($this->row->photo, "admin");
			$imgInfo = Nette\Utils\Html::el()->setHtml("<a href='$origSrc'><img src='$thumbSrc'></a>");
			$form["image"]->setOption("description", $imgInfo);
		}

		$form->addSubmit("send", $this->row ? "Upravit" : "Přidat")
			->setAttribute("class", "btn-primary")
			->onClick[] = $this->save;

		if ($this->row) {
			$form->addSubmit("sendAndContinue", "Uložit a pokračovat v editaci")
				->setAttribute("class", "btn-primary")
				->onClick[] = $this->saveAndContinue;
		}

		return $form;
	}


	public function process(Nette\Application\UI\Form $form)
	{
		$values = $form->getValues(true);

		$image = $values["image"];
		unset ($values["image"]);

		if (!$this->row) {
			$values["ins_process"] = __METHOD__;
			$link = $this->linkEntity->insert($values);
		} else {
			$values["upd_process"] = __METHOD__;
			$link = $this->row;
			$link->update($values);
		}
		
		if ($image->isOk()) {
			if ($this->row) {
				$this->photoService->deletePhoto($this->row->photo_id);
			}
			
			$photoRow = $this->imageUploadService->upload($image, self::UPLOAD_PATH . "/" . $link->id, "main", array("admin"));

			$values["photo_id"] = $photoRow->id;
			$this->linkEntity->update($link->id, $values);
		}

	}

}
