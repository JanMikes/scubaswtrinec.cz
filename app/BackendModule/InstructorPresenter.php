<?php

namespace App\BackendModule;

use App,
	App\Factories\IInstructorsListFactory,
	App\Factories\ManageInstructorFormFactory;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class InstructorPresenter extends SecuredPresenter
{
	/** @persistent int */
	public $id;

	/** @var App\Database\Entities\InstructorEntity @autowire */
	protected $instructorEntity;


	public function startup()
	{
		parent::startup();

		if ($this->view != "edit") {
			$this->id = null;
		}
	}


	public function actionEdit($id)
	{
		$this->template->instructor = $this->instructorEntity->find($id);
		if (!$this->template->instructor) {
			$this->redirect("default");
		}

		$defaults = $this->template->instructor->toArray();

		$this["manageInstructorForm"]->setDefaults($defaults);
	}


	protected function createComponentInstructorsList(IInstructorsListFactory $factory)
	{
		return $factory->create();
	}


	protected function createComponentManageInstructorForm(ManageInstructorFormFactory $factory)
	{
		return $factory->create($this->id);
	}
}
