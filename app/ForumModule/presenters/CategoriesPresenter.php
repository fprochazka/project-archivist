<?php

namespace Archivist\ForumModule;

use Archivist\Forum\Category;
use Archivist\Forum\Question;
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
			->andWhere('tp.category = c2.id');

		$unsolvedCountQb = $questions->createQueryBuilder('sp')
			->select('COUNT(sp.id)')
			->leftJoin('sp.solution', 'sps')->andWhere('sps.id IS NULL') // has no solution
			->andWhere('sp.pinned = FALSE AND sp.locked = FALSE')
			->andWhere('sp.spam = FALSE AND sp.deleted = FALSE')
			->andWhere('sp.category = c2.id');

		$categories = $this->em->getDao(Category::class);
		$categoriesQb = $categories->createQueryBuilder('c1')
			->where('c1.parent IS NULL')
			->leftJoin('c1.children', 'c2')->addSelect('c2')
			->addSelect("($totalCountQb) as posts_count")
			->addSelect("($unsolvedCountQb) as unsolved_count")
			->leftJoin('c2.lastQuestion', 'lq')->addSelect('lq.id as l_id, lq.createdAt as l_createdAt, lq.title as l_title')
			->leftJoin('lq.author', 'li')
			->leftJoin('li.user', 'lu')->addSelect('lu.name as l_author_name')
			->orderBy('c1.position', 'ASC')->addOrderBy('c2.position', 'ASC');

		$result = array_map('Nette\ArrayHash::from', $categoriesQb->getQuery()->getScalarResult());
		$this->template->categories = Nette\Utils\Arrays::associate($result, 'c1_id|c2_id->');
	}

}
