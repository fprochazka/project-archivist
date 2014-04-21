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
 * @ORM\Table(name="forum_category")
 */
class Category extends Kdyby\Doctrine\Entities\IdentifiedEntity
{

	/**
	 * @ORM\Column(type="string", nullable=FALSE)
	 * @var string
	 */
	protected $name;

	/**
	 * @ORM\OneToMany(targetEntity="Post", mappedBy="category", cascade={"persist"})
	 * @var Post
	 */
	protected $posts;

	/**
	 * @ORM\Column(type="smallint", nullable=FALSE)
	 * @var integer
	 */
	protected $position = 0;

}
