<?php

namespace App\Components;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class GA extends BaseComponent
{

	public function render($code = null)
	{
		$template = parent::render();
		$template->code = $code;
		
		$template->render();
	}

}