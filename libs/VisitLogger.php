<?php

namespace App\Services;

use Nette,
	App\Database\Entities\LogVisitEntity,
	Nette\Http\Request as HttpRequest,
	App\Database\DTO;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class VisitLogger extends Nette\Object
{
	/** @var Nette\Http\Request */
	private $httpRequest;

	/** @var string */
	private $serverAddress;

	/** @var App\Database\Entities\LogVisitEntity */
	private $logVisitEntity;

	/** @var Nette\Database\Table\ActiveRow */
	private $lastLogRow;

	/** @var boolean */
	private $productionMode;


	public function __construct($productionMode, LogVisitEntity $logVisitEntity, HttpRequest $httpRequest)
	{
		$this->productionMode = $productionMode;
		$this->logVisitEntity = $logVisitEntity;
		$this->httpRequest = $httpRequest;
		$this->serverAddress = $_SERVER["SERVER_ADDR"];
	}


	/** 
	 * Inserts detailed log into database, about user's visit
	 * @return Nette\Database\Table\ActiveRow|FALSE
	 */
	public function log()
	{
		if (!$this->productionMode) {
			return false;
		}

		$data = array(
			"ajax_flag" => $this->httpRequest->isAjax(),
			"url" => $this->httpRequest->getUrl()->getPath(),
			"http_method" => $this->httpRequest->getMethod(),
			"http_get" => $this->createQueryString($this->httpRequest->getQuery()),
			"http_post" => $this->createQueryString($this->httpRequest->getPost()),
			"remote_ip" => $this->httpRequest->getRemoteAddress(),
			"server_ip" => $this->serverAddress,
			"user_agent" => $this->httpRequest->getHeader("User-Agent"),
			"referral" => $this->httpRequest->getReferer(),
			"ins_dt" => new \DateTime,
		);

		return ($this->lastLogRow = $this->logVisitEntity->insert(DTO\LogVisit::fromArray($data)));
	}


	/**
	 * @param   float $elapsed
	 * @return  boolean
	 */
	public function updateElapsedTime($elapsed)
	{
		return ($this->productionMode ? $this->lastLogRow->update(["elapsed" => $elapsed]) : false);
	}


    /** 
     * Converts array into string that is saved into database
     * @param  array $arr
     * @return string
     */
    private function createQueryString($arr)
    {
    	if (empty($arr)) {
    		return null;
    	}

    	$ret = "?";
    	foreach ($arr as $k => $v) {
    		if ($ret <> "?") {
    			$ret .= "&";
    		}
    		$ret .= print_r($k, true) . "=" . print_r($v, true);
    	}

    	return $ret;
    }

}
