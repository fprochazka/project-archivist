<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Archivist\Forum\Events;

use Archivist\Forum\Category;
use Archivist\Forum\Post;
use Archivist\Forum\Question;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\NoResultException;
use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class NewestQuestionListener extends Nette\Object
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
	 * @param Post|Question $post
	 * @param PreFlushEventArgs $args
	 */
	public function preFlush(Post $post, PreFlushEventArgs $args)
	{
		if (!$post->isQuestion()) {
			return;
		}

		$category = $post->category;
		$lastQuestion = $category->lastQuestion;

		if ($post->isDeleted() || $post->isSpam()) {
			if ($lastQuestion !== $post) {
				return;
			}

			$category->setLastQuestion($this->findLastQuestion($category, $post));

		} elseif (!$lastQuestion || $post->getCreatedAt() > $lastQuestion->getCreatedAt()) {
			$category->setLastQuestion($post);
		}

		$UoW = $this->em->getUnitOfWork();
		$UoW->computeChangeSet($this->em->getClassMetadata(Category::class), $category);
	}



	/**
	 * @param Category $category
	 * @param Post $except
	 * @return Question
	 */
	private function findLastQuestion(Category $category, Post $except)
	{
		$answers = $this->em->getDao(Question::class);

		$lastPostQb = $answers->createQueryBuilder('q')
			->innerJoin('q.category', 'qc')
			->andWhere('qc.id = :category')->setParameter('category', $category->getId())
			->andWhere('q.deleted = FALSE AND q.spam = FALSE')
			->andWhere('q.id != :post')->setParameter('post', $except->getId())
			->addOrderBy('q.createdAt', 'DESC')
			->setMaxResults(1);

		try {
			return $lastPostQb->getQuery()->getSingleResult();

		} catch (NoResultException $e) {
			return NULL;
		}
	}

}