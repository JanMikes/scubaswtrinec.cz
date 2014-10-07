<?php

namespace App\Components;

use App,
	App\Database\Entities\InstructorEntity;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class InstructorsList extends Component
{
	use TRecordHandlers;


	public function __construct(InstructorEntity $instructorEntity)
	{
		$this->entity = $instructorEntity;
	}


	public function render($code = null)
	{
		$template = $this->createTemplate();

		$template->rows = $this->entity->findAll();
		$template->inactiveCnt = $this->entity->getInactiveCnt($template->rows);

		$template->render();
	}
}
