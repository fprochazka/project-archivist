<?php

namespace Archivist\ForumModule;

use Archivist\Forum\Query\PostsQuery;
use Archivist\ForumModule\Posts\IPostsControlFactory;
use Archivist\Rss\FeedControl;
use Archivist\Rss\IFeedControlFactory;
use Kdyby\Doctrine\Hydration\HashHydrator;
use Nette;



class LatestPostsPresenter extends BasePresenter
{

	public function actionDefault($categoryId)
	{
	}



	protected function createComponentRss(IFeedControlFactory $factory)
	{
		$control = $factory->create();

		$control->onAttached[] = function (FeedControl $control) {
			$query = (new PostsQuery())
				->withCategory()
				->withQuestion();

			$control->getChannel()->setTitle('Newest posts - help.kdyby.org');
			$control->setQuery($query, HashHydrator::NAME);
		};

		return $control;
	}



	protected function createComponentPosts(IPostsControlFactory $factory)
	{
		$query = (new PostsQuery())
			->withCategory()
			->withQuestion();

		return $factory->create()
			->setQuery($query);
	}



	protected function beforeRender()
	{
		$this->template->robots = "noindex, nofollow";
		parent::beforeRender();
	}

}
