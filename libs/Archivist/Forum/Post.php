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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @ORM\Entity()
 * @ORM\Table(name="forum_posts", indexes={
 * 		@ORM\Index(columns={"spam", "deleted", "created_at", "type"}),
 * 		@ORM\Index(columns={"spam", "deleted", "category_id", "type", "pinned", "locked"})
 * })
 *
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string", length=10)
 * @ORM\DiscriminatorMap({
 *    "post" = "Post",
 *    "question" = "Question",
 *    "answer" = "Answer"
 * })
 */
abstract class Post extends Identified
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
	 * @ORM\ManyToOne(targetEntity="\Archivist\Users\User", inversedBy="posts", cascade={"persist"})
	 * @var \Archivist\Users\User
	 */
	private $user;

	/**
	 * @ORM\OneToMany(targetEntity="Vote", mappedBy="post", cascade={"persist"})
	 * @var Vote|ArrayCollection
	 */
	protected $votes;

	/**
	 * @ORM\Column(type="integer", name="votes", nullable=FALSE, options={"default":0})
	 * @var string
	 */
	protected $votesSum = 0;

	/**
	 * @ORM\OrderBy(value={"votesSum": "DESC", "createdAt": "ASC"})
	 * @ORM\OneToMany(targetEntity="Answer", mappedBy="parentPost", cascade={"persist"})
	 * @var Answer|ArrayCollection
	 */
	protected $comments;

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

	/**
	 * @ORM\Column(type="boolean", nullable=FALSE, options={"default":"0"})
	 * @var boolean
	 */
	private $deleted = FALSE;

	/**
	 * @ORM\Column(type="boolean", nullable=FALSE, options={"default":"0"})
	 * @var boolean
	 */
	private $spam = FALSE;



	public function __construct($content)
	{
		$this->content = $content;
		$this->votes = new ArrayCollection();
		$this->comments = new ArrayCollection();
		$this->createdAt = new \DateTime();
	}



	/**
	 * @return boolean
	 */
	public function isDeleted()
	{
		return $this->deleted;
	}



	/**
	 * @param boolean $deleted
	 * @return Post
	 */
	public function setDeleted($deleted)
	{
		$this->deleted = (bool) $deleted;
		return $this;
	}



	/**
	 * @return boolean
	 */
	public function isSpam()
	{
		return $this->spam;
	}



	/**
	 * @param boolean $spam
	 * @return Post
	 */
	public function setSpam($spam)
	{
		$this->spam = (bool) $spam;
		return $this;
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
		$this->user = $author->getUser();
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



	public function isAuthor(Identity $identity = NULL)
	{
		if (!$identity) {
			return FALSE;
		}

		if ($this->author === $identity) {
			return TRUE;
		}

		if ($this->user === NULL) {
			$this->user = $this->author->getUser();
		}

		return $this->user === $identity->getUser();
	}



	protected function updated()
	{
		$this->editedAt = new \DateTime();
	}



	public function isQuestion()
	{
		return $this instanceof Question;
	}



	public function isAnswer()
	{
		return $this instanceof Answer;
	}



	/**
	 * @param Identity $author
	 * @return Vote|NULL
	 */
	public function getVoteOfAuthor(Identity $author)
	{
		return $this->votes->filter(function (Vote $v) use ($author) {
			return $v->getUser() === $author->getUser();
		})->first();
	}



	/**
	 * @return bool
	 */
	public function hasComments()
	{
		$criteria = Criteria::create();
		$criteria->andWhere($criteria->expr()->eq('deleted', false));
		$criteria->andWhere($criteria->expr()->eq('spam', false));
		$criteria->setMaxResults(1);

		return $this->comments->matching($criteria)->count() > 0;
	}



	/**
	 * @return Answer
	 */
	public function getComments()
	{
		$criteria = Criteria::create();
		$criteria->andWhere($criteria->expr()->eq('deleted', FALSE));
		$criteria->andWhere($criteria->expr()->eq('spam', FALSE));

		return $this->comments->matching($criteria)->toArray();
	}



	public function __toString()
	{
		$type = $this->isQuestion() ? 'Question' : 'Answer';
		return $type . ' ' . $this->id . '#' . ($this->editedAt ? $this->editedAt->format('YmdHis') : $this->createdAt->format('YmdHis'));
	}

}
