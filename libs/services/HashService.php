<?php

namespace App\Services;

use Nette;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class HashService extends Nette\Object
{
	/** @var string */
	private $leadingSalt;

	/** @var string */
	private $trailingSalt;


	public function __construct($leadingSalt, $trailingSalt)
	{
		$this->leadingSalt = $leadingSalt;
		$this->trailingSalt = $trailingSalt;
	}


	/**
	 * @param  string $string
	 * @return string
	 */
	public function calculateHash($string)
	{
		return md5($this->leadingSalt . $string . $this->trailingSalt);
	}
}
