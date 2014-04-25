<?php

/**
 * Nette Framework Extras
 *
 * This source file is subject to the New BSD License.
 *
 * For more information please see http://extras.nettephp.com
 *
 * @copyright  Copyright (c) 2009 David Grudl
 * @license    New BSD License
 * @link       http://extras.nettephp.com
 * @package    Nette Extras
 * @version    $Id: VisualPaginator.php 4 2009-07-14 15:22:02Z david@grudl.com $
 */

namespace Archivist;

use Nette\Application\UI\Control;
use Nette\Utils\Html;
use Nette\Utils\Paginator;



/**
 * Visual paginator control.
 *
 * @author     David Grudl
 * @author     Filip Procházka
 */
class VisualPaginator extends Control
{
	/**
	 * @var Paginator
	 */
	private $paginator;

	/**
	 * @persistent
	 */
	public $page = 1;

	/**
	 * @var bool
	 */
	public $showWords = TRUE;

	/**
	 * @var bool
	 */
	public $alwaysShow = FALSE;



	public function __construct($itemsPerPage = NULL)
	{
		parent::__construct();

		if ($itemsPerPage !== NULL) {
			$this->getPaginator()->itemsPerPage = $itemsPerPage;
		}
	}



	/**
	 * @return Paginator
	 */
	public function getPaginator()
	{
		if (!$this->paginator) {
			$this->paginator = new Paginator;
		}

		// always update when touching on paginator
		$this->paginator->page = max($this->page, 1);

		return $this->paginator;
	}



	/**
	 * Renders paginator.
	 * @return void
	 */
	public function render()
	{
		echo $this->getControl();
	}



	/**
	 * @return Html
	 */
	public function getControl()
	{
		return Html::el('div', ['class' => 'paginator'])
			->setHtml(($this->getPaginator()->pageCount > 1 || $this->alwaysShow) ? $this->template : '');
	}



	/**
	 * @param null $class
	 * @return \Nette\Templating\FileTemplate
	 */
	protected function createTemplate($class = NULL)
	{
		$template = parent::createTemplate($class);
		/** @var \Nette\Templating\FileTemplate|\stdClass $template */
		$template->setFile(__DIR__ . '/default.latte');

		$template->steps = $this->calcSteps();
		$template->paginator = $this->getPaginator();
		$template->showWords = $this->showWords;

		return $template;
	}



	/**
	 * @return array
	 */
	public function calcSteps()
	{
		$paginator = $this->getPaginator();

		$page = $paginator->page;
		if ($paginator->pageCount <= 10) {
			$steps = range(1, $paginator->pageCount);

		} else {
			$arr = range(max($paginator->firstPage, $page - 3), min($paginator->lastPage, $page + 3));
			$count = 4;
			$quotient = ($paginator->pageCount - 1) / $count;
			for ($i = 0; $i <= $count; $i++) {
				$arr[] = round($quotient * $i) + $paginator->firstPage;
			}
			sort($arr);
			$steps = array_values(array_unique($arr));
		}

		return $steps;
	}



	public function loadState(array $params)
	{
		parent::loadState($params);
		$this->getPaginator()->page = max($this->page, 1);
	}

}
