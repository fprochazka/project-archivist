<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Archivist\Forum;

use Archivist\InvalidStateException;
use Archivist\Users\Identity;
use Doctrine;
use Doctrine\ORM\Mapping as ORM;
use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @ORM\Entity()
 * @ORM\Table(name="forum_posts")
 *
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string", length=10)
 * @ORM\DiscriminatorMap({
 *    "post" = "Post",
 *    "question" = "Question",
 *    "answer" = "Answer"
 * })
 */
abstract class Post extends Kdyby\Doctrine\Entities\IdentifiedEntity
{

	/**
	 * @ORM\Column(type="text", nullable=FALSE)
	 * @var string
	 */
	private $content;

	/**
	 * @ORM\ManyToOne(targetEntity="Category", inversedBy="posts", cascade={"persist"})
	 * @ORM\JoinColumn(nullable=FALSE)
	 * @var Category
	 */
	protected $category;

	/**
	 * @ORM\ManyToOne(targetEntity="\Archivist\Users\Identity", inversedBy="posts", cascade={"persist"})
	 * @ORM\JoinColumn(nullable=FALSE)
	 * @var \Archivist\Users\Identity
	 */
	private $author;

	/**
	 * @ORM\Column(type="datetime", nullable=FALSE)
	 * @var \DateTime
	 */
	private $createdAt;

	/**
	 * @ORM\Column(type="datetime", nullable=TRUE)
	 * @var \DateTime
	 */
	private $editedAt;



	public function __construct($content)
	{
		$this->content = $content;
		$this->createdAt = new \DateTime();
	}



	/**
	 * @return string
	 */
	public function getContent()
	{
		return $this->content;
	}



	public function editContent($content)
	{
		$this->content = $content;
		$this->updated();
	}



	public function setAuthor(Identity $author)
	{
		if ($this->author) {
			throw new InvalidStateException();
		}

		$this->author = $author;
	}



	/**
	 * @return Identity
	 */
	public function getAuthor()
	{
		return $this->author;
	}



	/**
	 * @return \DateTime|NULL
	 */
	public function getCreatedAt()
	{
		return $this->createdAt ? clone $this->createdAt : NULL;
	}



	/**
	 * @return \DateTime|NULL
	 */
	public function getEditedAt()
	{
		return $this->editedAt ? clone $this->editedAt : NULL;
	}



	protected function updated()
	{
		$this->editedAt = new \DateTime();
	}

}
