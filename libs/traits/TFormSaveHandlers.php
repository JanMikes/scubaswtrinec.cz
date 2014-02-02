<?php

namespace App\Factories;

use Nette;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
trait TFormSaveHandlers 
{
	public function save(Nette\Forms\Controls\Button $button)
	{
		$form = $button->form;
		$presenter = $form->presenter;
		$this->process($form);

		$presenter->flashMessage("Záznam byl úspěšně uložen!", "success");
		$presenter->redirect("default");
	}


	public function saveAndContinue(Nette\Forms\Controls\Button $button)
	{
		$form = $button->form;
		$presenter = $form->presenter;
		$this->process($form);

		$presenter->flashMessage("Záznam byl úšpěšně uložen!", "success");
		$presenter->redirect("this");
	}
}