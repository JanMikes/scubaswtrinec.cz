<?php

namespace App\Factories;

use Nette,
	App,
	App\Database\Entities\ContactEntity;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class ManageContactFormFactory extends Nette\Object
{
	use TFormSaveHandlers;


	/** @var App\Factories\FormFactory */
	private $formFactory;

	/** @var App\Database\Entities\ContactEntity */
	private $contactEntity;


	public function __construct(
		FormFactory $formFactory,
		ContactEntity $contactEntity
	) {
		$this->formFactory = $formFactory;
		$this->contactEntity = $contactEntity;
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
		$this->contactEntity->insert($values);
	}

}
