<?php

namespace Archivist\ForumModule;

use Archivist\Forum\Category;
use Archivist\Forum\Question;
use Archivist\UI\BaseForm;
use Nette;
use Nette\Forms\Controls\SubmitButton;



class NewestQuestionsPresenter extends BasePresenter
{

	/**
	 * @var \Archivist\Forum\Reader
	 * @autowire
	 */
	protected $reader;



	public function renderDefault()
	{
		$this->template->topics = $this->reader->readTopics();
	}

}
