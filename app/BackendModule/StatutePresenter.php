<?php

namespace App\BackendModule;

use App,
	App\Factories\IActualitiesListFactory,
	App\Factories\ManageStatuteFormFactory;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class StatutePresenter extends SecuredPresenter
{
	/** @var App\Database\Entities\StatuteEntity @autowire */
	protected $statuteEntity;


	public function actionDefault()
	{
		$row = $this->statuteEntity->getLast();		
		if ($row) {
			$this["manageStatuteForm"]->setDefaults($row->toArray());
		}
	}


	protected function createComponentManageStatuteForm(ManageStatuteFormFactory $factory)
	{
		return $factory->create();
	}
}
