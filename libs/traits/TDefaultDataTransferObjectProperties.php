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
	 * @default
	 * @var string
	 */
	public $insProcess;

	/**
	 * @column ins_user_id
	 * @default
	 * @var string
	 */
	public $insUserId;

	/**
	 * @column upd_process
	 * @default
	 * @var string
	 */
	public $updProcess;

	/**
	 * @column upd_user_id
	 * @default
	 * @var string
	 */
	public $updUserId;

	/**
	 * @column active_flag
	 * @default
	 * @var boolean
	 */
	public $activeFlag;

	/**
	 * @column del_flag
	 * @var boolean
	 */
	public $delFlag = 0;
}