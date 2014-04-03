<?php

namespace App\Database;

use Nette,
	Nette\Database as NDB;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class ContextPool extends Nette\Object
{
	/** @var array */
	private $settings = array();

	/** @var array */
	private $contexts = array();

	/** @var Nette\Caching\IStorage */
	private $cacheStorage;


	public function __construct(array $settings, Nette\Caching\IStorage $cacheStorage = null)
	{
		$this->settings = $settings;
		$this->cacheStorage = $cacheStorage;
	}


	/**
	 * @param  string $name
	 * @return Nette\Database\Connection
	 */
	public function getContext($name = "default")
	{
		if (!isset($this->contexts[$name])) {
			$this->contexts[$name] = $this->createContext($name);
		}

		return $this->contexts[$name];
	}


	/////////////////////
	// PRIVATE METHODS //
	/////////////////////


	/**
	 * @return Nette\Database\Connection
	 * @throws Nette\InvalidArgumentException
	 */
	private function createContext($name)
	{
		if (empty($this->settings[$name])) {
			throw new Nette\InvalidArgumentException("Context '$name' definition is missing in config!");
		}

		$config = $this->settings[$name];
		$connection = new NDB\Connection(isset($config["dsn"]) ? $config["dsn"] : "$config[driver]:host=$config[host];dbname=$config[dbname]", $config["user"], $config["password"]);

		// Panels will be created and logged only if debug mode is enabled
		if (Nette\Diagnostics\Debugger::isEnabled()) {
			Nette\Diagnostics\Debugger::addPanel(new NDB\Diagnostics\ConnectionPanel($connection), $name . "Connection");
		}

		return new NDB\Context($connection, new NDB\Reflection\DiscoveredReflection($connection, $this->cacheStorage), $this->cacheStorage);
	}

}
