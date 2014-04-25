<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Archivist\Forum\Events;

use Archivist\Forum\Answer;
use Archivist\Forum\Post;
use Archivist\Forum\Question;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\NoResultException;
use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class LastPostListener extends Nette\Object
{

	/**
	 * @var \Kdyby\Doctrine\EntityManager
	 */
	private $em;



	public function __construct(Kdyby\Doctrine\EntityManager $em)
	{
		$this->em = $em;
	}



	/**
	 * @param Post|Answer $post
	 * @param PreFlushEventArgs $args
	 */
	public function preFlush(Post $post, PreFlushEventArgs $args)
	{
		if (!$post->isAnswer()) {
			return;
		}

		$answers = $this->em->getDao(Answer::class);
		$UoW = $this->em->getUnitOfWork();

		$question = $post->getQuestion();
		$lastPost = $question->getLastPost();

		if ($post->isDeleted() || $post->isSpam() || !$lastPost) {
			if ($lastPost && $lastPost !== $post) {
				return;
			}

			$lastPostQb = $answers->createQueryBuilder('a')
				->innerJoin('a.question', 'q')
				->andWhere('q.id = :question')->setParameter('question', $question->getId())
				->andWhere('a.deleted = FALSE AND a.spam = FALSE')
				->andWhere('a.id != :post')->setParameter('post', $post->getId())
				->addOrderBy('a.createdAt', 'DESC')
				->setMaxResults(1);

			try {
				$question->setLastPost($lastPostQb->getQuery()->getSingleResult());

			} catch (NoResultException $e) {
				$question->setLastPost(NULL);
			}

		} elseif (!$lastPost || $post->getCreatedAt() > $lastPost->getCreatedAt()) {
			$question->setLastPost($post);
		}

		$UoW->computeChangeSet($this->em->getClassMetadata(Question::class), $question);
	}

}
