<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Archivist\ForumModule\Questions;

use Archivist\Forum\Query\QuestionsQuery;
use Archivist\Forum\Reader;
use Archivist\UI\BaseControl;
use Archivist\VisualPaginator;
use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @persistent(vp)
 */
class ThreadsControl extends BaseControl
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
	private $view = 'threads';

	/**
	 * @var QuestionsQuery
	 */
	private $query;

	/**
	 * @var Reader
	 */
	private $reader;



	public function __construct(Reader $reader)
	{
		$this->reader = $reader;
	}



	/**
	 * @param QuestionsQuery $query
	 * @return ThreadsControl
	 */
	public function setQuery(QuestionsQuery $query)
	{
		$this->query = $query;
		return $this;
	}



	/**
	 * @param string $view
	 * @return ThreadsControl
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

		/** @var ThreadsControl|VisualPaginator[] $this */

		$this->template->topics = $this->reader->fetch($this->query)
			->setFetchJoinCollection($this->fetchJoin)
			->applyPaginator($this['vp']->getPaginator());

		$this->template->options = $options + ['author' => TRUE];
		$this->template->setFile(__DIR__ . '/' . $this->view . '.latte')->render();
	}



	protected function createComponentVp()
	{
		return (new VisualPaginator($this->perPage))
			->setAlwaysShow(TRUE);
	}

}



interface IThreadsControlFactory
{

	/** @return ThreadsControl */
	function create();
}
