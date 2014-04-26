<?php

namespace Archivist\ForumModule;

use Archivist\Forum\Category;
use Archivist\Forum\Query\QuestionsQuery;
use Archivist\Forum\Question;
use Archivist\UI\BaseForm;
use Archivist\VisualPaginator;
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
	 * @var \Archivist\Forum\Writer
	 * @autowire
	 */
	protected $writer;

	/**
	 * @var \Archivist\Forum\Reader
	 * @autowire
	 */
	protected $reader;



	public function actionDefault($categoryId)
	{
		if (!$this->category = $this->reader->readCategory($categoryId)) {
			$this->error();

		} elseif ($this->category->url) {
			$this->redirectUrl($this->category->url);
		}
	}



	protected function beforeRender()
	{
		parent::beforeRender();
		$this->template->category = $this->category;
	}



	public function renderDefault($categoryId)
	{
		$query = (new QuestionsQuery($this->category))
			->withAnswersCount()
			->withLastPost()
			->sortByPinned()
			->sortByHasSolution();

		/** @var TopicsPresenter|VisualPaginator[] $this */
		$this->template->topics = $this->reader->fetch($query)
			->setFetchJoinCollection(FALSE)
			->applyPaginator($this['vp']->getPaginator());
	}



	protected function createComponentVp()
	{
		return new VisualPaginator(30);
	}



	/**
	 * @param IPostFormFactory $factory
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
