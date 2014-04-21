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
 * @ORM\Entity(readOnly=TRUE)
 * @ORM\Table(name="forum_ranks")
 */
class Rank extends Kdyby\Doctrine\Entities\BaseEntity
{

	/**
	 * @ORM\Id()
	 * @ORM\Column(type="string", length=20, nullable=TRUE)
	 * @var string
	 */
	private $id;

	/**
	 * @ORM\Column(type="string", nullable=TRUE)
	 * @var string
	 */
	private $title;

	/**
	 * @ORM\Column(type="integer", nullable=TRUE)
	 * @var integer
	 */
	private $value;



	/**
	 * @return string
	 */
	public function getId()
	{
		return $this->id;
	}



	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}



	/**
	 * @return int
	 */
	public function getValue()
	{
		return $this->value;
	}

}
