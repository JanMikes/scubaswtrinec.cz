<?php

namespace App\Components;

use Nette,
	Kdyby,
	App\Factories\TemplateFactory;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
abstract class Component extends Nette\Application\UI\Control
{
	use Kdyby\Autowired\AutowireComponentFactories;


	/** @var App\Factories\TemplateFactory */
	protected $templateFactory;

	public function injectTemplateFactory(TemplateFactory $templateFactory)
	{
		$this->templateFactory = $templateFactory;
	}


	protected function createTemplate($class = NULL) {
		$template = (!is_null($this->templateFactory) ? $this->templateFactory->createTemplate($this) : parent::createTemplate($class));
		return $this->setupTemplate($template);
	}


	protected function setupTemplate(Nette\Templating\ITemplate $template, $view = "default", $filename = null)
	{
		$controlReflection = new Nette\Reflection\ClassType(get_class($this));
		$controlDir = dirname($controlReflection->getFileName());

		$filename = ($filename? $controlDir . DIRECTORY_SEPARATOR . $filename : $controlDir . DIRECTORY_SEPARATOR . "$view.latte");
		$template->setFile($filename);

		return $template;
	}
}
