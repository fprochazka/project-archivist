<?php

namespace Archivist\ForumModule;

use Archivist\Forum\Category;
use Archivist\Forum\Query\QuestionsQuery;
use Archivist\Forum\Question;
use Archivist\UI\BaseForm;
use Archivist\VisualPaginator;
use Nette;
use Nette\Forms\Controls\SubmitButton;



class NewestQuestionsPresenter extends BasePresenter
{

	/**
	 * @var \Archivist\Forum\Reader
	 * @autowire
	 */
	protected $reader;



	public function renderDefault($categoryId)
	{
		$query = (new QuestionsQuery())
			->withAnswersCount()
			->withCategory();

		/** @var TopicsPresenter|VisualPaginator[] $this */
		$this->template->topics = $this->reader->fetch($query)
			->setFetchJoinCollection(FALSE)
			->applyPaginator($this['vp']->getPaginator());
	}



	protected function createComponentVp()
	{
		return new VisualPaginator(50);
	}

}
