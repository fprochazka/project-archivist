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
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Join;
use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @method onVote(VotesControl $self)
 */
class VotesControl extends BaseControl
{

	/**
	 * @var array
	 */
	public $onVote = [];

	/**
	 * @var Voter
	 */
	private $voter;

	/**
	 * @var \Archivist\Security\UserContext
	 */
	private $user;

	/**
	 * @var \Kdyby\Doctrine\EntityDao
	 */
	private $postsDao;

	/**
	 * @var Post
	 */
	private $post;

	/**
	 * @var \Kdyby\Doctrine\EntityManager
	 */
	private $em;



	public function __construct(Voter $voter, UserContext $user, Kdyby\Doctrine\EntityManager $em)
	{
		$this->voter = $voter;
		$this->user = $user;
		$this->postsDao = $em->getDao(Post::class);
		$this->em = $em;

		$this->onVote[] = function () {
			if (!$this->post || !$this->user->isLoggedIn()) {
				$this->error();
			}

			$this->em->clear();

			$refreshPostQuery = $this->postsDao->createQueryBuilder('p')
				->addSelect('v')
				->leftJoin('p.author', 'a')->addSelect('a')
				->leftJoin('a.user', 'u')->addSelect('u')
				->leftJoin('p.votes', 'v', Join::WITH, 'v.user = :user')->setParameter('user', $this->user->getUserEntity()->getId())
				->andWhere('p.id = :post')->setParameter('post', $this->post->getId())
				->getQuery()->setMaxResults(1);

			try {
				$post = $refreshPostQuery->getSingleResult();

			} catch (NoResultException $e) {
				return; // fuck !
			}

			/** @var VotesControl $component */
			foreach ($this->getParent()->getComponents(FALSE, VotesControl::class) as $component) {
				$component->setPost($post);
			}
		};
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
			$this->error();
		}

		try {
			// update by newly loaded from database
			$this->post = $this->voter->voteUp($this->post);

		} catch (CannotVoteOnOwnPostException $e) {
			$this->error();

		} catch (InsufficientPermissionsException $e) {
			$this->error();
		}

		// $this->redrawControl(); // must be called in the callback
		$this->onVote($this);

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
			$this->error();
		}

		try {
			// update by newly loaded from database
			$this->post = $this->voter->voteDown($this->post);

		} catch (CannotVoteOnOwnPostException $e) {
			$this->error();

		} catch (InsufficientPermissionsException $e) {
			$this->error();
		}

		// $this->redrawControl(); // must be called in the callback
		$this->onVote($this);

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
