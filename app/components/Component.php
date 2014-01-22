<?php

namespace App\Components;

use Nette,
	Kdyby,
	App;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
abstract class Component extends Nette\Application\UI\Control
{
	use Kdyby\Autowired\AutowireComponentFactories;
	

	/** @var App\Factories\TemplateFactory */
	protected $templateFactory;

	/** @var Nette\Localization\ITranslator */
	protected $translator;


	public function setTranslator(Nette\Localization\ITranslator $translator)
	{
		$this->translator = $translator;
	}


	public function setTemplateFactory(App\Factories\TemplateFactory $templateFactory)
	{
		$this->templateFactory = $templateFactory;
	}


	public function render()
	{
		return $this->setupTemplate();
	}


	protected function createTemplate($class = NULL) {
		if (!is_null($this->templateFactory)) {
			return $this->templateFactory->createTemplate($this);
		}
		return parent::createTemplate($class);
	}


	protected function setupTemplate($view = "default", $filename = null)
	{
		$controlReflection = new Nette\Reflection\ClassType(get_class($this));
		$controlDir = dirname($controlReflection->getFileName());

		$filename = ($filename? $controlDir . DIRECTORY_SEPARATOR . $filename : $controlDir . DIRECTORY_SEPARATOR . "$view.latte");
		$this->template->setFile($filename);

		if (!is_null($this->translator)) {
			$this->template->setTranslator($this->translator);
		}

		return $this->template;
	}
}