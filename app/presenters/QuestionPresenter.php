<?php

namespace Archivist;

use Archivist\Forum\Question;
use Nette;



class QuestionPresenter extends BasePresenter
{

	/**
	 * @persistent
	 */
	public $questionId;

	/**
	 * @var Question
	 */
	private $question;

	/**
	 * @var \Kdyby\Doctrine\EntityManager
	 * @autowire
	 */
	protected $em;



	public function actionDefault($questionId)
	{
		if (!$this->question = $this->em->getDao(Question::class)->find($questionId)) {
			$this->error();
		}
	}



	public function renderDefault($questionId)
	{
		$this->template->question = $this->question;
	}

}
