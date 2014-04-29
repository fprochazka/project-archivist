<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Archivist\Rss;

use Archivist\Forum\Answer;
use Archivist\Forum\IRenderer;
use Archivist\Forum\Question;
use Archivist\Forum\Reader;
use Archivist\InvalidStateException;
use Archivist\UI\BaseControl;
use Kdyby;
use Kdyby\Doctrine\QueryObject;
use Kdyby\NewsFeed\Channel;
use Kdyby\NewsFeed\Item;
use Kdyby\NewsFeed\Responses\RssResponse;
use Nette;
use Nette\Utils\Html;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class FeedControl extends BaseControl
{

	/**
	 * @var \Kdyby\NewsFeed\Channel
	 */
	private $channel;

	/**
	 * @var QueryObject
	 */
	private $query;

	/**
	 * @var Reader
	 */
	private $reader;

	/**
	 * @var \Archivist\Forum\IRenderer
	 */
	private $postRenderer;



	public function __construct(Reader $reader, IRenderer $postRenderer)
	{
		parent::__construct();
		$this->reader = $reader;
		$this->postRenderer = $postRenderer;
	}



	/**
	 * @return Channel
	 */
	public function getChannel()
	{
		if ($this->channel === NULL) {
			$this->channel = (new Channel())
				->setFeedUri($this->link('//feed!'))
				->setWebsiteUri($this->getPresenter()->link('//Categories:'));
		}

		return $this->channel;
	}



	public function setQuery(QueryObject $query)
	{
		$this->query = $query;
		return $this;
	}



	public function handleFeed()
	{
		if (!$this->query) {
			throw new InvalidStateException("Missing query to feed the RSS");
		}

		$channel = clone $this->getChannel();
		$presenter = $this->getPresenter();

		$results = $this->reader->fetch($this->query)->setFetchJoinCollection(FALSE);
		foreach ($results->applyPaging(0, 100) as $post) {
			/** @var Answer|Question $post */

			$permalink = $presenter->link('//Question:', ['permalinkId' => $post->getId()]);
			$title = $post->isQuestion() ? $post->getTitle() : $post->getQuestion()->getTitle();
			$link = $post->isQuestion() ? $presenter->link('//Question:', $post->getId()) : $permalink;

			$item = (new Item($title, $link, $post->getCreatedAt()))
				->setCreator($post->getAuthor()->name)
				->setDescription($this->postRenderer->toHtml($post->getContent()))
				->setCategories([$post->category->name, $post->category->parent->name])
				->setGuid($permalink);

			$channel->addItem($item);
		}

		$this->getPresenter()->sendResponse(new RssResponse($channel));
	}



	public function renderLink()
	{
		echo Html::el('link', [
			'rel' => 'alternate',
			'type' => 'application/rss+xml',
			'title' => $this->channel->getTitle()
		])->href($this->getChannel()->getFeedUri());
	}

}



interface IFeedControlFactory
{

	/** @return FeedControl */
	function create();
}
