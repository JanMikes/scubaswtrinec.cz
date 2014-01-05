<?php

namespace App;

use Nette,
	Kdyby;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter
{

	use Kdyby\Autowired\AutowireProperties;
	use Kdyby\Autowired\AutowireComponentFactories;


	/** @var WebLoader\LoaderFactory @autowire */
	protected $webLoader;

	/** @var App\Factories\TemplateFactory @autowire */
	protected $templateFactory;


	/**
	 * Overwrites original nette creating template
	 * @param  string $class
	 * @return 
	 */
	public function createTemplate($class = null)
	{
		return $this->templateFactory->createTemplate($this);
	}


	protected function startup()
	{
		parent::startup();
		
		if (!$this->hasFlashSession() && $this->getParam(self::FLASH_KEY) ) {
			unset($this->params[self::FLASH_KEY]);
			$this->redirect(301, 'this');
		}
	}


	/** @return CssLoader */
	protected function createComponentCss()
	{
		return $this->webLoader->createCssLoader('default');
	}


	/** @return JavaScriptLoader */
	protected function createComponentJs()
	{
		return $this->webLoader->createJavaScriptLoader('default');
	}

}
