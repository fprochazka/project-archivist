<?php

namespace Archivist\ForumModule;

use Archivist\Forum\Query\QuestionsQuery;
use Archivist\ForumModule\Questions\IThreadsControlFactory;
use Archivist\Users\User;
use Nette;



class ProfilePresenter extends BasePresenter
{

	/**
	 * @persistent
	 */
	public $userId;

	/**
	 * @var User
	 */
	private $user;

	/**
	 * @var \Archivist\Users\Manager
	 * @autowire
	 */
	protected $users;



	public function actionDefault($userId)
	{
		if (!is_numeric($userId) || !$this->user = $this->users->find($userId)) {
			$this->error();
		}
	}



	protected function beforeRender()
	{
		parent::beforeRender();
		$this->template->userEntity = $this->user;
	}



	protected function createComponentThreads(IThreadsControlFactory $factory)
	{
		$query = (new QuestionsQuery())
			->byUser($this->user)
			->withAnswersCount()
			->withCategory();

		$control = $factory->create()->setQuery($query);
		$control->perPage = 10;
		return $control->setView('questions');
	}

}
