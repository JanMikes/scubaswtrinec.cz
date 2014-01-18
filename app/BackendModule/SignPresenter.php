<?php

namespace App\BackendModule;

use App;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class SignPresenter extends BasePresenter
{
	/** @persistent string */
	public $backlink;

	public function actionDefault()
	{
		$this->redirect("in");
	}


	public function actionIn($backlink)
	{
		if ($backlink) {
			$this["signInForm"]["backlink"]->setValue($backlink);
		}
	}


	protected function startup()
	{
		parent::startup();

		if ($this->user->loggedIn) {
			$this->redirect(":Backend:Dashboard:");
		}
	}


	protected function createComponentSignInForm(App\Factories\SignInFormFactory $signInFormFactory)
	{
		return $signInFormFactory->create();
	}

}