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

		$question = $post->getQuestion();

		if ($post->isDeleted() || $post->isSpam()) {
			if (!$question->lastPost || $question->lastPost === $post) {
				$question->setLastPost($this->findLastPost($question, $post));
			}

			if ($question->solution === $post) {
				$question->setSolution(NULL);
			}

		} else {
			if (!$question->lastPost || $post->getCreatedAt() > $question->lastPost->getCreatedAt()) {
				$question->setLastPost($post);
			}

			$lastPost = $this->findLastPost($question, $post);
			if ($lastPost && $lastPost->getCreatedAt() > $question->lastPost->getCreatedAt()) {
				$question->setLastPost($lastPost);
			}
		}

		$UoW = $this->em->getUnitOfWork();
		$UoW->computeChangeSet($this->em->getClassMetadata(Question::class), $question);
	}



	/**
	 * @param Question $question
	 * @param Post $except
	 * @return Answer
	 */
	private function findLastPost(Question $question, Post $except)
	{
		$answers = $this->em->getDao(Answer::class);

		$lastPostQb = $answers->createQueryBuilder('a')
			->andWhere('a.question = :question')->setParameter('question', $question->getId())
			->andWhere('a.spam = FALSE AND a.deleted = FALSE')
			->addOrderBy('a.createdAt', 'DESC')
			->setMaxResults(1);

		if ($except->getId() !== NULL) { // persisted
			$lastPostQb->andWhere('a.id != :post')->setParameter('post', $except->getId());
		}

		try {
			return $lastPostQb->getQuery()->getSingleResult();

		} catch (NoResultException $e) {
			return NULL;
		}
	}

}
