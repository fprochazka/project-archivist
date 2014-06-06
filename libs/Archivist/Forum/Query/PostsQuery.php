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
use Archivist\Forum\Post;
use Archivist\Forum\Question;
use Archivist\InvalidArgumentException;
use Archivist\Users\Identity;
use Archivist\Users\User;
use Doctrine\ORM\NativeQuery;
use Kdyby;
use Kdyby\Doctrine\Mapping\ResultSetMappingBuilder;
use Kdyby\Doctrine\NativeQueryBuilder;
use Kdyby\Doctrine\QueryBuilder;
use Kdyby\Persistence\Queryable;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class PostsQuery extends Kdyby\Doctrine\QueryObject
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
		$this->filter[] = function (NativeQueryBuilder $qb) use ($category) {
			$qb->andWhere('p.category_id = :category', $category->getId());
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

		$this->filter[] = function (NativeQueryBuilder $qb) use ($user) {
			$qb->andWhere('u.id = :user', $user->getId());
		};

		return $this;
	}



	public function withCategory()
	{
		$this->select[] = function (NativeQueryBuilder $qb, ResultSetMappingBuilder $rsm) {
			$qb
				->innerJoin('p', 'category', 'c', 'p.category_id = c.id')
				->innerJoin('c', 'parent', 'cp', 'c.parent_id = cp.id');

			$qb->addColumn('c', 'id', 'name');
			$qb->addColumn('cp', 'id', 'name');
		};
		return $this;
	}



	public function withQuestion()
	{
		$this->select[] = function (NativeQueryBuilder $qb, ResultSetMappingBuilder $rsm) {
			$qb
				->leftJoin('p', 'question', 'q', 'p.question_id = q.id AND p.type = :answer', 'answer')
				->leftJoin('q', 'author', 'qi', 'q.author_id = qi.id');

			$qb->addColumn('q', 'type', 'title', 'content', 'created_at');
		};
		return $this;
	}



	/**
	 * @param Queryable $repository
	 * @return \Doctrine\ORM\Query|\Doctrine\ORM\QueryBuilder|\Kdyby\Doctrine\NativeQueryWrapper
	 */
	protected function doCreateQuery(Queryable $repository)
	{
		$qb = $this->createBasicDql($repository);
		$rsm = $qb->getResultSetMapper();

		$qb->addColumn('p', 'id', 'type', 'title', 'content', 'created_at', 'edited_at');
		$qb->addColumn('u', 'id', 'name', 'email');

		foreach ($this->select as $modifier) {
			$modifier($qb, $rsm);
		}

		$qb->addOrderBy('p.created_at', 'DESC');

		return $qb;
	}



	/**
	 * @param Queryable $repository
	 * @return NativeQuery
	 */
	protected function doCreateCountQuery(Queryable $repository)
	{
		$qb = $this->createBasicDql($repository)
			->select('COUNT(p.id) as total_count');

		$rsm = $qb->getResultSetMapper();
		$rsm->addScalarResult('total_count', 'total_count');

		return $qb;
	}



	/**
	 * @param Queryable|Kdyby\Doctrine\EntityDao $repository
	 * @return NativeQueryBuilder
	 */
	private function createBasicDql(Queryable $repository)
	{
		$qb = (new NativeQueryBuilder($repository->getEntityManager()))
			->select()->from(Post::class, 'p')
			->innerJoin('p', 'author', 'i', 'p.author_id = i.id')
			->innerJoin('i', 'user', 'u', 'i.user_id = u.id')
			->andWhere('p.spam = FALSE AND p.deleted = FALSE');

		$rsm = $qb->getResultSetMapper();

		foreach ($this->filter as $modifier) {
			$modifier($qb, $rsm);
		}

		return $qb;
	}

}
