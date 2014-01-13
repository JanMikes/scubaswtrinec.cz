<?php

namespace App\Services;

use Nette;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class Authorizator extends Nette\Object implements Nette\Security\IAuthorizator
{
	public function isAllowed($role, $resource, $privilege)
	{
		return true;
	}
}