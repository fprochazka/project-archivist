<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\NewsFeed\Responses;

use Kdyby;
use Kdyby\NewsFeed\Channel;
use Kdyby\NewsFeed\Item;
use Kdyby\NewsFeed\XmlElement;
use Nette;



/**
 * @author Robert Lemke <rl@robertlemke.com>
 * @author Filip Procházka <filip@prochazka.su>
 */
class RssResponse extends Nette\Object implements Nette\Application\IResponse
{

	/**
	 * @var \Kdyby\NewsFeed\Channel
	 */
	private $channel;



	public function __construct(Channel $channel)
	{
		$this->channel = $channel;
	}



	public function send(Nette\Http\IRequest $httpRequest, Nette\Http\IResponse $httpResponse)
	{
		$xml = XmlElement::createRssElement('rss');
		$xml->appendChild($this->channelToXml($this->channel));

		$httpResponse->setContentType('application/rss+xml', 'utf-8');
		echo $xml->toXml();
	}



	/**
	 * @param Channel $channel
	 * @return XmlElement
	 */
	protected function channelToXml(Channel $channel)
	{
		$xml = XmlElement::createRssElement('channel');
		$xml->addChild('title', $channel->getTitle());

		$xml->addChild('link', NULL, array(
			'href' => $channel->getFeedUri(),
			'rel' => 'self',
			'type' => 'application/rss+xml',
		), 'http://www.w3.org/2005/Atom');

		$xml->addChild('link', $channel->getWebsiteUri());
		$xml->addChild('description', $channel->getDescription());
		$xml->addChild('language', $channel->getLanguage());
		$xml->addChild('lastBuildDate', new \DateTime('now', new \DateTimeZone('GMT')));

		foreach ($channel->getItems() as $item) {
			$xml->appendChild($this->feedItemToXml($item));
		}

		return $xml;
	}



	protected function feedItemToXml(Item $item)
	{
		$xml = XmlElement::createRssElement('item');
		$xml->addChild('guid', $item->getGuid(), array('isPermaLink' => 'false'));
		$xml->addChild('title', $item->getTitle());
		$xml->addChild('link', $item->getItemLink());

		if ($item->getCommentsLink() !== NULL) {
			$xml->addChild('comments', $item->getCommentsLink());
		}
		if ($item->getPublicationDate() !== NULL) {
			$xml->addChild('pubDate', $item->getPublicationDate());
		}
		if ($item->getCreator() !== NULL) {
			$xml->addChild('creator', $item->getCreator(), 'http://purl.org/dc/elements/1.1/');
			$xml->addChild('author', $item->getCreator());
		}
		if ($item->getDescription() !== NULL) {
			$xml->addChild('description')->addCdata($item->getDescription());
		}
		foreach ($item->getCategories() as $category) {
			$xml->addChild('category')->addCdata($category);
		}

		return $xml;
	}

}
