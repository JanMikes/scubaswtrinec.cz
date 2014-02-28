<?php

namespace App\InjectEnabler;

use Nette\Configurator,
	Nette\DI\Compiler,
	Nette\DI\CompilerExtension;

if (!class_exists('Nette\DI\CompilerExtension')) {
	class_alias('Nette\Config\Compiler', 'Nette\DI\Compiler');
	class_alias('Nette\Config\Configurator', 'Nette\Configurator');
	class_alias('Nette\Config\CompilerExtension', 'Nette\DI\CompilerExtension');
}

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class Extension extends CompilerExtension
{
	const EXTENSION_NAME = "injectEnabler";


	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();

		foreach ($builder->definitions as $definition) {
			if ($definition->implement && method_exists($definition->implement, 'create')) {
				$definition->setInject(TRUE);
			}
		}
	}


	public function install(Configurator $configurator)
	{
		$self = $this;
		$configurator->onCompile[] = function ($configurator, Compiler $compiler) use ($self) {
			$compiler->addExtension($self::EXTENSION_NAME, $self);
		};
	}
}
