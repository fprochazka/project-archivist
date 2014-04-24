<?php

namespace Archivist;

use Archivist\Forum\Answer;
use Archivist\Forum\ModificationsNotAllowedException;
use Archivist\Forum\Post;
use Archivist\Forum\PostIsNotReadableException;
use Archivist\Forum\PostQuestionCollisionException;
use Archivist\Forum\Question;
use Archivist\Forum\Reader;
use Archivist\Security\Role;
use Archivist\UI\BaseForm;
use Nette;
use Nette\Forms\Controls\SubmitButton;
use Nette\Forms\Controls\TextInput;



class QuestionPresenter extends BasePresenter
{

	/**
	 * @persistent
	 */
	public $questionId;

	/**
	 * @persistent
	 */
	public $postId;

	/**
	 * @var Question
	 */
	private $question;

	/**
	 * @var Question|Answer
	 */
	private $editingPost;

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

	/**
	 * @var \Archivist\Forum\Reader
	 * @autowire
	 */
	protected $reader;



	protected function startup()
	{
		parent::startup();

		try {
			if (!$this->question = $this->reader->readQuestion($this->questionId)) {
				$this->error("Topic not found");
			}

		} catch (PostIsNotReadableException $e) {
			$this->error($e->getMessage());
		}
	}



	protected function beforeRender()
	{
		parent::beforeRender();
		$this->template->question = $this->question;
	}



	public function renderDefault($questionId)
	{
		$this->template->answers = $this->reader->readAnswers($this->question);

		if ($this->postId && !$this->isSignalReceiver($this)) {
			$this->redirect('this', ['postId' => NULL]);
		}
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
			if (!$this->question) {
				$this->error();
			}

			if (!$this->user->isLoggedIn()) {
				$form->addError("Please login first before posting");
				return;
			}

			$identity = $this->getUser()->getIdentity();
			$identity->getUser()->name = $values->username;

			$this->writer->answerQuestion(new Answer($values->content), $this->question);

			$this->redirect('this');
		};

		$form->setupBootstrap3Rendering();
		return $form;
	}



	public function actionEdit($postId)
	{
		try {
			if (!$this->editingPost = $this->reader->readForModification($postId)) {
				$this->error("Post not found");
			}

		} catch (PostIsNotReadableException $e) {
			$this->error($e->getMessage());

		} catch (ModificationsNotAllowedException $e) {
			$this->error($e->getMessage());
		}

		return $this->editingPost;
	}



	public function renderEdit($postId)
	{
		$this->template->editingPost = $this->editingPost;
	}



	public function handleToggleResolved($postId)
	{
		try {
			if (!$answer = $this->reader->readAnswer($postId, $this->question)) {
				$this->error("Post not found");
			}

			$this->writer->toggleResolvedBy($this->question, $answer);
			$this->em->flush();

		} catch (PostIsNotReadableException $e) {
			$this->error($e->getMessage());
		}

		$this->redirect('this', ['postId' => NULL]);
	}



	public function handleDelete($postId)
	{
		if (!$this->actionEdit($postId)) {
			$this->error();
		}

		$this->editingPost->deleted = TRUE;
		$this->em->flush();

		if ($this->editingPost->isQuestion()) {
			$this->flashMessage("Topic '" . $this->editingPost->getTitle() . "' was deleted.", 'danger');
			$this->redirect('Topics:', ['categoryId' => $this->editingPost->category->getId()]);
		}

		$this->flashMessage("Post was deleted.", 'danger');
		$this->redirect('this', ['postId' => NULL]);
	}



	public function handleMarkAsSpam($postId)
	{
		if (!$this->actionEdit($postId)) {
			$this->error();
		}

		$this->editingPost->spam = TRUE;
		$this->em->flush();

		if ($this->editingPost->isQuestion()) {
			$this->flashMessage("Topic '" . $this->editingPost->getTitle() . "' was marked as spam.", 'danger');
			$this->redirect('Topics:', ['categoryId' => $this->editingPost->category->getId()]);
		}

		$this->flashMessage("Post was marked as spam.", 'danger');
		$this->redirect('this', ['postId' => NULL]);
	}



	protected function createComponentEditPostForm(IPostFormFactory $factory)
	{
		if (!$this->editingPost) {
			$this->error();
		}

		/** @var PostForm|SubmitButton[] $form */
		$form = $factory->create();
		$form['send']->caption = "Save changes";
		$form['content']->setAttribute('rows', 25);

		if ($this->editingPost->isQuestion()) {
			$form->addTitle($this->editingPost->getTitle());
		}

		/** @var QuestionPresenter|BaseForm[] $this */
		$form->setDefaults(['content' => $this->editingPost->getContent()]);

		$form->onSuccess[] = function (PostForm $form, $values) {
			$this->editingPost->editContent($values->content);

			if ($this->editingPost->isQuestion()) {
				$this->editingPost->changeTitle($values->title);
			}

			$this->em->flush();

			$this->redirect('Question:', array('postId' => NULL));
		};

		$form->setupBootstrap3Rendering();
		return $form;
	}

}
