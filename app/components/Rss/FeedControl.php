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
use Archivist\Forum\Post;
use Archivist\Forum\Question;
use Archivist\Forum\Reader;
use Archivist\InvalidStateException;
use Archivist\NotImplementedException;
use Archivist\UI\BaseControl;
use Doctrine\ORM\AbstractQuery;
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
	 * @var int
	 */
	private $queryHydration;

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



	public function setQuery(QueryObject $query, $hydration = AbstractQuery::HYDRATE_OBJECT)
	{
		$this->query = $query;
		$this->queryHydration = $hydration;
		return $this;
	}



	public function handleFeed()
	{
		if (!$this->query) {
			throw new InvalidStateException("Missing query to feed the RSS");
		}

		$channel = clone $this->getChannel();
		$presenter = $this->getPresenter();

		$results = $this->reader->fetch($this->query)
			->setFetchJoinCollection(FALSE)
			->applyPaging(0, 100)
			->getIterator($this->queryHydration);

		foreach ($results as $post) {
			/** @var Answer|Question|\stdClass $post */

			if ($post instanceof Post) {
				$permalink = $presenter->link('//Question:', ['permalinkId' => $post->getId()]);
				$link = $post->isQuestion() ? $presenter->link('//Question:', $post->getId()) : $permalink;

				$item = (new Item($post->getTitle(), $link, $post->getCreatedAt()))
					->setCreator($post->getAuthor()->getUser()->getName())
					->setDescription($this->postRenderer->toHtml($post->getContent()))
					->setCategories([$post->getCategory()->getName(), $post->getCategory()->getParent()->getName()])
					->setGuid($permalink);

			} elseif (isset($post->p_id, $post->p_type, $post->p_content, $post->p_created_at)) {
				$permalink = $presenter->link('//Question:', ['permalinkId' => $post->p_id]);
				$link = $post->p_type === 'question' ? $presenter->link('//Question:', $post->p_id) : $permalink;
				$title = $post->p_type === 'question' ? $post->p_title : $post->q_title;

				$item = (new Item($title, $link, $post->p_created_at))
					->setCreator($post->u_name)
					->setDescription($this->postRenderer->toHtml($post->p_content))
					->setCategories([$post->c_name, $post->cp_name])
					->setGuid($permalink);

			} else {
				throw new NotImplementedException;
			}

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
