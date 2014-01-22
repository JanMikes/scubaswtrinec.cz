<?php

namespace App\Factories;

use Nette,
	App;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class GAFactory extends ComponentFactory
{

	public function create()
	{
		return $this->populateComponent(new App\Components\GA);
	}

}
