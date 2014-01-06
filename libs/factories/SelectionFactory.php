<?php

namespace App\Factories;

use Nette,
	App;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class SelectionFactory extends Nette\Object
{
	/** @var Nette\Security\User */
	private $tuser;

	/** @var Nette\Caching\IStorage */
	private $cacheStorage;


	public function __construct(Nette\Security\User $user, Nette\Caching\IStorage $cacheStorage = null)
	{
		$this->user = $user;
		$this->cacheStorage = $cacheStorage;
	}

	public function create($tableName, Nette\Database\Context $dbContext){
		return new App\Database\Selection($this->user, $dbContext->getConnection(), $tableName, $dbContext->getDatabaseReflection(), $this->cacheStorage);
	}

}