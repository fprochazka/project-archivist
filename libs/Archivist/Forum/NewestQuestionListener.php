<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Archivist\Forum;

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



	public function preFlush(Post $post, PreFlushEventArgs $args)
	{
		if (!$post->isQuestion()) {
			return;
		}

		$questions = $this->em->getDao(Question::class);

		$category = $post->category;
		$lastQuestion = $category->lastQuestion;

		if ($post->deleted || $post->spam) {
			if ($category->lastQuestion !== $post) {
				return;
			}

			$lastQuestionQb = $questions->createQueryBuilder('q')
				->innerJoin('q.category', 'qc')
				->andWhere('qc.id = :category')->setParameter('category', $category->getId())
				->andWhere('q.deleted = FALSE AND q.spam = FALSE')
				->andWhere('q.id != :post')->setParameter('post', $post->getId())
				->addOrderBy('q.createdAt', 'DESC')
				->setMaxResults(1);

			try {
				$category->lastQuestion = $lastQuestionQb->getQuery()->getSingleResult();

			} catch (NoResultException $e) {
				$category->lastQuestion = NULL;
			}

			return;
		}

		if (!$lastQuestion || $post->getCreatedAt() > $lastQuestion->getCreatedAt()) {
			$category->lastQuestion = $post;
		}
	}

}
