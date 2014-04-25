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
use Kdyby;
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
	 * @var array
	 */
	private $with = [
		'lastPost' => FALSE,
		'answersCount' => FALSE,
		'category' => FALSE,
	];

	/**
	 * @var array
	 */
	private $orderBy = [];



	public function __construct(Category $category = NULL)
	{
		$this->category = $category;
	}



	public function withLastPost()
	{
		$this->with['lastPost'] = TRUE;
		return $this;
	}



	public function withCategory()
	{
		$this->with['category'] = TRUE;
		return $this;
	}



	public function withAnswersCount()
	{
		$this->with['answersCount'] = TRUE;
		return $this;
	}



	public function sortByHasSolution($direction = 'ASC')
	{
		$this->orderBy[] = 'hasSolution ' . $direction;
		return $this;
	}



	/**
	 * @param \Kdyby\Persistence\Queryable $repository
	 * @return \Doctrine\ORM\Query|\Doctrine\ORM\QueryBuilder
	 */
	protected function doCreateQuery(Kdyby\Persistence\Queryable $repository)
	{
		$qb = $this->createBasicDql($repository)
			->addSelect('partial i.{id}, partial u.{id, name}')
			->addSelect('FIELD(IsNull(q.solution), TRUE, FALSE) as HIDDEN hasSolution');

		if ($this->with['category']) {
			$qb->addSelect('c, pc')
				->innerJoin('q.category', 'c')
				->innerJoin('c.parent', 'pc');
		}

		if ($this->with['lastPost']) {
			$qb->addSelect('partial lp.{id}, partial lpa.{id}, partial lpau.{id, name}')
				->leftJoin('q.lastPost', 'lp')
				->leftJoin('lp.author', 'lpa')
				->leftJoin('lpa.user', 'lpau');
		}

		if ($this->with['answersCount']) {
			$qb->addSelect('COUNT(a.id) as answers_count')
				->leftJoin('q.answers', 'a')
				->groupBy('q.id');
		}

		foreach ($this->orderBy as $by) {
			list($sort, $order) = explode(' ', $by);
			$qb->addOrderBy($sort, $order);
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

		return $qb;
	}

}
