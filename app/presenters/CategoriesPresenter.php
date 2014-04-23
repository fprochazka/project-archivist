<?php

namespace Archivist;

use Archivist\Forum\Category;
use Archivist\Forum\Question;
use Doctrine\ORM\Query\Expr\Join;
use Nette;


class CategoriesPresenter extends BasePresenter
{

	/**
	 * @var \Kdyby\Doctrine\EntityManager
	 * @autowire
	 */
	protected $em;



	public function renderDefault()
	{
		$questions = $this->em->getDao(Question::class);

		$totalCountQb = $questions->createQueryBuilder('tp')
			->select('COUNT(tp.id)')
			->andWhere('tp.spam = FALSE AND tp.deleted = FALSE')
			->innerJoin('tp.category', 'tpc')
			->andWhere('tpc.id = c2.id');

		$solvedCountQb = $questions->createQueryBuilder('sp')
			->select('COUNT(sp.id)')
			->andWhere('sp.spam = FALSE AND sp.deleted = FALSE')
			->innerJoin('sp.solution', 'sps') // has solution
			->innerJoin('sp.category', 'spc')
			->andWhere('spc.id = c2.id');

		$categories = $this->em->getDao(Category::class);
		$qb = $categories->createQueryBuilder('c1')
			->where('c1.parent IS NULL')
			->leftJoin('c1.children', 'c2')->addSelect('c2')
			->addSelect('(' . $totalCountQb->getDQL() . ') as posts_count')
			->addSelect('(' . $solvedCountQb->getDQL() . ') as solved_count')
			->leftJoin(Question::class, 'l', Join::WITH, 'c2 = l.category AND l.spam = FALSE AND l.deleted = FALSE')
				->addSelect('l.id as l_id, l.createdAt as l_createdAt, l.title as l_title')
			->leftJoin('l.author', 'li')
			->leftJoin('li.user', 'lu')->addSelect('lu.name as l_author_name')
			->orderBy('c1.position', 'ASC')->addOrderBy('c2.position', 'ASC')
			->groupBy('c1.id, c2.id');

		$result = array_map('Nette\ArrayHash::from', $qb->getQuery()->getScalarResult());
		$this->template->categories = Nette\Utils\Arrays::associate($result, 'c1_id|c2_id->');
	}

}
