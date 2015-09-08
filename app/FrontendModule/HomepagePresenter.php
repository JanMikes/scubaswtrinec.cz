<?php

namespace App\FrontendModule;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class HomepagePresenter extends BasePresenter
{
	/** @var App\Database\Entities\ActualityEntity @autowire */
	protected $actualityEntity;

	/** @var App\Database\Entities\ArticleEntity @autowire */
	protected $articleEntity;

	/** @var App\Database\Entities\DiscussionEntity @autowire */
	protected $discussionEntity;

	/** @var App\Database\Entities\LinkEntity @autowire */
	protected $linkEntity;

	/** @var App\Database\Entities\ContactEntity @autowire */
	protected $contactEntity;

	/** @var App\Database\Entities\InstructorEntity @autowire */
	protected $instructorEntity;

	/** @var App\Database\Entities\GalleryEntity @autowire */
	protected $galleryEntity;


	public function renderAktuality($page)
	{
		$this->template->actualities = $this->actualityEntity->findActive();

		$rowsCnt = clone $this->template->actualities;
		$rowsCnt = $rowsCnt->count("id");

		$paginator = new \Nette\Utils\Paginator;
		$paginator->setItemCount($rowsCnt);
		$paginator->setItemsPerPage(5);
		$paginator->setPage($page);

		$this->template->actualities->limit($paginator->getLength(), $paginator->getOffset());
		$this->template->paginator = $paginator;
	}


	public function actionDetailAktuality($id)
	{
		$this->template->actuality = $this->actualityEntity->findActive($id);
		if (!$id || !$this->template->actuality) {
			$this->redirect("aktuality");
		}

		$this->template->photos = $this->template->actuality->related("actuality_photo")->where("del_flag", 0)->where("active_flag", 1);
	}


	public function renderClanky($page)
	{
		$this->template->articles = $this->articleEntity->findActive();

		$rowsCnt = clone $this->template->articles;
		$rowsCnt = $rowsCnt->count("id");

		$paginator = new \Nette\Utils\Paginator;
		$paginator->setItemCount($rowsCnt);
		$paginator->setItemsPerPage(5);
		$paginator->setPage($page);

		$this->template->articles->limit($paginator->getLength(), $paginator->getOffset());
		$this->template->paginator = $paginator;
	}


	public function actionDetailClanku($id)
	{
		$this->template->article = $this->articleEntity->findActive($id);
		if (!$id || !$this->template->article) {
			$this->redirect("clanky");
		}

		$this->template->photos = $this->template->article->related("article_photo")->where("del_flag", 0)->where("active_flag", 1);
	}


	public function renderGalerie($page)
	{
		$this->template->galleries = $this->galleryEntity->findActive();

		$rowsCnt = clone $this->template->galleries;
		$rowsCnt = $rowsCnt->count("id");

		$paginator = new \Nette\Utils\Paginator;
		$paginator->setItemCount($rowsCnt);
		$paginator->setItemsPerPage(9);
		$paginator->setPage($page);

		$this->template->galleries->limit($paginator->getLength(), $paginator->getOffset());
		$this->template->paginator = $paginator;
	}


	public function actionDetailGalerie($id)
	{
		$this->template->gallery = $this->galleryEntity->findActive($id);
		if (!$id || !$this->template->gallery) {
			$this->redirect("galerie");
		}

		$this->template->photos = $this->template->gallery->related("gallery_photo")->where("del_flag", 0)->where("active_flag", 1);
	}


	public function renderInstruktori()
	{
		$this->template->instructors = $this->instructorEntity->findActive()->order("order DESC");
	}


	public function renderDiskuze($page)
	{
		$this->template->discussion = $this->discussionEntity->findActive();

		$rowsCnt = clone $this->template->discussion;
		$rowsCnt = $rowsCnt->count("id");

		$paginator = new \Nette\Utils\Paginator;
		$paginator->setItemCount($rowsCnt);
		$paginator->setItemsPerPage(10);
		$paginator->setPage($page);

		$this->template->discussion->limit($paginator->getLength(), $paginator->getOffset());
		$this->template->paginator = $paginator;
	}


	public function renderOdkazy()
	{
		$this->template->links = $this->linkEntity->findActive();
	}


	public function renderKontakt()
	{
		$this->template->contact = $this->contactEntity->getLast();
	}


	protected function beforeRender()
	{
		parent::beforeRender();

		$this->template->recentActualities = $this->actualityEntity->findActive()->limit(3)->order("date DESC");
		$this->template->recentGalleries = $this->galleryEntity->findActive()->limit(3)->order("order DESC");
	}


	protected function createComponentDiscussionForm(\App\Factories\ManageDiscussionFormFactory $factory)
	{
		$form = $factory->create(null);
		$form["sendForm"]->onClick[] = function($button) {
			if ($button->form->isSuccess()) {
				$this->flashMessage("Vaše zpráva byla úspěšně odeslána!", "success");
				$this->redirect("this");
			}
		};
		return $form;
	}
}
