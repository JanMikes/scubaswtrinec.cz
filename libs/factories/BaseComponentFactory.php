<?php

namespace App\Factories;

use Nette;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
abstract class BaseComponentFactory extends Nette\Object
{

	/** @var App\Factories\TemplateFactory @inject */
	protected $templateFactory;


	public function populateComponent(Nette\Application\UI\Control $component)
	{
		$component->setTemplateFactory($this->templateFactory);
		return $component;
	}

}
