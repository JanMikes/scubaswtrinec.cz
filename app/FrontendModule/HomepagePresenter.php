<?php

namespace App\FrontendModule;

/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class HomepagePresenter extends BasePresenter
{
	protected function createComponentDiscussionForm(\App\Factories\ManageDiscussionFormFactory $factory)
	{
		return $factory->create(null);
	}
}
