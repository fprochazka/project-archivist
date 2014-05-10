<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Archivist\Forum\Query;

use Archivist\Forum\Answer;
use Archivist\Forum\Category;
use Archivist\Forum\Question;
use Archivist\InvalidArgumentException;
use Archivist\Users\Identity;
use Archivist\Users\User;
use Doctrine\ORM\Query\Expr\Join;
use Kdyby;
use Kdyby\Doctrine\QueryBuilder;
use Kdyby\Persistence\Queryable;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class QuestionsQuery extends Kdyby\Doctrine\QueryObject
{

	/**
	 * @var array|\Closure[]
	 */
	private $filter = [];

	/**
	 * @var array|\Closure[]
	 */
	private $select = [];



	public function inCategory(Category $category = NULL)
	{
		$this->filter[] = function (QueryBuilder $qb) use ($category) {
			$qb->andWhere('q.category = :category')->setParameter('category', $category->getId());
		};
		return $this;
	}



	public function byUser($user)
	{
		if ($user instanceof Identity) {
			$user = $user->getUser();

		} elseif (!$user instanceof User) {
			throw new InvalidArgumentException;
		}

		$this->filter[] = function (QueryBuilder $qb) use ($user) {
			$qb->andWhere('u.id = :user')->setParameter('user', $user->getId());
		};
		return $this;
	}



	public function withLastPost()
	{
		$this->select[] = function (QueryBuilder $qb) {
			$qb->addSelect('partial lp.{id, createdAt}, partial lpa.{id}, partial lpau.{id, name}')
				->leftJoin('q.lastPost', 'lp', Join::WITH, 'lp.spam = FALSE AND lp.deleted = FALSE')
				->leftJoin('lp.author', 'lpa')
				->leftJoin('lpa.user', 'lpau');
		};
		return $this;
	}



	public function withCategory()
	{
		$this->select[] = function (QueryBuilder $qb) {
			$qb->addSelect('c, pc')
				->innerJoin('q.category', 'c')
				->innerJoin('c.parent', 'pc');
		};
		return $this;
	}



	public function withAnswersCount()
	{
		$this->select[] = function (QueryBuilder $qb) {
			$subCount = $qb->getEntityManager()->createQueryBuilder()
				->select('COUNT(a.id)')->from(Answer::class, 'a')
				->andWhere('a.spam = FALSE AND a.deleted = FALSE')
				->andWhere('a.question = q');

			$qb->addSelect("($subCount) AS answers_count");
		};
		return $this;
	}



	public function sortByPinned($order = 'ASC')
	{
		$this->select[] = function (QueryBuilder $qb) use ($order) {
			$qb->addSelect('FIELD(q.pinned, TRUE, FALSE) as HIDDEN isPinned');
			$qb->addOrderBy('isPinned', $order);
		};
		return $this;
	}



	public function sortByHasSolution($order = 'ASC')
	{
		$this->select[] = function (QueryBuilder $qb) use ($order) {
			$qb->addSelect('FIELD(IsNull(q.solution), TRUE, FALSE) as HIDDEN hasSolution');
			$qb->addOrderBy('hasSolution', $order);
		};
		return $this;
	}



	/**
	 * @param \Kdyby\Persistence\Queryable $repository
	 * @return \Doctrine\ORM\Query|\Doctrine\ORM\QueryBuilder
	 */
	protected function doCreateQuery(Queryable $repository)
	{
		$qb = $this->createBasicDql($repository)
			->addSelect('partial i.{id}, partial u.{id, name}');

		foreach ($this->select as $modifier) {
			$modifier($qb);
		}

		return $qb->addOrderBy('q.createdAt', 'DESC');
	}



	protected function doCreateCountQuery(Queryable $repository)
	{
		return $this->createBasicDql($repository)->select('COUNT(q.id)');
	}



	private function createBasicDql(Queryable $repository)
	{
		$qb = $repository->createQueryBuilder()
			->select('q')->from(Question::class, 'q')
			->andWhere('q.spam = FALSE AND q.deleted = FALSE')
			->innerJoin('q.author', 'i')
			->innerJoin('i.user', 'u');

		foreach ($this->filter as $modifier) {
			$modifier($qb);
		}

		return $qb;
	}

}
