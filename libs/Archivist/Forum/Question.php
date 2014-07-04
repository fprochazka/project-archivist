<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Archivist\Forum;

use Archivist\InvalidArgumentException;
use Doctrine;
use Doctrine\ORM\Mapping as ORM;
use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @ORM\Entity()
 *
 * @property Answer $lastPost
 */
class Question extends Post
{

	/**
	 * @ORM\Column(type="string", nullable=FALSE)
	 * @var string
	 */
	private $title;

	/**
	 * @ORM\ManyToOne(targetEntity="Answer", cascade={"persist"})
	 * @var Answer
	 */
	protected $solution;

	/**
	 * @ORM\ManyToOne(targetEntity="Answer", cascade={"persist"})
	 * @var Answer
	 */
	private $lastPost;

	/**
	 * @ORM\OneToMany(targetEntity="Answer", mappedBy="question", cascade={"persist"}, fetch="EXTRA_LAZY")
	 * @var Answer[]
	 */
	protected $answers;

	/**
	 * @ORM\Column(type="boolean", nullable=FALSE, options={"default":"0"})
	 * @var boolean
	 */
	protected $pinned = FALSE;

	/**
	 * @ORM\Column(type="boolean", nullable=FALSE, options={"default":"0"})
	 * @var boolean
	 */
	protected $locked = FALSE;



	public function __construct($title, $content)
	{
		parent::__construct($content);
		$this->title = Nette\Utils\Strings::firstUpper($title);
	}



	public function changeTitle($title)
	{
		$this->title = Nette\Utils\Strings::firstUpper($title);
		$this->updated();
	}



	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}



	/**
	 * @return boolean
	 */
	public function isPinned()
	{
		return $this->pinned;
	}



	/**
	 * @param boolean $pinned
	 * @return Question
	 */
	public function setPinned($pinned)
	{
		$this->pinned = (bool) $pinned;
		return $this;
	}



	/**
	 * @return boolean
	 */
	public function isLocked()
	{
		return $this->locked;
	}



	/**
	 * @param boolean $locked
	 * @return Question
	 */
	public function setLocked($locked)
	{
		$this->locked = (bool) $locked;
		return $this;
	}



	public function addAnswer(Answer $answer)
	{
		$this->answers[] = $answer;
		$answer->question = $this;
		$answer->category = $this->category;
	}



	/**
	 * @param Answer $solution
	 * @return Question
	 */
	public function setSolution(Answer $solution = NULL)
	{
		if ($solution && ($solution->isDeleted() || $solution->isSpam())) {
			throw new PostIsNotReadableException;
		}

		$this->solution = $solution;
		return $this;
	}



	/**
	 * @return Answer
	 */
	public function getLastPost()
	{
		if ($this->lastPost && ($this->lastPost->isDeleted() || $this->lastPost->isSpam())) {
			$this->lastPost = NULL;
		}

		return $this->lastPost;
	}



	/**
	 * @param Answer $lastPost
	 * @return Question
	 */
	public function setLastPost(Answer $lastPost = NULL)
	{
		if ($lastPost && ($lastPost->getQuestion() !== $this || $lastPost->isSpam() || $lastPost->isDeleted() || $lastPost->getParentPost())) {
			throw new InvalidArgumentException;
		}

		$this->lastPost = $lastPost;
		return $this;
	}

}
