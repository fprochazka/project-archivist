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
	 * @ORM\OneToMany(targetEntity="Answer", mappedBy="question", cascade={"persist"}, fetch="EXTRA_LAZY")
	 * @var Answer[]
	 */
	protected $answers;



	public function __construct($title, $content)
	{
		$this->title = $title;
		parent::__construct($content);
	}



	public function changeTitle($title)
	{
		$this->title = $title;
		$this->updated();
	}

}
