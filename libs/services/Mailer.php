<?php

namespace App\Services;

use Nette;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class Mailer extends Nette\Object {

	/** @var Nette\Mail\IMailer */
	private $mailer;

	/** @var boolean */
	private $productionMode;

	/** @var string */
	private $from;

	/** @var array */
	private $developRecipients = array();


	public function __construct($productionMode, $from, $developRecipients, Nette\Mail\IMailer $mailer) {
		$this->productionMode = $productionMode;
		$this->mailer = $mailer;
		$this->from = $from;
		$this->developRecipients = $developRecipients;
	}


	public function send(Nette\Mail\Message $mail)
	{
		if (!$mail->getFrom()) {
			$mail->setFrom($this->from);
		}

		if ($this->productionMode) {
			// @TODO add optional logging emails into database
			$this->mailer->send($mail);
		} else {
			$mail->clearHeader("To");
			$mail->clearHeader("Bcc");
			$mail->clearHeader("Cc");
			if (count($this->developRecipients)) {
				foreach ($this->developRecipients as $to) {
					$mail->addTo($to);
				}
				$this->mailer->send($mail);
			}
		}
	}

}