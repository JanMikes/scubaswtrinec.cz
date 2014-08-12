<?php

namespace App\Factories;

use Nette,
	App,
	App\Database\Entities\StatuteEntity;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class ManageStatuteFormFactory extends Nette\Object
{
	use TFormSaveHandlers;


	/** @var App\Factories\FormFactory */
	private $formFactory;

	/** @var App\Database\Entities\StatuteEntity */
	private $statuteEntity;


	public function __construct(
		FormFactory $formFactory,
		StatuteEntity $statuteEntity
	) {
		$this->formFactory = $formFactory;
		$this->statuteEntity = $statuteEntity;
	}


	public function create()
	{
		$form = $this->formFactory->create();

		$form->addTextarea("text", "Text", 50, 4)
			->setAttribute("class", "ckeditor");

		
		$form->addSubmit("sendAndContinue", "Uložit")
			->setAttribute("class", "btn-primary")
			->onClick[] = $this->saveAndContinue;

		return $form;
	}


	public function process(Nette\Application\UI\Form $form)
	{
		$values = $form->getValues(true);

		$values["ins_process"] = __METHOD__;
		$this->statuteEntity->insert($values);
	}

}
