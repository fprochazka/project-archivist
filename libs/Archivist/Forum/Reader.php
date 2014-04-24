<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Archivist\Forum;

use Archivist\Security\Role;
use Archivist\Security\UserContext;
use Kdyby;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Doctrine\ResultSet;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class Reader extends Nette\Object
{

	/**
	 * @var EntityManager
	 */
	private $em;

	/**
	 * @var \Archivist\Security\UserContext
	 */
	private $user;

	/**
	 * @var \Kdyby\Doctrine\EntityDao
	 */
	private $categories;

	/**
	 * @var \Kdyby\Doctrine\EntityDao
	 */
	private $posts;

	/**
	 * @var \Kdyby\Doctrine\EntityDao
	 */
	private $questions;

	/**
	 * @var \Kdyby\Doctrine\EntityDao
	 */
	private $answers;



	public function __construct(EntityManager $em, UserContext $user)
	{
		$this->em = $em;
		$this->categories = $em->getDao(Category::class);
		$this->posts = $em->getDao(Post::class);
		$this->questions = $em->getDao(Question::class);
		$this->answers = $em->getDao(Answer::class);
		$this->user = $user;
	}



	/**
	 * @param int $categoryId
	 * @return Category
	 */
	public function readCategory($categoryId)
	{
		if (!$categoryId || !($category = $this->categories->find($categoryId))) {
			return NULL;
		}

		return $category;
	}



	/**
	 * @param Category $category
	 * @return \Kdyby\Doctrine\ResultSet
	 */
	public function readTopics(Category $category = NULL)
	{
		$qb = $this->questions->createQueryBuilder('q')
			->innerJoin('q.author', 'i')->addSelect('i')
			->innerJoin('i.user', 'u')->addSelect('u')
			->innerJoin('q.category', 'c')->addSelect('c')
			->andWhere('q.deleted = FALSE AND q.spam = FALSE')
			->addSelect('FIELD(IsNull(q.solution), TRUE, FALSE) as HIDDEN hasSolution')
			->addOrderBy('q.createdAt', 'DESC');

		if ($category !== NULL) {
			$qb->andWhere('q.category = :category')->setParameter('category', $category->getId());
		}

		return new ResultSet($qb->getQuery());
	}



	/**
	 * @param int $questionId
	 * @throws PostIsNotReadableException
	 * @return Question
	 */
	public function readQuestion($questionId)
	{
		/** @var Question $question */
		if (!$questionId || !($question = $this->questions->find($questionId))) {
			return NULL;
		}

		$this->assertReadable($question);

		return $question;
	}



	/**
	 * @param int $postId
	 * @param Question|NULL $question
	 * @throws PostIsNotReadableException
	 * @return Answer
	 */
	public function readAnswer($postId, Question $question = NULL)
	{
		/** @var Answer $post */
		if (!$postId || !($post = $this->answers->find($postId))) {
			return NULL;
		}

		if ($question && $post->getQuestion() !== $question) {
			return NULL;
		}

		$this->assertReadable($post);

		return $post;
	}



	/**
	 * @param Question $question
	 * @return ResultSet
	 */
	public function readAnswers(Question $question)
	{
		$qb = $this->answers->createQueryBuilder('a')
			->innerJoin('a.author', 'i')->addSelect('i')
			->innerJoin('i.user', 'u')->addSelect('u')
			->innerJoin('a.category', 'c')->addSelect('c')
			->andWhere('a.question = :question')->setParameter('question', $question->getId())
			->andWhere('a.deleted = FALSE AND a.spam = FALSE')
			->orderBy('a.createdAt', 'ASC');

		return new ResultSet($qb->getQuery());
	}



	/**
	 * @param int $postId
	 * @throws PostIsNotReadableException
	 * @throws ModificationsNotAllowedException
	 * @return Answer|Question
	 */
	public function readForModification($postId)
	{
		/** @var Answer|Question $post */
		if (!$postId || !($post = $this->posts->find($postId))) {
			return NULL;
		}

		$this->assertReadable($post);
		$this->assertAllowedToModify($post);

		return $post;
	}



	/**
	 * @param Post $post
	 * @throws PostIsNotReadableException
	 */
	protected function assertReadable(Post $post)
	{
		if ($post->deleted) {
			throw new PostIsNotReadableException("$post was deleted");

		} elseif ($post->spam) {
			throw new PostIsNotReadableException("$post is spam");
		}
	}



	/**
	 * @param Post $post
	 * @throws ModificationsNotAllowedException
	 */
	protected function assertAllowedToModify(Post $post)
	{
		if (!($post->isAuthor($this->user->getIdentity()) || $this->user->isInRole(Role::MODERATOR))) {
			throw new ModificationsNotAllowedException;
		}
	}

}
