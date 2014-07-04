<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Archivist\Cli;

use Archivist\Forum\Category;
use Archivist\Forum\Question;
use Kdyby;
use Kdyby\Doctrine\ResultSet;
use KdybyModule\CliPresenter;
use Nette;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tracy\Debugger;
use Tracy\Dumper;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class GenerateSitemapCommand extends Command
{

	/**
	 * @inject
	 * @var Kdyby\Doctrine\EntityManager
	 */
	public $em;

	/**
	 * @inject
	 * @var Nette\DI\Container
	 */
	public $serviceLocator;

	/**
	 * @inject
	 * @var Nette\Application\Application
	 */
	public $app;



	protected function configure()
	{
		$this->setName('archivist:generate:sitemap');
	}



	/**
	 * @see https://support.google.com/webmasters/answer/183668
	 * @todo sitemap index https://support.google.com/webmasters/answer/71453
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$xml = fopen($targetFile = $this->serviceLocator->expand('safe://%wwwDir%/sitemap.xml'), 'wb');
		$write = function ($content, $newline = "\n") use ($xml) {
			fwrite($xml, $content . $newline);
		};
		$writeUrl = function ($loc, $changeFreq = NULL, $lastMod = NULL, $priority = NULL) use ($write) {
			$content = '<loc>' . htmlspecialchars($loc) . '</loc>';
			if ($lastMod !== NULL) {
				$content .= '<lastmod>' . Nette\Utils\DateTime::from($lastMod)->format('Y-m-d') . '</lastmod>';
			}
			if ($changeFreq !== NULL) {
				$content .= '<changefreq>' . htmlspecialchars($changeFreq) . '</changefreq>';
			}
			if ($priority !== NULL) {
				$content .= '<priority>' . number_format($priority, 1, '.', '') . '</priority>';
			}

			$write("<url>{$content}</url>");
		};

		$write('<?xml version="1.0" encoding="UTF-8"?>');
		$write('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');

		/** @var CliPresenter $linker */
		$linker = $this->app->getPresenter();

		// homepage
		$output->writeln("Writing meta");
		$writeUrl($linker->link('//:Forum:Categories:'), 'always');

		// categories
		$output->writeln("Writing categories");
		foreach ($this->em->getDao(Category::class)->findBy(['parent' => NULL, 'url' => NULL]) as $category) {
			$writeUrl($linker->link('//:Forum:Topics:', ['categoryId' => $category->id]), 'always');
		}

		// forum questions
		$output->writeln("Writing questions");
		$query = $this->em->getDao(Question::class)->createQueryBuilder('q')
			->select('partial q.{id, createdAt, editedAt}')
			->leftJoin('q.author', 'a')->addSelect('partial a.{id}')
			->leftJoin('q.lastPost', 'lp')->addSelect('partial lp.{id, createdAt, editedAt}')
			->leftJoin('lp.author', 'lpa')->addSelect('partial lpa.{id}')
			->orderBy('q.createdAt', 'ASC')
			->getQuery();

		$paginator = new Nette\Utils\Paginator();
		$paginator->setItemsPerPage(1000);

		$allQuestions = new ResultSet($query);
		$allQuestions->applyPaginator($paginator);

		/** @var \Symfony\Component\Console\Helper\ProgressHelper $questionsProgress */
		$questionsProgress = $this->getHelperSet()->get('progress');
		$questionsProgress->setFormat($questionsProgress::FORMAT_VERBOSE);
		$questionsProgress->start($output, $allQuestions->getTotalCount());

		$page = 1;
		do {
			$paginator->setPage($page++);
			foreach ($allQuestions->applyPaginator($paginator) as $question) {
				$writeUrl($linker->link('//:Forum:Question:', ['questionId' => $question->id]));
				$questionsProgress->advance();
			}

			$this->em->clear();

		} while (!$paginator->isLast());
		$questionsProgress->finish();


		$write('</urlset>');
		@fclose($xml);
	}

}
