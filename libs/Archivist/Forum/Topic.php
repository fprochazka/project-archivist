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
 * @ORM\Table(name="forum_topics")
 */
class Topic extends Kdyby\Doctrine\Entities\IdentifiedEntity
{

	/**
	 * @ORM\ManyToOne(targetEntity="Question", cascade={"persist"})
	 * @var Question
	 */
	protected $question;

	/**
	 * @ORM\OneToMany(targetEntity="Post", mappedBy="topic", cascade={"persist"}, fetch="EXTRA_LAZY")
	 * @var Post[]
	 */
	protected $posts;

}
