<?php

namespace App\Database\Entities;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class UserEntity extends Entity
{
	public function findByEmail($email)
	{
		return $this->findOneBy(array(
			"email" => $email,
		));
	}
}
