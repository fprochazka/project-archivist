<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Archivist\ForumModule\Posts;

use Archivist\Forum\Query\PostsQuery;
use Archivist\Forum\Query\QuestionsQuery;
use Archivist\Forum\Reader;
use Archivist\UI\BaseControl;
use Archivist\VisualPaginator;
use Doctrine\ORM\AbstractQuery;
use IPub;
use Kdyby;
use Kdyby\Doctrine\Hydration\HashHydrator;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @persistent(vp)
 */
class PostsControl extends BaseControl
{
	/**
	 * @var int
	 */
	public $perPage = 30;

	/**
	 * @var bool
	 */
	public $fetchJoin = FALSE;

	/**
	 * @var string
	 */
	private $view = 'posts';

	/**
	 * @var QuestionsQuery
	 */
	private $query;

	/**
	 * @var Reader
	 */
	private $reader;

	/**
	 * @var \IPub\Gravatar\Gravatar
	 */
	protected $gravatar;



	public function __construct(Reader $reader, IPub\Gravatar\Gravatar $gravatar)
	{
		$this->reader = $reader;
		$this->gravatar = $gravatar;
	}



	/**
	 * @param PostsQuery $query
	 * @return PostsControl
	 */
	public function setQuery(PostsQuery $query)
	{
		$this->query = $query;
		return $this;
	}



	/**
	 * @param string $view
	 * @return PostsControl
	 */
	public function setView($view)
	{
		$this->view = $view;
		return $this;
	}



	public function render(array $options = [])
	{
		if (!$this->query) {
			return;
		}

		/** @var PostsControl|VisualPaginator[] $this */

		$this->template->posts = $this->reader->fetch($this->query)
			->setFetchJoinCollection($this->fetchJoin)
			->applyPaginator($this['vp']->getPaginator())
			->getIterator(HashHydrator::NAME);

		$this->template->options = $options + ['author' => TRUE];
		$this->template->setFile(__DIR__ . '/' . $this->view . '.latte')->render();
	}



	protected function createComponentVp()
	{
		return new VisualPaginator($this->perPage);
	}



	protected function createTemplate($class = NULL)
	{
		/** @var Nette\Bridges\ApplicationLatte\Template|\stdClass $template */
		$template = parent::createTemplate($class);

		// Add gravatar to template
		$template->_gravatar = $this->gravatar;

		// Register template helpers
		$template->addFilter('gravatar', function ($email, $size = NULL) {
			return $this->gravatar->buildUrl($email, $size);
		});

		return $template;
	}

}



interface IPostsControlFactory
{

	/** @return PostsControl */
	function create();
}
