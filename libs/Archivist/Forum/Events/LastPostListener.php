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
use Archivist\Forum\Category;
use Archivist\Forum\Post;
use Archivist\Forum\Question;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\NoResultException;
use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class LastPostListener extends Nette\Object implements Kdyby\Events\Subscriber
{

	/**
	 * @var \Kdyby\Doctrine\EntityManager
	 */
	private $em;



	public function __construct(Kdyby\Doctrine\EntityManager $em)
	{
		$this->em = $em;
	}



	public function getSubscribedEvents()
	{
		return [Events::onFlush];
	}



	public function onFlush(OnFlushEventArgs $args)
	{
		$UoW = $this->em->getUnitOfWork();

		foreach ($UoW->getScheduledEntityInsertions() as $entity) {
			if ($entity instanceof Post) {
				$this->updateLastPost($entity);
				$this->updateLastQuestion($entity);
			}
		}

		foreach ($UoW->getScheduledEntityUpdates() as $entity) {
			if ($entity instanceof Post) {
				$this->updateLastPost($entity);
				$this->updateLastQuestion($entity);
			}
		}

		foreach ($UoW->getScheduledEntityDeletions() as $entity) {
			if ($entity instanceof Post) {
				$this->updateLastPost($entity);
				$this->updateLastQuestion($entity);
			}
		}
	}



	/**
	 * @param Post|Answer|Question $post
	 */
	public function updateLastPost(Post $post)
	{
		if (!$post->isAnswer()) {
			if (!$post->lastPost) {
				$post->setLastPost($this->findLastPost($post));
				$this->recomputeQuestionChangeSet($post);
			}

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

		$this->recomputeQuestionChangeSet($question);
	}



	private function recomputeQuestionChangeSet(Question $question)
	{
		$UoW = $this->em->getUnitOfWork();
		$class = $this->em->getClassMetadata(Question::class);

		if (!$UoW->isScheduledForDelete($question) && !$UoW->isScheduledForUpdate($question) && !$UoW->isScheduledForInsert($question)) {
			$UoW->computeChangeSet($class, $question);

		} else {
			$UoW->recomputeSingleEntityChangeSet($class, $question);
		}
	}



	/**
	 * @param Question $question
	 * @param Post $except
	 * @return Answer
	 */
	private function findLastPost(Question $question, Post $except = NULL)
	{
		$answers = $this->em->getDao(Answer::class);

		$lastPostQb = $answers->createQueryBuilder('a')
			->andWhere('a.question = :question')->setParameter('question', $question->getId())
			->andWhere('a.spam = FALSE AND a.deleted = FALSE')
			->addOrderBy('a.createdAt', 'DESC')
			->setMaxResults(1);

		if ($except && $except->getId() !== NULL) { // persisted
			$lastPostQb->andWhere('a.id != :post')->setParameter('post', $except->getId());
		}

		try {
			return $lastPostQb->getQuery()->getSingleResult();

		} catch (NoResultException $e) {
			return NULL;
		}
	}



	/**
	 * @param Post|Question|Answer $post
	 */
	public function updateLastQuestion(Post $post)
	{
		$category = $post->category;

		if (!$post->isQuestion()) {
			if (!$category->lastQuestion) {
				$category->setLastQuestion($this->findLastQuestion($category));
				$this->recomputeCategoryChangeSet($category);
			}

			return;
		}

		if ($post->isDeleted() || $post->isSpam()) {
			if ($category->lastQuestion && $category->lastQuestion !== $post) {
				return;
			}

			$category->setLastQuestion($this->findLastQuestion($category, $post));

		} else {
			if (!$category->lastQuestion || $post->getCreatedAt() > $category->lastQuestion->getCreatedAt()) {
				$category->setLastQuestion($post);
			}

			$lastQuestion = $this->findLastQuestion($category, $post);
			if ($lastQuestion && $lastQuestion->getCreatedAt() > $category->lastQuestion->getCreatedAt()) {
				$category->setLastQuestion($lastQuestion);
			}
		}

		$this->recomputeCategoryChangeSet($category);
	}



	private function recomputeCategoryChangeSet(Category $category)
	{
		$UoW = $this->em->getUnitOfWork();
		$class = $this->em->getClassMetadata(Category::class);

		if (!$UoW->isScheduledForDelete($category) && !$UoW->isScheduledForUpdate($category) && !$UoW->isScheduledForInsert($category)) {
			$UoW->computeChangeSet($class, $category);

		} else {
			$UoW->recomputeSingleEntityChangeSet($class, $category);
		}
	}



	/**
	 * @param Category $category
	 * @param Post $except
	 * @return Question
	 */
	private function findLastQuestion(Category $category, Post $except = NULL)
	{
		$answers = $this->em->getDao(Question::class);

		$lastPostQb = $answers->createQueryBuilder('q')
			->andWhere('q.category = :category')->setParameter('category', $category->getId())
			->andWhere('q.deleted = FALSE AND q.spam = FALSE')
			->addOrderBy('q.createdAt', 'DESC')
			->setMaxResults(1);

		if ($except && $except->getId() !== NULL) {
			$lastPostQb->andWhere('q.id != :post')->setParameter('post', $except->getId());
		}

		try {
			return $lastPostQb->getQuery()->getSingleResult();

		} catch (NoResultException $e) {
			return NULL;
		}
	}

}
