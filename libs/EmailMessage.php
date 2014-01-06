<?php

namespace App;

use Nette;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class EmailMessage extends Nette\Mail\Message {

	/** @var Nette\Templating\FileTemplate */
	public $template;

	/** @var App\Services\Mailer */
	private $customMailer;


	public function __construct(Services\Mailer $mailer, Nette\Templating\FileTemplate $template)
	{
		parent::__construct();

		$this->customMailer = $mailer;
		$this->template = $template;
	}


	public function send()
	{
		$this->setHtmlBody($this->template);
		$this->customMailer->send($this);
	}

}