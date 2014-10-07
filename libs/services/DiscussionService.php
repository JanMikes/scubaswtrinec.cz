<?php

namespace App\Services;

use Nette,
	App\Database\Entities as DBE;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class DiscussionService extends Nette\Object
{
	/** @var App\Database\Entities\DiscussionBanEntity */
	private $discussionBanEntity;


	public function __construct(DBE\DiscussionBanEntity $discussionBanEntity)
	{
		$this->discussionBanEntity = $discussionBanEntity;
	}


	public function getBlockedIps()
	{
		return $this->discussionBanEntity->findAll()->fetchPairs("id", "ip");
	}


	public function isBanned($ip)
	{
		return ($this->discussionBanEntity->findAll()->where("ip", $ip)->count("id") > 0);
	}


	public function ban($ip, $reason = null)
	{
		$this->discussionBanEntity->insert(array(
			"ip" => $ip,
			"reason" => $reason,
			"ins_process" => __METHOD__,
		));
	}


	public function unban($ip)
	{
		$this->discussionBanEntity->findAll()->where("ip", $ip)->update(array(
			"del_flag" => 1,
			"upd_process" => __METHOD__,
		));
	}
}
