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
class Item extends Nette\Object
{

	/**
	 * @var string
	 */
	private $title;

	/**
	 * @var string
	 */
	private $itemLink;

	/**
	 * @var string
	 */
	private $commentsLink;

	/**
	 * @var \DateTime
	 */
	private $publicationDate;

	/**
	 * @var string
	 */
	private $creator;

	/**
	 * @var string
	 */
	private $guid;

	/**
	 * @var string
	 */
	private $description;

	/**
	 * @var string
	 */
	private $content;

	/**
	 * @var array<string>
	 */
	private $categories = array();



	public function __construct($title, $itemLink, \DateTime $publicationDate = NULL)
	{
		$this->setTitle($title);
		$this->setItemLink($itemLink);
		$this->setPublicationDate($publicationDate);
	}



	/**
	 * @param array $categories
	 * @return Item
	 */
	public function setCategories(array $categories)
	{
		$this->categories = $categories;
		return $this;
	}



	/**
	 * @return array
	 */
	public function getCategories()
	{
		return (array) $this->categories;
	}



	/**
	 * @param string $commentsLink
	 * @return Item
	 */
	public function setCommentsLink($commentsLink)
	{
		$this->commentsLink = $commentsLink;
		return $this;
	}



	/**
	 * @return string
	 */
	public function getCommentsLink()
	{
		return $this->commentsLink;
	}



	/**
	 * @param string $content
	 * @return Item
	 */
	public function setContent($content)
	{
		$this->content = $content;
		return $this;
	}



	/**
	 * @return string
	 */
	public function getContent()
	{
		return $this->content;
	}



	/**
	 * @param string $creator
	 * @return Item
	 */
	public function setCreator($creator)
	{
		$this->creator = $creator;
		return $this;
	}



	/**
	 * @return string
	 */
	public function getCreator()
	{
		return $this->creator;
	}



	/**
	 * @param string $description
	 * @return Item
	 */
	public function setDescription($description)
	{
		$this->description = $description;
		return $this;
	}



	/**
	 * @return string
	 */
	public function getDescription()
	{
		return $this->description;
	}



	/**
	 * @param string $guid
	 * @return Item
	 */
	public function setGuid($guid)
	{
		$this->guid = $guid;
		return $this;
	}



	/**
	 * @return string
	 */
	public function getGuid()
	{
		return $this->guid;
	}



	/**
	 * @param string $itemLink
	 * @return Item
	 */
	public function setItemLink($itemLink)
	{
		$this->itemLink = $itemLink;
		return $this;
	}



	/**
	 * @return string
	 */
	public function getItemLink()
	{
		return $this->itemLink;
	}



	/**
	 * @param \DateTime $publicationDate
	 * @return Item
	 */
	public function setPublicationDate(\DateTime $publicationDate = NULL)
	{
		$this->publicationDate = $publicationDate;
		return $this;
	}



	/**
	 * @return \DateTime
	 */
	public function getPublicationDate()
	{
		return $this->publicationDate;
	}



	/**
	 * @param string $title
	 * @return Item
	 */
	public function setTitle($title)
	{
		$this->title = $title;
		return $this;
	}



	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}

}
