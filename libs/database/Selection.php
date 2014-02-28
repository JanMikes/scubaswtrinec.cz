<?php

namespace App\Database;

use Nette,
	Nette\Database as NDB;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class Selection extends NDB\Table\Selection
{
	/**
	 * User object
	 * @var \Nette\Security\User
	 */
	private $user;

	/**
	 * Overrides default constructor and adds $user
	 * @param Nette\Security\User 			$user
	 * @param Nette\Database\Connection 	$connection
	 * @param string                    	$table
	 * @param Nette\Database\IReflection 	$reflection
	 * @param Nette\CachingIStorage 		$cacheStorage
	 */
	public function __construct(
		Nette\Security\User $user,
		NDB\Connection $connection,
		$table,
		NDB\IReflection $reflection,
		Nette\Caching\IStorage $cacheStorage = NULL
	) {
		parent::__construct($connection, $table, $reflection, $cacheStorage);

		$this->user = $user;
	}


	/**
	 * Overrides default insert method to add who inserted to each table
	 * @param  array|\Traversable|Selection array($column => $value)|\Traversable|Selection for INSERT ... SELECT
	 * @return IRow|int|bool Returns IRow or number of affected rows for Selection or table without primary key
	 */
	public function insert($data)
	{
		if ($data instanceof \Traversable) {
			$data->ins_user_id = $this->getUserId();
		} elseif (is_array($data)) {
			$data['ins_user_id'] = $this->getUserId();
		}

		return parent::insert($data);
	}


	/**
	 * Overrides default update method for adding information who updated to each table
	 * @param   array|\Traversable ($column => $value)
	 * @return int number of affected rows or FALSE in case of an error
	 */
	public function update($data)
	{
		if ($data instanceof \Traversable) {
			$data->upd_user_id = $this->getUserId();
		} elseif (is_array($data)) {
			$data['upd_user_id'] = $this->getUserId();
		}

		return parent::update($data);
	}


	/**
	 * Get actual logged in user_id
	 * @return int|NULL
	 */
	private function getUserId()
	{
		return ($this->user && $this->user->isLoggedIn()) ? $this->user->id : null;
	}
}
