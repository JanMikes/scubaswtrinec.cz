<?php

namespace App\Database;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
trait TDefaultDataTransferObjectProperties
{
	/**
	 * @column ins_process
	 * @var string
	 */
	public $insProcess;

	/**
	 * @column ins_user_id
	 * @var string
	 */
	public $insUserId;

	/**
	 * @column upd_process
	 * @var string
	 */
	public $updProcess;

	/**
	 * @column upd_user_id
	 * @var string
	 */
	public $updUserId;

	/**
	 * @column active_flag
	 * @var boolean
	 */
	public $activeFlag = 0;

	/**
	 * @column del_flag
	 * @var boolean
	 */
	public $delFlag = 0;
}