<?php

namespace App\Database\DTO;

use App,
	Nette;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class LogVisit extends Nette\Object implements App\Database\IDataTransferObject
{
	use App\Database\TDefaultDataTransferObject;


	/** @column ajax_flag */
	public $ajaxFlag;

	/** @column url */
	public $url;

	/** @column http_method */
	public $httpMethod;

	/** @column http_get */
	public $httpGet;

	/** @column http_post */
	public $httpPost;

	/** @column remote_ip */
	public $remoteIp;

	/** @column server_ip */
	public $serverIp;

	/** @column user_agent */
	public $userAgent;

	/** @column referral */
	public $referral;

	/** @column elapsed */
	public $elapsed;

	/** @column ins_user_id */
	public $insUserId;

	/** @column ins_dt */
	private $insDt;


	public function setInsDt(\DateTime $dt)
	{
		$this->insDt = $dt;
	}


	public function getInsDt()
	{
		return $this->insDt->format("Y-m-d H:i:s");
	}
}
