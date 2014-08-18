<?php

namespace App\BackendModule;

use App,
	App\Factories\IActualitiesListFactory,
	App\Factories\ManageContactFormFactory;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class ContactPresenter extends SecuredPresenter
{
	/** @var App\Database\Entities\ContactEntity @autowire */
	protected $contactEntity;


	public function actionDefault()
	{
		$row = $this->contactEntity->getLast();		
		if ($row) {
			$this["manageContactForm"]->setDefaults($row->toArray());
		}
	}


	protected function createComponentManageContactForm(ManageContactFormFactory $factory)
	{
		return $factory->create();
	}
}
