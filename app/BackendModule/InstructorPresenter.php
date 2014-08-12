<?php

namespace App\BackendModule;

use App,
	App\Factories\IActualitiesListFactory,
	App\Factories\ManageInstructorFormFactory;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class InstructorPresenter extends SecuredPresenter
{
	/** @var App\Database\Entities\InstructorEntity @autowire */
	protected $instructorEntity;


	public function actionDefault()
	{
		$row = $this->instructorEntity->getLast();		
		if ($row) {
			$this["manageInstructorForm"]->setDefaults($row->toArray());
		}
	}


	protected function createComponentManageInstructorForm(ManageInstructorFormFactory $factory)
	{
		return $factory->create();
	}
}
