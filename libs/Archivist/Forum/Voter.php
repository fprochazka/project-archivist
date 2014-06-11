<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Archivist\Forum;

use Archivist\InsufficientPermissionsException;
use Archivist\Security\UserContext;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Join;
use Kdyby;
use Kdyby\Doctrine\Connection;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class Voter extends Nette\Object
{

	/**
	 * @var \Kdyby\Doctrine\EntityManager
	 */
	private $em;

	/**
	 * @var Connection
	 */
	private $db;

	/**
	 * @var \Archivist\Security\UserContext
	 */
	private $user;

	/**
	 * @var \Doctrine\ORM\Mapping\ClassMetadata
	 */
	private $votesClass;

	/**
	 * @var \Doctrine\ORM\Mapping\ClassMetadata
	 */
	private $postsClass;

	/**
	 * @var \Kdyby\Doctrine\EntityDao
	 */
	private $postsDao;



	public function __construct(Kdyby\Doctrine\EntityManager $em, UserContext $user)
	{
		$this->em = $em;
		$this->db = $this->em->getConnection();
		$this->votesClass = $this->em->getClassMetadata(Vote::class);
		$this->postsClass = $this->em->getClassMetadata(Post::class);
		$this->postsDao = $this->em->getDao(Post::class);
		$this->user = $user;
	}



	/**
	 * @param Post $post
	 * @return Post
	 */
	public function voteUp(Post $post)
	{
		if (!$this->user->isLoggedIn()) {
			throw new InsufficientPermissionsException;
		}

		if ($post->isAuthor($this->user->getIdentity())) {
			throw new CannotVoteOnOwnPostException();
		}

		return $this->updateVotes(+1, $post);
	}



	/**
	 * @param Post $post
	 * @return Post
	 */
	public function voteDown(Post $post)
	{
		if (!$this->user->isLoggedIn()) {
			throw new InsufficientPermissionsException;
		}

		if ($post->isAuthor($this->user->getIdentity())) {
			throw new CannotVoteOnOwnPostException();
		}

		return $this->updateVotes(-1, $post);
	}



	/**
	 * @param int $value
	 * @param Post $post
	 * @return Post
	 */
	protected function updateVotes($value, Post $post)
	{
		$this->db->transactional(function (Connection $db) use ($value, $post) {
			$votesTbl = $this->votesClass->getTableName();
			$postsTbl = $this->postsClass->getTableName();

			$params = [$post->getId(), $this->user->getUserEntity()->getId()];

			// create entry with 0 rating for given user if it doesn't yet exists
			$sql = sprintf('INSERT INTO "%s" (post_id, user_id, points) SELECT ?, ?, 0 WHERE NOT EXISTS (SELECT 1 FROM "%s" WHERE post_id = ? AND user_id = ?)', $votesTbl, $votesTbl);
			$db->prepare($sql)->execute(array_merge($params, $params));

			// update the entry to required state
			$sql = sprintf('UPDATE "%s" SET points = GREATEST(LEAST(points %s, 1), -1) WHERE post_id = ? AND user_id = ?', $votesTbl, $value > 0 ? "+ 1" : "- 1");
			$db->prepare($sql)->execute($params);

			// update the sum of votes for relevant posts entry
			$sql = sprintf('UPDATE "%s" SET votes = (SELECT SUM(points) FROM "%s" WHERE post_id = ?) WHERE id = ?', $postsTbl, $votesTbl);
			$db->prepare($sql)->execute([$post->getId(), $post->getId()]);
		});

		$this->em->clear();

		$refreshPostQuery = $this->postsDao->createQueryBuilder('p')
			->addSelect('v')
			->leftJoin('p.author', 'a')->addSelect('a')
			->leftJoin('a.user', 'u')->addSelect('u')
			->leftJoin('p.votes', 'v', Join::WITH, 'v.user = :user')->setParameter('user', $this->user->getUserEntity()->getId())
			->andWhere('p.id = :post')->setParameter('post', $post->getId())
			->getQuery()->setMaxResults(1);

		try {
			$post = $refreshPostQuery->getSingleResult();

		} catch (NoResultException $e) {
			// fuck !
		}

		return $post;
	}

}
