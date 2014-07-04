<?php

namespace Archivist\ForumModule;

use Archivist\Forum\Answer;
use Archivist\Forum\ModificationsNotAllowedException;
use Archivist\Forum\Post;
use Archivist\Forum\PostIsNotReadableException;
use Archivist\Forum\Question;
use Archivist\ForumModule\OnePost\IPostControlFactory;
use Archivist\UI\BaseForm;
use Archivist\VisualPaginator;
use Nette;
use Nette\Application\UI\Multiplier;
use Nette\Forms\Controls\SubmitButton;



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
		/** @var QuestionPresenter|VisualPaginator[] $this */
		parent::startup();

		if ($this->action === 'default' && $permalinkId = $this->getParameter('permalinkId')) {
			if ($position = $this->reader->calculatePostPosition($permalinkId, $this['vp']->getPaginator())) {
				list($questionId, $page, $anchor) = $position;
				$this->redirect('Question:' . $anchor, ['questionId' => $questionId, 'vp-page' => $page]);
			}

			$this->error();
		}

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
		/** @var QuestionPresenter|VisualPaginator[] $this */
		$this->template->answers = $this->reader->readAnswers($this->question)
			->setFetchJoinCollection(FALSE)
			->applyPaginator($this['vp']->getPaginator());

		if ($this->postId && !$this->isSignalReceiver($this)) {
			$this->redirect('this', ['postId' => NULL]);
		}
	}



	protected function createComponentVp()
	{
		return new VisualPaginator(50);
	}



	protected function createComponentPost(IPostControlFactory $factory)
	{
		$posts = $this->em->getDao(Post::class);

		return new Multiplier(function ($postId) use ($factory, $posts) {
			return $factory->create()->setPost($posts->find($postId));
		});
	}



	/**
	 * @return BaseForm
	 */
	protected function createComponentAnswerForm()
	{
		$form = new BaseForm();

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
			$this->notAllowed($e->getMessage());
		}
	}



	public function renderEdit($postId)
	{
		$this->template->editingPost = $this->editingPost;
	}



	/**
	 * @secured
	 */
	public function handleTogglePinThread()
	{
		try {
			$this->writer->togglePinned($this->question);
			$this->em->flush();

		} catch (ModificationsNotAllowedException $e) {
			$this->notAllowed($e->getMessage());
		}

		$this->redirect('this');
	}



	/**
	 * @secured
	 */
	public function handleToggleLockThread()
	{
		try {
			$this->writer->toggleLocked($this->question);
			$this->em->flush();

		} catch (ModificationsNotAllowedException $e) {
			$this->notAllowed($e->getMessage());
		}

		$this->redirect('this');
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
