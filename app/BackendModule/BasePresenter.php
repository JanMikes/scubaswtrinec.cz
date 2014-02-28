<?php

namespace App\BackendModule;

use App;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
abstract class BasePresenter extends App\BasePresenter
{
	/** @return CssLoader */
	protected function createComponentCss()
	{
		return $this->webLoader->createCssLoader('backend');
	}


	/** @return JavaScriptLoader */
	protected function createComponentJs()
	{
		return $this->webLoader->createJavaScriptLoader('backend');
	}
}
