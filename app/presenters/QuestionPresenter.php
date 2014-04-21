<?php

namespace Archivist;

use Archivist\Forum\Answer;
use Archivist\Forum\Question;
use Archivist\UI\BaseForm;
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

	/**
	 * @var \Archivist\Forum\Writer
	 * @autowire
	 */
	protected $writer;



	public function actionDefault($questionId)
	{
		if (!$this->question = $this->em->getDao(Question::class)->find($questionId)) {
			$this->error();
		}
	}



	public function renderDefault($questionId)
	{
		$this->template->question = $this->question;

		$answers = $this->em->getDao(Answer::class);
		$qb = $answers->createQueryBuilder('a')
			->andWhere('a.question = :question')->setParameter('question', $this->question->getId())
			->orderBy('a.createdAt', 'ASC');

		$this->template->answers = $qb->getQuery()->getResult();
	}



	/**
	 * @return BaseForm
	 */
	protected function createComponentAnswerForm()
	{
		$form = new BaseForm();

		$form->addText('username', 'Your name')
			->setDefaultValue($this->user->getIdentity()->name)
			->setRequired();

		$form->addTextArea('content', 'Answer')
			->setAttribute('rows', 10)
			->setRequired();

		$form->addSubmit("send", "Post answer");
		$form->onSuccess[] = function (BaseForm $form, $values) {
			if (!$this->user->isLoggedIn()) {
				$form->addError("Please login first before posting");
				return;
			}

			if (!$this->question) {
				$this->error();
			}

			$identity = $this->getUser()->getIdentity();
			$user = $identity->getUser();
			$user->name = $values->username;

			$this->writer->answerQuestion(new Answer($values->content), $this->question);

			$this->redirect('this');
		};

		$form->setupBootstrap3Rendering();
		return $form;
	}

}
