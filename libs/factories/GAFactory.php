<?php

namespace App\Factories;

use Nette,
	App;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class GAFactory extends Nette\Object
{

	public function create()
	{
		return new App\Components\GA;
	}

}
