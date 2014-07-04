<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Archivist\Forum;

use Doctrine;
use Doctrine\ORM\Mapping as ORM;
use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @ORM\Entity()
 */
class Answer extends Post
{

	/**
	 * @ORM\ManyToOne(targetEntity="Question", inversedBy="answers", cascade={"persist"})
	 * @var Question
	 */
	protected $question;

	/**
	 * @ORM\ManyToOne(targetEntity="Post", inversedBy="comments", cascade={"persist"})
	 * @var Post
	 */
	protected $parentPost;



	public function __construct($content, Post $parent = NULL)
	{
		parent::__construct($content);
		$this->parentPost = $parent;
	}



	/**
	 * @return Question
	 */
	public function getQuestion()
	{
		return $this->question;
	}



	/**
	 * @return Post
	 */
	public function getParentPost()
	{
		return $this->parentPost;
	}



	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->getQuestion()->getTitle();
	}

}
