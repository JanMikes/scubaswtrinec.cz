<?php

namespace App\RenderableComponent;

use App;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
interface IComponent
{
	public function setTemplateFactory(App\Factories\TemplateFactory $templateFactory);
}