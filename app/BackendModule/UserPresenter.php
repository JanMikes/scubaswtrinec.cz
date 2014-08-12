<?php

namespace App\BackendModule;

use App\Factories\ChangePasswordFormFactory;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class UserPresenter extends SecuredPresenter
{
	protected function createComponentChangePasswordForm(ChangePasswordFormFactory $factory)
	{
		return $factory->create();
	}
}
