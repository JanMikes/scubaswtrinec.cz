<?php

namespace App\Factories;

use Nette,
	App,
	App\Database\Entities\DiscussionEntity;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class ManageDiscussionFormFactory extends Nette\Object
{
	use TFormSaveHandlers;


	/** @var App\Factories\FormFactory */
	private $formFactory;

	/** @var App\Database\Entities\DiscussionEntity */
	private $discussionEntity;

	/** @var Nette\Database\Table\ActiveRow */
	private $row;


	public function __construct(
		FormFactory $formFactory,
		DiscussionEntity $discussionEntity
	) {
		$this->formFactory = $formFactory;
		$this->discussionEntity = $discussionEntity;
	}


	public function create($id)
	{
		if ($id) {
			$this->row = $this->discussionEntity->find($id);
		}

		$form = $this->formFactory->create();

		
		$form->addText("author", "Autor", 50, 50);

		$form->addText("email", "E-mail", 50, 50);

		$form->addText("subject", "Předmět", 50, 50);

		$form->addTextarea("text", "Text", 50, 5);
		

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

		if (!$this->row) {
			$values["ins_process"] = __METHOD__;
			$this->discussionEntity->insert($values);
		} else {
			$values["upd_process"] = __METHOD__;
			$this->row->update($values);
		}

	}

}
