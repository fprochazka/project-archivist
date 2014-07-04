<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Archivist\ForumModule\Comment;

use Archivist\Forum\Answer;
use Archivist\Forum\Post;
use Archivist\Forum\PostIsNotReadableException;
use Archivist\Forum\Question;
use Archivist\Forum\ThreadLockedException;
use Archivist\Forum\Writer;
use Archivist\Security\UserContext;
use Archivist\UI\BaseControl;
use Archivist\UI\BaseForm;
use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class ReplyControl extends BaseControl
{

	/**
	 * @var \Archivist\Security\UserContext
	 */
	private $user;

	/**
	 * @var \Archivist\Forum\Writer
	 */
	private $writer;

	/**
	 * @var Answer|Question
	 */
	private $post;

	/**
	 * @var bool
	 */
	private $visible = FALSE;



	public function __construct(Writer $writer, UserContext $user)
	{
		$this->user = $user;
		$this->writer = $writer;
	}



	/**
	 * @param Post $post
	 * @return ReplyControl
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



	public function handleShow()
	{
		$this->visible = TRUE;
		$this->redrawControl();
	}



	protected function createComponentForm()
	{
		$form = new BaseForm();
		$form->setupBootstrap3Rendering();

		$form->addTextArea('content', 'Answer')
			->setAttribute('rows', 2)
			->setRequired();

		$form->addSubmit("send", "Post answer")
			->onClick[] = function () { $this->visible = TRUE; };

		$form->onSuccess[] = function (BaseForm $form, $values) {
			if (!$this->post) {
				$this->error();
			}

			if (!$this->user->isLoggedIn()) {
				$form->addError("Please login first before posting");
				return;
			}

			try {
				$this->writer->answerQuestion(new Answer($values->content, $this->post), $this->getQuestion());

			} catch (PostIsNotReadableException $e) {
				$this->error();

			} catch (ThreadLockedException $e) {
				$this->error();
			}

			$this->visible = FALSE;
			$this->redrawControl();
			$this->getParent()->redrawControl(); // redraw post

			if (!$this->isAjax()) {
				$this->redirect('this');
			}
		};

		return $form;
	}



	public function render()
	{
		if (!$this->post) {
			return;
		}

		$this->template->visible = $this->visible;
		$this->template
			->setFile(__DIR__ . '/default.latte')
			->render();
	}

}



interface IReplyControlFactory
{

	/** @return ReplyControl */
	function create();
}
