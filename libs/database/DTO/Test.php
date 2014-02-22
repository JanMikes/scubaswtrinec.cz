<?php

namespace App\Database\DTO;

use App,
	Nette;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class Test extends Nette\Object implements App\Database\IDataTransferObject
{
	use App\Database\TDefaultDataTransferObject;


	/**
	 * @column test
	 * @var string
	 */
	public $a = "foo";

	/**
	 * @column test_1
	 * @var string
	 */
	private $b = "bar";

	/**
	 * @column test_2
	 * @var string
	 */
	private $c;

	/** @column test_4 */
	public $d = "baz";

	/** @var array */
	public $e = array();


	public function __construct()
	{
		$this->c = "foobar";
	}
}