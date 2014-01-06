<?php

namespace App\Factories;

use Nette,
	App;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class EmailMessageFactory extends Nette\Object {

	/** @var App\Factories\TemplateFactory */
	private $templateFactory;

	/** @var App\Services\Mailer */
	private $mailer;


	public function __construct(TemplateFactory $templateFactory, App\Services\Mailer $mailer)
	{
		$this->templateFactory = $templateFactory;
		$this->mailer = $mailer;
	}

	public function create($name, $lang = null){
		$template = $this->templateFactory->createEmailTemplate($name, $lang);

		return new App\EmailMessage($this->mailer, $template);
	}

}