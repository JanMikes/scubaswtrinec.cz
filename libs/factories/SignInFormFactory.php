<?php

namespace App\Factories;

use Nette,
	App;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class SignInFormFactory extends Nette\Object
{
	/** @var App\Factories\FormFactory */
	private $formFactory;

	/** @var Nette\Security\User */
	private $user;


	public function __construct(FormFactory $formFactory, Nette\Security\User $user)
	{
		$this->formFactory = $formFactory;
		$this->user = $user;
	}


	public function create()
	{
		$form = $this->formFactory->create();
	
		$form->addText("username", "Uživatelské jméno", 30)
			->setRequired("Prosím vyplňte uživatelské jméno!");

		$form->addPassword("password", "Heslo", 30)
			->setRequired("Prosím vyplňte heslo!");
		
		$form->addCheckbox("persistent", "Trvalé přihlášení");
		$form->addHidden("backlink");
		$form->addSubmit("send", "Přihlásit se");

		$form->onSuccess[] = $this->process;

		return $form;
	}


	public function process(Nette\Forms\Form $form)
	{
		$values = $form->values;
		$presenter = $form->presenter;
		$user = $this->user;

		try{
			if ($values->persistent) {
				$user->setExpiration("+14 days", false, true);
			} else {
				$user->setExpiration("+1 hour", true, true);
			}

			$user->login($values->username, $values->password);
			$presenter->flashMessage("Přihlášení proběhlo úspěšně!");

			if ($values->backlink) {
				$presenter->restoreRequest($values->backlink);
			} else {
				$presenter->redirect(":Backend:Dashboard:");
			}
		}
		catch (Nette\Security\AuthenticationException $e) {
			$form->addError("Byly zadány neplatné přihlašovací údaje!");
		}
	}

}
