<?php

namespace Archivist\ForumModule;

use Archivist\Forum\Query\QuestionsQuery;
use Archivist\ForumModule\Questions\IThreadsControlFactory;
use Nette;



class NewestQuestionsPresenter extends BasePresenter
{

	public function actionDefault($categoryId)
	{
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
