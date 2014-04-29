<?php

namespace Archivist\ForumModule;

use Archivist\Forum\Query\QuestionsQuery;
use Archivist\ForumModule\Questions\IThreadsControlFactory;
use Archivist\Rss\FeedControl;
use Archivist\Rss\IFeedControlFactory;
use Kdyby\NewsFeed\Channel;
use Nette;



class NewestQuestionsPresenter extends BasePresenter
{

	public function actionDefault($categoryId)
	{
	}



	protected function createComponentRss(IFeedControlFactory $factory)
	{
		$control = $factory->create();

		$control->onAttached[] = function (FeedControl $control) {
			$control->getChannel()->setTitle('Newest questions - help.kdyby.org');
			$control->setQuery(new QuestionsQuery());
		};

		return $control;
	}



	protected function createComponentThreads(IThreadsControlFactory $factory)
	{
		$query = (new QuestionsQuery())
			->withAnswersCount()
			->withCategory();

		return $factory->create()
			->setView('questions')
			->setQuery($query);
	}

}
