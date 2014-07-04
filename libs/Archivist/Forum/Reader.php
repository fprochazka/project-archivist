<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Archivist\Forum;

use Archivist\Forum\Query\QuestionsQuery;
use Archivist\ForumModule\TopicsPresenter;
use Archivist\Security\Role;
use Archivist\Security\UserContext;
use Doctrine\DBAL\SQLParserUtils;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\ResultSetMapping;
use Kdyby;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Doctrine\NativeQueryWrapper;
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
	 * @param Kdyby\Doctrine\QueryObject $query
	 * @return ResultSet
	 */
	public function fetch(Kdyby\Doctrine\QueryObject $query)
	{
		if ($query instanceof QuestionsQuery) {
			return $this->questions->fetch($query);
		}

		return $this->posts->fetch($query);
	}



	/**
	 * @param int $categoryId
	 * @return Category
	 */
	public function readCategory($categoryId)
	{
		if (!$categoryId || !is_numeric($categoryId) || !($category = $this->categories->find($categoryId))) {
			return NULL;
		}

		return $category;
	}



	/**
	 * @param int $questionId
	 * @throws PostIsNotReadableException
	 * @return Question
	 */
	public function readQuestion($questionId)
	{
		/** @var Question $question */
		if (!$questionId || !is_numeric($questionId)) {
			return NULL;
		}

		$qb = $this->questions->createQueryBuilder('q')
			->leftJoin('q.author', 'a')->addSelect('a')
			->leftJoin('a.user', 'u')->addSelect('u')
			->andWhere('q.id = :question')->setParameter('question', $questionId)
			->setMaxResults(1);

		$qb
			->leftJoin('q.votes', 'v', Join::WITH, 'v.user = :user')->addSelect('v')
				->setParameter('user', $this->user->loggedIn ? $this->user->getUserEntity()->getId() : 0);

		try {
			$question = $qb->getQuery()->getSingleResult();

		} catch (NoResultException $e) {
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
		if (!$postId || !is_numeric($postId) || !($post = $this->answers->find($postId))) {
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
		$qb = $this->buildAnswersDql($question)
			->leftJoin('a.votes', 'v', Join::WITH, 'v.user = :user')->addSelect('v')
				->setParameter('user', $this->user->loggedIn ? $this->user->getUserEntity()->getId() : 0);

		return new ResultSet($qb->getQuery());
	}



	private function buildAnswersDql(Question $question)
	{
		$qb = $this->answers->createQueryBuilder('a')
			->innerJoin('a.author', 'i')->addSelect('i')
			->innerJoin('i.user', 'u')->addSelect('u')
			->innerJoin('a.category', 'c')->addSelect('c')
			->andWhere('a.question = :question')->setParameter('question', $question->getId())
			->andWhere('a.deleted = FALSE AND a.spam = FALSE')
			->andWhere('a.parentPost IS NULL');

		$qb
			->leftJoin('a.question', 'q')
			->addSelect('FIELD(q.solution, a) as HIDDEN hasSolution')
//			->addOrderBy('hasSolution', 'ASC')
		;

		return $qb
//			->addOrderBy('a.votesSum', 'DESC')
			->addOrderBy('a.createdAt', 'ASC')
			;
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
		if (!$postId || !is_numeric($postId) || !($post = $this->posts->find($postId))) {
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
		if ($post->isDeleted()) {
			throw new PostIsNotReadableException("$post was deleted");

		} elseif ($post->isSpam()) {
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



    /**
     * Returns [$questionId, $page, $anchor]
     */
    public function calculatePostPosition($permalinkId, Nette\Utils\Paginator $paginator)
	{
		/** @var Question|Answer $post */
		if (!is_numeric($permalinkId) || !$post = $this->posts->find($permalinkId)) {
			return NULL;
		}

        $parent = $post->getParentPost() ?: $post;

		if ($parent instanceof Question) {
			return [$parent->getId(), 1, '#post' . $post->getId()];
		}

		$answersQb = $this->buildAnswersDql($post->getQuestion())
			->select('a.id, a.createdAt, FIELD(q.solution, a) as HIDDEN hasSolution')
			->addSelect('ROW_NUMBER(a.createdAt ASC) AS position');

		$rsm = new ResultSetMapping();
		$rsm->addScalarResult('id0', 'id', 'integer');
		$rsm->addScalarResult('created_at1', 'createdAt', 'datetime');
		$rsm->addScalarResult('sclr3', 'position', 'integer');

		$answersSql = $answersQb->getQuery()->getSQL();
		$positionQuery = $this->em->createNativeQuery("SELECT t.* FROM ($answersSql) t WHERE t.id0 = ?", $rsm)
			->setParameters([$answersQb->getParameter('question')->getValue(), $parent->getId()]);

		$position = (new NativeQueryWrapper($positionQuery))
			->setMaxResults(1)->getScalarResult();

		return $position ? array(
			$post->getQuestion()->getId(),
			(int) ceil($position[0]['position'] / $paginator->itemsPerPage),
			'#post' . $post->getId(),
		) : NULL;
	}

}
