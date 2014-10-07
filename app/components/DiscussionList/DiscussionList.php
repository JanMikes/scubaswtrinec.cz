<?php

namespace App\Components;

use App,
	App\Database\Entities\DiscussionEntity,
	App\Database\Entities\DiscussionBanEntity,
	App\Services\DiscussionService;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class DiscussionList extends Component
{
	use TRecordHandlers;

	private $discussionBanEntity;

	private $discussionService;


	public function __construct(DiscussionEntity $discussionEntity, DiscussionBanEntity $discussionBanEntity, DiscussionService $discussionService)
	{
		$this->entity = $discussionEntity;
		$this->discussionBanEntity = $discussionBanEntity;
		$this->discussionService = $discussionService;
	}


	public function render($code = null)
	{
		$template = $this->createTemplate();

		$template->bans = $this->discussionBanEntity->findAll();

		$template->rows = $this->entity->findAll();
		$template->inactiveCnt = $this->entity->getInactiveCnt($template->rows);

		$template->blockedIps = $this->discussionService->getBlockedIps();

		$template->render();
	}


	public function handleBan($ip, $reason = null)
	{
		$this->discussionService->ban($ip, $reason);
		$this->presenter->flashMessage("Blokace úspěšně přidána!");
		$this->redirect("this");
	}


	public function handleUnban($ip)
	{
		$this->discussionService->unban($ip);
		$this->presenter->flashMessage("Blokace úspěšně zrušena!");
		$this->redirect("this");
	}
}
