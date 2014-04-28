<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\NewsFeed;

use Kdyby;
use Nette;



/**
 * @author Robert Lemke <rl@robertlemke.com>
 * @author Filip Procházka <filip@prochazka.su>
 */
class Channel extends Nette\Object
{

	/**
	 * @var string
	 */
	private $title;

	/**
	 * @var string
	 */
	private $description;

	/**
	 * @var string
	 */
	private $feedUri;

	/**
	 * @var string
	 */
	private $websiteUri;

	/**
	 * @var string
	 */
	private $imageUri;

	/**
	 * @var string
	 */
	private $language;

	/**
	 * @var array|Item[]
	 */
	private $items = array();



	/**
	 * @param string $description
	 * @return Channel
	 */
	public function setDescription($description)
	{
		$this->description = $description;
		return $this;
	}



	/**
	 * @param string $title
	 * @return Channel
	 */
	public function setTitle($title)
	{
		$this->title = $title;
		return $this;
	}



	/**
	 * @param string $feedUri
	 * @return Channel
	 */
	public function setFeedUri($feedUri)
	{
		$this->feedUri = $feedUri;
		return $this;
	}



	/**
	 * @param string $websiteUri
	 * @return Channel
	 */
	public function setWebsiteUri($websiteUri)
	{
		$this->websiteUri = $websiteUri;
		return $this;
	}



	/**
	 * @param string $imageUri
	 * @return Channel
	 */
	public function setImageUri($imageUri)
	{
		$this->imageUri = $imageUri;
		return $this;
	}



	/**
	 * @param string $language
	 * @return Channel
	 */
	public function setLanguage($language)
	{
		$this->language = $language;
		return $this;
	}



	/**
	 * Adds a new item to this channel
	 *
	 * @param Item $item An item
	 * @return Channel
	 */
	public function addItem(Item $item)
	{
		$this->items[] = $item;
		return $this;
	}



	/**
	 * @return string
	 */
	public function getWebsiteUri()
	{
		return $this->websiteUri;
	}



	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}



	/**
	 * @return string
	 */
	public function getLanguage()
	{
		return $this->language;
	}



	/**
	 * @return array|Item[]
	 */
	public function getItems()
	{
		return $this->items;
	}



	/**
	 * @return string
	 */
	public function getImageUri()
	{
		return $this->imageUri;
	}



	/**
	 * @return string
	 */
	public function getFeedUri()
	{
		return $this->feedUri;
	}



	/**
	 * @return string
	 */
	public function getDescription()
	{
		return $this->description;
	}

}
