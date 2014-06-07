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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @ORM\Entity()
 * @ORM\Table(name="forum_category")
 *
 * @property Question $lastQuestion
 * @property string $url
 */
class Category extends Identified
{

	/**
	 * @ORM\Column(type="string", nullable=FALSE)
	 * @var string
	 */
	protected $name;

	/**
	 * @ORM\OneToMany(targetEntity="Post", mappedBy="category", cascade={"persist"})
	 * @var Post[]|ArrayCollection
	 */
	protected $posts;

	/**
	 * @ORM\ManyToOne(targetEntity="Question", cascade={"persist"})
	 * @var Question
	 */
	private $lastQuestion;

	/**
	 * @ORM\Column(type="string", nullable=TRUE)
	 * @var string
	 */
	protected $url;

	/**
	 * @ORM\Column(type="text", nullable=TRUE)
	 * @var string
	 */
	protected $description;

	/**
	 * @ORM\Column(type="smallint", nullable=FALSE)
	 * @var integer
	 */
	protected $position = 0;

	/**
	 * @ORM\ManyToOne(targetEntity="Category", inversedBy="children", cascade={"persist"})
	 * @var Category
	 */
	protected $parent;

	/**
	 * @ORM\OneToMany(targetEntity="Category", mappedBy="parent", cascade={"persist"})
	 * @var Category[]|ArrayCollection
	 */
	protected $children;



	public function __construct()
	{
		$this->posts = new ArrayCollection();
		$this->children = new ArrayCollection();
	}



	/**
	 * @return Question|NULL
	 */
	public function getLastQuestion()
	{
		if ($this->lastQuestion && ($this->lastQuestion->isDeleted() || $this->lastQuestion->isSpam())) {
			$this->lastQuestion = NULL;
		}

		return $this->lastQuestion;
	}



	/**
	 * @param Question $question
	 * @return Category
	 */
	public function setLastQuestion(Question $question)
	{
		if ($question && ($question->isSpam() || $question->isDeleted())) {
			throw new InvalidArgumentException;
		}

		$this->lastQuestion = $question;
		return $this;
	}



	/**
	 * @return Category
	 */
	public function getParent()
	{
		return $this->parent;
	}

}
