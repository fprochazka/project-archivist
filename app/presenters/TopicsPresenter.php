<?php

namespace Archivist;

use Archivist\Forum\Category;
use Nette;


class TopicsPresenter extends BasePresenter
{

	/**
	 * @persistent
	 */
	public $categoryId;

	/**
	 * @var \Kdyby\Doctrine\EntityManager
	 * @autowire
	 */
	protected $em;



	public function actionDefault($categoryId)
	{
		if (!$this->em->getDao(Category::class)->find($categoryId)) {
			$this->error();
		}
	}



	public function renderDefault($categoryId)
	{
		$categories = $this->em->getDao(Category::class);
		$category = $categories->find($categoryId);

		$this->template->category = $category;

	}

}
