<?php

namespace App\Components;

/**
 *  Trait containing handling methods, for components in backend application
 *
 *  Only classes extending Nette\Application\UI\Control should be using this trait
 *
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
trait TRecordHandlers
{
	/** @var App\Database\Entities\Entity */
	protected $entity;

	/**
	 * This property is expected to be overwritten by parent
	 * @var array
	 */
	protected $additionalReorderConditions = array();


	public function handleDelete($id)
	{
		$presenter = $this->presenter;
		try {
			$this->entity->delete($id);
			$presenter->flashMessage("Záznam úspěšně smazán", "success");
		} catch (\Exception $e) {
			throw $e;
			$presenter->flashMessage("Omlouváme se, při mazání došlo k chybě!", "danger");
		}
		$presenter->redirect("this");
	}


	public function handleActivate($id)
	{
		$presenter = $this->presenter;
		try {
			$this->entity->activate($id);
			$presenter->flashMessage("Záznam úspěšně aktivován", "success");
		} catch (\Exception $e) {
			$presenter->flashMessage("Omlouváme se, při aktivaci došlo k chybě!", "danger");
		}
		$presenter->redirect("this");
	}


	public function handleActivateAll()
	{
		$presenter = $this->presenter;
		try {
			$this->entity->activateAll();
			$presenter->flashMessage("Záznamy úspěšně aktivovány", "success");
		} catch (\Exception $e) {
			$presenter->flashMessage("Omlouváme se, při aktivaci došlo k chybě!", "danger");
		}
		$presenter->redirect("this");
	}


	public function handleDeactivate($id)
	{
		$presenter = $this->presenter;
		try {
			$this->entity->deactivate($id);
			$presenter->flashMessage("Záznam úspěšně deaktivován", "success");
		} catch (\Exception $e) {
			$presenter->flashMessage("Omlouváme se, při deaktivaci došlo k chybě!", "danger");
		}
		$presenter->redirect("this");
	}


	public function handleChangeRowOrder($id, $direction)
	{
		$presenter = $this->presenter;

		$result = $this->entity->changeRowOrder($id, $direction, $this->additionalReorderConditions);

		if ($result) {
			$presenter->flashMessage("Pořadí bylo úspěšně změněno!", "success");
		} else {
			$presenter->flashMessage("Omlouváme se, došlo k chybě, opakujte prosím akci později!", "danger");
		}
		$presenter->redirect("this");
	}

}
