<?php

namespace App\Components;

use App;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class GA extends Component
{
	public function render($code = null)
	{
		$template = $this->createTemplate();

		$template->code = $code;
		
		$template->render();
	}
}