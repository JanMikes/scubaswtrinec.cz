<?php

namespace App\Factories;

use Nette;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class FormFactory extends Nette\Object
{

	public function create()
	{
		return new Nette\Application\UI\Form;
	}

}
