<?php

namespace Archivist;

use Archivist\Forum\Answer;
use Archivist\Forum\Post;
use Archivist\Forum\Question;
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



	protected function startup()
	{
		parent::startup();

		if (!$this->question = $this->em->getDao(Question::class)->find($this->questionId)) {
			$this->error("Topic not found");

		} elseif ($this->question->deleted || $this->question->spam) {
			$this->error("Topic was deleted");
		}
	}



	protected function beforeRender()
	{
		parent::beforeRender();
		$this->template->question = $this->question;
	}



	public function renderDefault($questionId)
	{
		$answers = $this->em->getDao(Answer::class);
		$qb = $answers->createQueryBuilder('a')
			->innerJoin('a.author', 'i')->addSelect('i')
			->innerJoin('i.user', 'u')->addSelect('u')
			->innerJoin('a.category', 'c')->addSelect('c')
			->andWhere('a.question = :question')->setParameter('question', $this->question->getId())
			->andWhere('a.deleted = FALSE AND a.spam = FALSE')
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



	public function actionEdit($postId)
	{
		/** @var Answer|Question $post */
		if (!$post = $this->em->getDao(Post::class)->findOneBy(['id' => $postId])) {
			$this->error("Post not found");

		} elseif ($post->deleted || $post->spam) {
			$this->error("Post was deleted");

		} elseif ($post->isAnswer() && $post->getQuestion() !== $this->question) {
			$this->error("Collision");

		} elseif (!($post->isAuthor($this->getUser()->getIdentity()) || $this->getUser()->isInRole(Role::MODERATOR))) {
			$this->notAllowed();
		}

		return $this->editingPost = $post;
	}



	public function renderEdit($postId)
	{
		$this->template->editingPost = $this->editingPost;
	}



	public function handleMarkResolved($postId)
	{
		/** @var Answer|Question $post */
		if (!$post = $this->em->getDao(Post::class)->findOneBy(['id' => $postId])) {
			$this->error("Post not found");

		} elseif ($post->deleted || $post->spam) {
			$this->error("Post was deleted");

		} elseif (!$post->isAnswer()) {
			$this->error("Not an answer");

		} elseif ($post->getQuestion() !== $this->question || !$this->question->isAuthor($this->getUser()->getIdentity())) {
			$this->notAllowed();
		}

		$this->question->solution = $post;
		$this->em->flush();

		$this->redirect('this', ['postId' => NULL]);
	}



	public function handleMarkNotResolved($postId)
	{
		/** @var Answer|Question $post */
		if (!$post = $this->em->getDao(Post::class)->findOneBy(['id' => $postId])) {
			$this->error("Post not found");

		} elseif ($post->deleted || $post->spam) {
			$this->error("Post was deleted");

		} elseif (!$post->isAnswer()) {
			$this->error("Not an answer");

		} elseif ($post->getQuestion() !== $this->question || !$this->question->isAuthor($this->getUser()->getIdentity())) {
			$this->notAllowed();
		}

		$this->question->solution = NULL;
		$this->em->flush();

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
