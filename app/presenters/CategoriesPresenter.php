<?php

namespace Archivist;

use Archivist\Forum\Category;
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
		$categories = $this->em->getDao(Category::class);
		$qb = $categories->createQueryBuilder('c1')
			->where('c1.parent IS NULL')
			->leftJoin('c1.children', 'c2')->addSelect('c2')
			->orderBy('c1.position', 'ASC')->addOrderBy('c2.position', 'ASC');

		$this->template->categories = $qb->getQuery()->getResult();
	}

}
