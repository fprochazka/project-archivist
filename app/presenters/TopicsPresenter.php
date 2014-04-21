<?php

namespace Archivist;

use Archivist\Forum\Category;
use Archivist\Forum\Question;
use Archivist\UI\BaseForm;
use Nette;
use Nette\Forms\Controls\SubmitButton;



class TopicsPresenter extends BasePresenter
{

	/**
	 * @persistent
	 */
	public $categoryId;

	/**
	 * @var Category
	 */
	private $category;

	/**
	 * @var \Kdyby\Doctrine\EntityManager
	 * @autowire
	 */
	protected $em;

	/**
	 * @var \Archivist\Forum\Writer
	 * @autowire
	 */
	protected $writer;

	/**
	 * @var \Archivist\Users\Manager
	 * @autowire
	 */
	protected $users;



	public function actionDefault($categoryId)
	{
		if (!$this->category = $this->em->getDao(Category::class)->find($categoryId)) {
			$this->error();
		}
	}



	public function renderDefault($categoryId)
	{
		$this->template->category = $this->category;

		$questions = $this->em->getDao(Question::class);
		$qb = $questions->createQueryBuilder('q')
			->innerJoin('q.author', 'i')->addSelect('i')
			->innerJoin('i.user', 'u')->addSelect('u')
			->innerJoin('q.category', 'c')->addSelect('c')
			->andWhere('q.category = :category')->setParameter('category', $this->category->getId())
			->andWhere('q.deleted = FALSE AND q.spam = FALSE')
			->orderBy('q.createdAt', 'DESC');

		$this->template->topics = $qb->getQuery()->getResult();
	}



	/**
	 * @return BaseForm
	 */
	protected function createComponentCreateTopicForm(IPostFormFactory $factory)
	{
		/** @var PostForm|SubmitButton[] $form */
		$form = $factory->create();
		$form->addTitle();
		$form['send']->caption = "Post question";

		$form->onSuccess[] = function (BaseForm $form, $values) {
			if (!$this->category) {
				$this->error();
			}

			if (!$this->category->parent) {
				$form->addError("Please create your topic in specific category");
				return;
			}

			$topic = $this->writer->askQuestion(new Question($values->title, $values->content), $this->category);
			$this->redirect('Question:', array('questionId' => $topic->id));
		};

		$form->setupBootstrap3Rendering();
		return $form;
	}


}
