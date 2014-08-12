<?php

namespace App\Factories;

use Nette,
	Nette\Security\User;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class ChangePasswordFormFactory extends Nette\Object {

	/** @var App\Factories\FormFactory */
	private $formFactory;

	/** @var Nette\Security\User */
	private $user;


	public function __construct(FormFactory $formFactory, User $user)
	{
		$this->formFactory = $formFactory;
		$this->user = $user;
	}


	public function create()
	{
		$form = $this->formFactory->create();

		$form->addPassword("old_password", "Staré heslo")
			->setRequired("Heslo je povinné!")
			->addRule($form::MIN_LENGTH, "Heslo musí mít alespoň 4 znaky!", 4);

		$form->addPassword("new_password", "Nové heslo")
			->setRequired("Heslo je povinné!")
			->addRule($form::MIN_LENGTH, "Minimální délka je 4 znaky!", 4)
			->addRule($form::MAX_LENGTH, "Maximální délka je 20 znaků!", 20);

		$form->addPassword("new_password2", "Nové heslo kontrola")
			->setRequired("Heslo je povinné!")
			->addRule($form::MIN_LENGTH, "Minimální délka je 4 znaky!", 4)
			->addRule($form::MAX_LENGTH, "Maximální délka je 20 znaků!", 20)
			->addRule($form::EQUAL, "Hesla se neshodují!", $form["new_password"]);


		$form->addSubmit("send", "Změnit heslo")
			->setAttribute("class", "btn-primary");

		$form->onSuccess[] = $this->process;

		return $form;
	}


	public function process($form)
	{
		$values = $form->getValues(true);
		$presenter = $form->presenter;
		$user = $this->user;
		$authenticator = $user->authenticator;


		try{
			$authenticator->authenticate(array($user->identity->email, $values["old_password"]));

			$authenticator->setPassword($user->id, $values["new_password"]);

			$user->login($user->identity->email, $values["new_password"]);

			$presenter->flashMessage("Heslo bylo úspěšně změněno!");
			$presenter->redirect("this");

		} catch (Nette\Security\AuthenticationException $e) {
			$form["old_password"]->addError("Bylo zadáno chybné staré heslo!");
		}
	}

}
