<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Archivist\ForumModule\OnePost;

use Archivist\Forum\Answer;
use Archivist\Forum\CannotBeSolvedByCommentException;
use Archivist\Forum\ModificationsNotAllowedException;
use Archivist\Forum\Post;
use Archivist\Forum\PostIsNotReadableException;
use Archivist\Forum\Question;
use Archivist\Forum\Reader;
use Archivist\Forum\ThreadLockedException;
use Archivist\Forum\Writer;
use Archivist\ForumModule\Comment\IReplyControlFactory;
use Archivist\ForumModule\Vote\IVotesControlFactory;
use Archivist\InsufficientPermissionsException;
use Archivist\UI\BaseControl;
use IPub\Gravatar\Gravatar;
use Kdyby;
use Kdyby\Doctrine\EntityManager;
use Nette;
use Nette\Application\UI\Multiplier;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class PostControl extends BaseControl
{

	/**
	 * @var \Archivist\Forum\Writer
	 */
	private $writer;

	/**
	 * @var \Archivist\Forum\Reader
	 */
	private $reader;

	/**
	 * @var EntityManager
	 */
	private $em;

	/**
	 * @var \IPub\Gravatar\Gravatar
	 */
	private $gravatar;

	/**
	 * @var Answer|Question
	 */
	private $post;



	public function __construct(Writer $writer, Reader $reader, Gravatar $gravatar, EntityManager $em)
	{
		parent::__construct();
		$this->writer = $writer;
		$this->reader = $reader;
		$this->gravatar = $gravatar;
		$this->em = $em;
	}



	/**
	 * @param Post $post
	 * @return PostControl
	 */
	public function setPost(Post $post)
	{
		$this->post = $post;
		return $this;
	}



	/**
	 * @return Question
	 */
	protected function getQuestion()
	{
		return $this->post->isQuestion() ? $this->post : $this->post->getQuestion();
	}



	/**
	 * @secured
	 */
	public function handleToggleResolved()
	{
		if (!$this->post) {
			$this->error();
		}

		try {
			if (!$answer = $this->reader->readAnswer($this->post->id, $this->getQuestion())) {
				$this->error("Post not found");
			}

			$this->writer->toggleResolvedBy($this->getQuestion(), $answer);
			$this->em->flush();

		} catch (InsufficientPermissionsException $e) {
			$this->error($e->getMessage());

		} catch (CannotBeSolvedByCommentException $e) {
			$this->error($e->getMessage());

		} catch (PostIsNotReadableException $e) {
			$this->error($e->getMessage());
		}

		$this->redirect('this');
	}



	/**
	 * @secured
	 */
	public function handleDelete($postId = 0)
	{
		$post = $this->modifyPost($postId, function (Post $post) {
			$this->writer->markAsDeleted($this->getQuestion(), $post);
		});

		if ($post->isQuestion()) {
			$this->flashMessage("Topic '" . $post->getTitle() . "' was deleted.", 'danger');
			$this->redirect('Topics:', ['categoryId' => $post->category->getId()]);
		}

		$this->flashMessage("Post was deleted.", 'danger');
		$this->redirect('this');
	}



	/**
	 * @secured
	 */
	public function handleMarkAsSpam($postId = 0)
	{
		$post = $this->modifyPost($postId, function (Post $post) {
			$this->writer->markAsSpam($this->getQuestion(), $post);
		});

		if ($post->isQuestion()) {
			$this->flashMessage("Topic '" . $post->getTitle() . "' was marked as spam.", 'danger');
			$this->redirect('Topics:', ['categoryId' => $post->category->getId()]);
		}

		$this->flashMessage("Post was marked as spam.", 'danger');
		$this->redirect('this');
	}



	protected function modifyPost($postId, $callback)
	{
		if (!$this->post) {
			$this->error();
		}

		$post = $this->post;
		if ($postId) {
			if (!is_numeric($postId)) {
				$this->error();
			}

			try {
				if (!($post = $this->reader->readForModification($postId)) || $post->getParentPost() !== $this->post) {
					$this->error();
				}

			} catch (PostIsNotReadableException $e) {
				$this->error();

			} catch (InsufficientPermissionsException $e) {
				$this->error();
			}
		}

		try {
			$callback($post);
			$this->em->flush();

		} catch (InsufficientPermissionsException $e) {
			$this->notAllowed($e->getMessage());
		}

		return $post;
	}



	public function render()
	{
		if (!$this->post) {
			return;
		}

		$this->injectGravatar($this->gravatar);

		$this->template->post = $this->post;
		$this->template
			->setFile(__DIR__ . '/default.latte')
			->render();
	}



	protected function createComponentVote(IVotesControlFactory $factory)
	{
		return new Multiplier(function () use ($factory) {
			$vote = $factory->create()->setPost($this->post);
			$vote->onAttached[] = function () {
				$this['vote-prepend']->redrawControl();
				$this['vote-footer']->redrawControl();
			};

			return $vote;
		});
	}



	protected function createComponentCommentForm(IReplyControlFactory $factory)
	{
		return $factory->create()->setPost($this->post);
	}

}



interface IPostControlFactory
{

	/** @return PostControl */
	function create();
}
