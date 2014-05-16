<?php

namespace App;

use Nette,
	Nette\Diagnostics\Debugger,
	Kdyby\Autowired,
	App\Factories\IGAFactory;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter
{
	use Autowired\AutowireProperties;
	use Autowired\AutowireComponentFactories;


	/** @var WebLoader\LoaderFactory @autowire */
	protected $webLoader;

	/** @var App\Factories\TemplateFactory @autowire */
	protected $templateFactory;

	/** @var App\Services\VisitLogger @autowire */
	protected $visitLogger;

	/** @var App\Factories\SelectionFactory @autowire */
	protected $selectionFactory;


	/**
	 * Overwrites original nette creating template
	 * @param  string $class
	 * @return
	 */
	public function createTemplate($class = null)
	{
		return $this->templateFactory->createTemplate($this);
	}


	public function shutdown($response)
	{
		parent::shutdown($response);

		$this->visitLogger->updateElapsedTime(Debugger::timer("global"));
	}


	protected function startup()
	{
		parent::startup();

		// This is required due to circular reference issue
		$this->selectionFactory->setUser($this->user);

		Debugger::timer("global");

		$this->visitLogger->log();

		if (!$this->hasFlashSession() && $this->getParameter(self::FLASH_KEY) ) {
			unset($this->params[self::FLASH_KEY]);
			$this->redirect(301, 'this');
		}
	}


	protected function beforeRender()
	{
		parent::beforeRender();

		$fid = $this->getParam(self::FLASH_KEY);
		$key = $this->getParam("key");
        if ($fid !== NULL || $key) {
            $this->template->canonical = $this->link('//this', array("key" => ""));
        }
	}


	/** @return App\Components\GA */
	protected function createComponentGa(IGAFactory $factory)
	{
		return $factory->create();
	}
}
