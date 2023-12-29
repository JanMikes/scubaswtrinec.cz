<?php

use Nette\Application\Routers\Route;

require __DIR__ . '/../vendor/autoload.php';

$configurator = new Nette\Configurator;

if (getenv('NETTE_DEBUG') !== false) {
    $configurator->setDebugMode(true);
}

if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $_SERVER['HTTPS'] = 'On';
    $_SERVER['SERVER_PORT'] = '443';
}

if (isset($_SERVER['HTTPS'])) {
    Route::$defaultFlags = Route::SECURED;
}

$configurator->enableDebugger(__DIR__ . '/../log');

$configurator->setTempDirectory(__DIR__ . '/../temp');

$configurator->createRobotLoader()
	->addDirectory(__DIR__)
	->addDirectory(__DIR__ . '/../libs')
	->register();

$configurator->addConfig(__DIR__ . '/config/config.neon');

if (is_file(__DIR__ . '/config/config.local.neon')) {
    $configurator->addConfig(__DIR__ . '/config/config.local.neon');
}

$container = $configurator->createContainer();

return $container;
