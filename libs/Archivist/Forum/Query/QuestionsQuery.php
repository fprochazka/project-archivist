<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Archivist\Forum\Query;

use Archivist\Forum\Category;
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
	 * @var \Archivist\Forum\Category
	 */
	private $category;

	/**
	 * @var array|\Closure[]
	 */
	private $filter = [];

	/**
	 * @var array|\Closure[]
	 */
	private $select = [];



	public function __construct(Category $category = NULL)
	{
		$this->category = $category;
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
			$qb->addSelect('COUNT(a.id) as answers_count')
				->leftJoin('q.answers', 'a')
				->groupBy('q.id');
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
		$qb = $repository->createQueryBuilder('q')
			->andWhere('q.deleted = FALSE AND q.spam = FALSE')
			->innerJoin('q.author', 'i')
			->innerJoin('i.user', 'u');

		if ($this->category !== NULL) {
			$qb->andWhere('q.category = :category')->setParameter('category', $this->category->getId());
		}

		foreach ($this->filter as $modifier) {
			$modifier($qb);
		}

		return $qb;
	}

}
