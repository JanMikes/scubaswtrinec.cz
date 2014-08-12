<?php

namespace App\Factories;

use Nette,
	App,
	App\Database\Entities\ActualityEntity;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class ManageArticleFormFactory extends Nette\Object
{
	use TFormSaveHandlers;


	/** @var App\Factories\FormFactory */
	private $formFactory;

	/** @var App\Database\Entities\ActualityEntity */
	private $actualityEntity;

	/** @var Nette\Database\Table\ActiveRow */
	private $row;


	public function __construct(
		FormFactory $formFactory,
		ActualityEntity $actualityEntity
	) {
		$this->formFactory = $formFactory;
		$this->actualityEntity = $actualityEntity;
	}


	public function create($id)
	{
		if ($id) {
			$this->row = $this->actualityEntity->find($id);
		}

		$form = $this->formFactory->create();

		$form->addText("date", "Datum")
			->setRequired("Datum je povinný údaj!")
			->setAttribute("class", "dtpicker")
			->setAttribute("placeholder", "dd.mm.rrrr")
			->addRule($form::PATTERN, "Datum musí být ve formátu dd.mm.rrrr", "(0[1-9]|[12][0-9]|3[01])\.(0[1-9]|1[012])\.(19|20)\d\d");

		$form->addText("name", "Nadpis", 50, 50)
			->setRequired("Nadpis je povinný!");

		$form->addTextarea("text", "Text", 50, 4)
			->setRequired("Text je povinný!")
			->setAttribute("class", "ckeditor");


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

		$values["date"] = Nette\DateTime::from($values["date"]);

		if (!$this->row) {
			$values["ins_process"] = __METHOD__;
			$this->actualityEntity->insert($values);
		} else {
			$values["upd_process"] = __METHOD__;
			$this->row->update($values);
		}

	}

}
