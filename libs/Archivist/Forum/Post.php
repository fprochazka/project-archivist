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
 * @ORM\Table(name="forum_posts")
 *
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string", length=10)
 * @ORM\DiscriminatorMap({
 *    "post" = "Post",
 *    "question" = "Question",
 *    "answer" = "Answer"
 * })
 */
abstract class Post extends Kdyby\Doctrine\Entities\IdentifiedEntity
{

	/**
	 * @ORM\Column(type="text", nullable=FALSE)
	 * @var string
	 */
	protected $content;

	/**
	 * @ORM\ManyToOne(targetEntity="Topic", inversedBy="posts", cascade={"persist"})
	 * @var Topic
	 */
	protected $topic;

	/**
	 * @ORM\ManyToOne(targetEntity="Category", inversedBy="posts", cascade={"persist"})
	 * @var Category
	 */
	protected $category;

	/**
	 * @ORM\ManyToOne(targetEntity="\Archivist\Users\Identity", inversedBy="posts", cascade={"persist"})
	 * @var \Archivist\Users\Identity
	 */
	protected $author;

	/**
	 * @ORM\Column(type="datetime", nullable=FALSE)
	 * @var \DateTime
	 */
	protected $createdAt;

	/**
	 * @ORM\Column(type="datetime", nullable=TRUE)
	 * @var \DateTime
	 */
	protected $editedAt;

}
