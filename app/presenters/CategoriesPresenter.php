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
			->innerJoin('tp.category', 'tpc')->andWhere('tpc.id = c2.id');

		$solvedCountQb = $questions->createQueryBuilder('sp')
			->select('COUNT(sp.id)')
			->andWhere('sp.spam = FALSE AND sp.deleted = FALSE')
			->innerJoin('sp.solution', 'sps') // has solution
			->innerJoin('sp.category', 'spc')->andWhere('spc.id = c2.id');

		$categories = $this->em->getDao(Category::class);
		$categoriesQb = $categories->createQueryBuilder('c1')
			->where('c1.parent IS NULL')
			->leftJoin('c1.children', 'c2')->addSelect('c2')
			->addSelect("($totalCountQb) as posts_count")
			->addSelect("($solvedCountQb) as solved_count")
			->orderBy('c1.position', 'ASC')->addOrderBy('c2.position', 'ASC')
			->groupBy('c1.id, c2.id');

		$result = array_map('Nette\ArrayHash::from', $categoriesQb->getQuery()->getScalarResult());
		$this->template->categories = Nette\Utils\Arrays::associate($result, 'c1_id|c2_id->');

		$db = $this->em->getConnection();
		$lastPostIds = $db->executeQuery("
			SELECT c.id, (
				SELECT p.id FROM forum_posts p
				INNER JOIN forum_category pc ON p.category_id = pc.id
				WHERE p.spam = 0 AND p.deleted = 0 AND p.type IN ('question') AND pc.id = c.id
				ORDER BY p.created_at DESC LIMIT 1
			) as last_post_id FROM forum_category c WHERE c.id IN (?)
		", [array_map(function ($row) { return $row->c2_id; }, $result)], [$db::PARAM_INT_ARRAY]);
		$lastPostIds = array_filter(Nette\Utils\Arrays::associate($lastPostIds->fetchAll(), 'id=last_post_id'));

		$lastQuestionQb = $questions->createQueryBuilder('lq')
			->select('lq.id as l_id, lq.createdAt as l_createdAt, lq.title as l_title')
			->andWhere('lq.spam = FALSE AND lq.deleted = FALSE')
			->innerJoin('lq.category', 'lqc')->addSelect('lqc.id as c_id')
			->leftJoin('lq.author', 'li')
			->leftJoin('li.user', 'lu')->addSelect('lu.name as l_author_name')
			->andWhere('lq.id IN (:ids)')->setParameter('ids', $lastPostIds);

		$lastQuestions = array_map('Nette\ArrayHash::from', $lastQuestionQb->getQuery()->getArrayResult());
		$this->template->lastQuestions = Nette\Utils\Arrays::associate($lastQuestions, 'c_id->');
	}

}
