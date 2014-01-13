<?php

namespace App\FrontendModule;

use App,
	Nette;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
abstract class BasePresenter extends App\BasePresenter
{
	/** @return CssLoader */
	protected function createComponentCss()
	{
		return $this->webLoader->createCssLoader('frontend');
	}


	/** @return JavaScriptLoader */
	protected function createComponentJs()
	{
		return $this->webLoader->createJavaScriptLoader('frontend');
	}
}
