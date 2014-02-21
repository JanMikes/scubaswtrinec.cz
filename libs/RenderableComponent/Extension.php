<?php

namespace App\RenderableComponent;

use Nette,
	Nette\Configurator,
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
	const EXTENSION_NAME = "renderableComponent";


	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();
		$definitions = $builder->getDefinitions();

		$templateFactory = $builder->getDefinition("templateFactory");

		foreach ($definitions as $definition) {
			if (!$definition->implement || !method_exists($definition->implement, "create")) {
				continue;
			}

			$methodReflection = Nette\Reflection\Method::from($definition->implement, "create");
			$returnAnnotation = $methodReflection->getAnnotation("return");
			if ($returnAnnotation) {
				$classReflection = new Nette\Reflection\ClassType($returnAnnotation);
				
				if ($classReflection->implementsInterface("App\RenderableComponent\IComponent")) {
					$definition->addSetup("setTemplateFactory", array($templateFactory));
				}
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