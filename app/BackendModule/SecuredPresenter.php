<?php

namespace App\BackendModule;

use Nette;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
abstract class SecuredPresenter extends BasePresenter
{
	public function startup()
	{
		parent::startup();

		if (!$this->user->isLoggedIn()) {
			$this->user->logout(TRUE); // We want to make sure identity is cleared
			$backlink = $this->storeRequest();
			$this->redirect("Sign:", array("backlink" => $backlink));
		}
	}


	public function handleLogout()
	{
		$this->user->logout();
		$this->redirect("Sign:");
	}

}
