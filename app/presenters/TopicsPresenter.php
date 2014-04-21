<?php

namespace Archivist;

use Archivist\Forum\Category;
use Archivist\Forum\Question;
use Archivist\UI\BaseForm;
use Nette;



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



	public function actionDefault($categoryId)
	{
		if (!$this->category = $this->em->getDao(Category::class)->find($categoryId)) {
			$this->error();
		}
	}



	public function renderDefault($categoryId)
	{
		$this->template->category = $this->category;

	}



	/**
	 * @return BaseForm
	 */
	protected function createComponentCreateTopicForm()
	{
		$form = new BaseForm();
		$form->addText('title', 'Topic');
		$form->addTextArea('content', 'Question')
			->setAttribute('rows', 10);

	    $form->addSubmit("send", "Odeslat");
		$form->onSuccess[] = function (BaseForm $form, $values) {
			if (!$this->user->isLoggedIn()) {
				$form->addError("Please login first before posting");
				return;
			}

			if (!$this->category) {
				$this->error();
			}

			$topic = $this->writer->askQuestion(new Question($values->title, $values->content), $this->category);
			$this->redirect('Question:', array('questionId' => $topic->id));
		};

		$form->setupBootstrap3Rendering();
		return $form;
	}


}
