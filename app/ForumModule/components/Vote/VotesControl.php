<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Archivist\ForumModule\Vote;

use Archivist\Forum\CannotVoteOnOwnPostException;
use Archivist\Forum\Post;
use Archivist\Forum\Voter;
use Archivist\InsufficientPermissionsException;
use Archivist\Security\UserContext;
use Archivist\UI\BaseControl;
use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class VotesControl extends BaseControl
{

	/**
	 * @var Voter
	 */
	private $voter;

	/**
	 * @var \Archivist\Security\UserContext
	 */
	private $user;

	/**
	 * @var Post
	 */
	private $post;



	public function __construct(Voter $voter, UserContext $user)
	{
		$this->voter = $voter;
		$this->user = $user;
	}



	/**
	 * @param Post $post
	 * @return VotesControl
	 */
	public function setPost(Post $post)
	{
		$this->post = $post;
		return $this;
	}



	/**
	 * @secured
	 */
	public function handleVoteUp()
	{
		if (!$this->post) {
			$this->getPresenter()->error();
		}

		try {
			// update by newly loaded from database
			$this->post = $this->voter->voteUp($this->post);

		} catch (CannotVoteOnOwnPostException $e) {
			$this->getPresenter()->error();

		} catch (InsufficientPermissionsException $e) {
			$this->getPresenter()->error();
		}

		$this->redrawControl();
		if (!$this->presenter->isAjax()) {
			$this->redirect('this');
		}
	}



	/**
	 * @secured
	 */
	public function handleVoteDown()
	{
		if (!$this->post) {
			$this->getPresenter()->error();
		}

		try {
			// update by newly loaded from database
			$this->post = $this->voter->voteDown($this->post);

		} catch (CannotVoteOnOwnPostException $e) {
			$this->getPresenter()->error();

		} catch (InsufficientPermissionsException $e) {
			$this->getPresenter()->error();
		}

		$this->redrawControl();
		if (!$this->presenter->isAjax()) {
			$this->redirect('this');
		}
	}



	public function render()
	{
		if (!$this->post) {
			return; // render nothing
		}

		$this->template->post = $this->post;
		$this->template->vote = $this->user->isLoggedIn() ? $this->post->getVoteOfAuthor($this->user->getIdentity()) : NULL;

		$this->template
			->setFile(__DIR__ . '/default.latte')
			->render();
	}

}



interface IVotesControlFactory
{

	/** @return VotesControl */
	function create();
}
