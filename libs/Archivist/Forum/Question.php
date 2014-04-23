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
	 * @ORM\OneToMany(targetEntity="Answer", mappedBy="question", cascade={"persist"}, fetch="EXTRA_LAZY")
	 * @var Answer[]
	 */
	protected $answers;



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



	public function addAnswer(Answer $answer)
	{
		$this->answers[] = $answer;
		$answer->question = $this;
		$answer->category = $this->category;
	}

}
