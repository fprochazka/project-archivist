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
	 * @ORM\ManyToMany(targetEntity="Rank", cascade={"persist"})
	 * @ORM\JoinTable(name="forum_answer_ranks",
	 *        joinColumns={@ORM\JoinColumn(name="post_id")},
	 *        inverseJoinColumns={@ORM\JoinColumn(name="rank")}
	 *    )
	 * @var Rank[]|\Doctrine\Common\Collections\ArrayCollection
	 */
	protected $ranks;

	/**
	 * @ORM\ManyToOne(targetEntity="Question", inversedBy="answers", cascade={"persist"})
	 * @var Question
	 */
	protected $question;



	/**
	 * @return Question
	 */
	public function getQuestion()
	{
		return $this->question;
	}



	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->getQuestion()->getTitle();
	}

}
