<?php

namespace Archivist\ForumModule;

use Archivist\Forum\Category;
use Archivist\Forum\Query\PostsQuery;
use Archivist\Forum\Query\QuestionsQuery;
use Archivist\Forum\Question;
use Archivist\ForumModule\Questions\IThreadsControlFactory;
use Archivist\Rss\FeedControl;
use Archivist\Rss\IFeedControlFactory;
use Archivist\UI\BaseForm;
use Kdyby\Doctrine\Hydration\HashHydrator;
use Kdyby\NewsFeed\Channel;
use Kdyby\NewsFeed\Item;
use Kdyby\NewsFeed\Responses\RssResponse;
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

	/**
	 * @var \Archivist\Forum\IRenderer
	 * @autowire
	 */
	protected $postRenderer;



	public function actionDefault($categoryId)
	{
		if (!$this->category = $this->reader->readCategory($categoryId)) {
			$this->error();

		} elseif ($this->category->url) {
			$this->redirectUrl($this->category->url);
		}
	}



	protected function createComponentRss(IFeedControlFactory $factory)
	{
		$control = $factory->create();

		$control->onAttached[] = function (FeedControl $control) {
			$control->getChannel()
				->setTitle($this->category->name . (($parent = $this->category->getParent()) ? ' - ' . $parent->name : '') . ' Questions - help.kdyby.org');

			$control->setQuery((new QuestionsQuery())
				->inCategory($this->category));
		};

		return $control;
	}



	protected function createComponentPosts(IFeedControlFactory $factory)
	{
		$control = $factory->create();

		$control->onAttached[] = function (FeedControl $control) {
			$control->getChannel()
				->setTitle($this->category->name . (($parent = $this->category->getParent()) ? ' - ' . $parent->name : '') . ' Posts - help.kdyby.org');

			$control->setQuery((new PostsQuery())
				->withCategory()
				->withQuestion()
				->inCategory($this->category), HashHydrator::NAME);
		};

		return $control;
	}



	protected function beforeRender()
	{
		parent::beforeRender();
		$this->template->category = $this->category;
	}



	protected function createComponentThreads(IThreadsControlFactory $factory)
	{
		$query = (new QuestionsQuery())
			->inCategory($this->category)
			->withAnswersCount()
			->withLastPost()
			->sortByPinned()
			->sortByHasSolution();

		return $factory->create()->setQuery($query);
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
			$this->redirect('Question:', array('questionId' => $topic->getId()));
		};

		$form->setupBootstrap3Rendering();
		return $form;
	}

}
