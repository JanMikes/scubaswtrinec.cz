<?php

namespace App\Factories;

use Nette,
	App,
	App\Services\ThumbnailService,
	App\Services\PhotoService,
	App\Services\ImageService,
	App\Services\ImageUploadService,
	App\Database\Entities\ArticlePhotoEntity;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class ManageArticlePhotoFormFactory extends Nette\Object
{
	use TFormSaveHandlers;


	const UPLOAD_PATH = "img/article";


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

	/** @var App\Database\Entities\ArticlePhotoEntity */
	private $articlePhotoEntity;


	public function __construct(
		FormFactory $formFactory,
		ImageService $imageService,
		ImageUploadService $imageUploadService,
		ThumbnailService $thumbnailService,
		PhotoService $photoService,
		ArticlePhotoEntity $articlePhotoEntity
	) {
		$this->formFactory = $formFactory;
		$this->imageService = $imageService;
		$this->photoService = $photoService;
		$this->thumbnailService = $thumbnailService;
		$this->imageUploadService = $imageUploadService;
		$this->articlePhotoEntity = $articlePhotoEntity;
	}


	public function create($articleId, $id)
	{
		if ($id) {
			$this->row = $this->articlePhotoEntity->find($id);
		}

		$form = $this->formFactory->create();

		$form->addHidden("article_id", $articleId);

		$form->addText("description", "Popis fotky", 60, 60);

		$form->addUpload("image", "Foto")
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
			$photo = $this->articlePhotoEntity->insert($values);
		} else {
			$values["upd_process"] = __METHOD__;
			$photo = $this->row;
			$photo->update($values);
		}
		
		if ($image->isOk()) {
			if ($this->row) {
				$this->photoService->deletePhoto($this->row->photo_id);
			}
			
			$photoRow = $this->imageUploadService->upload($image, self::UPLOAD_PATH . "/" . $values["article_id"], $photo->id, array("admin"));

			$values["photo_id"] = $photoRow->id;
			$this->articlePhotoEntity->update($photo->id, $values);
		}

	}

}
