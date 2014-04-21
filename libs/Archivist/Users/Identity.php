<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Archivist\Users;

use Doctrine;
use Doctrine\ORM\Mapping as ORM;
use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @ORM\Entity()
 * @ORM\Table(name="user_identities")
 *
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string", length=10)
 * @ORM\DiscriminatorMap({
 *    "base" = "Archivist\Users\Identity",
 *    "password" = "Archivist\Users\Identity\EmailPassword",
 *    "github" = "Archivist\Users\Identity\Github",
 *    "google" = "Archivist\Users\Identity\Google",
 *    "twitter" = "Archivist\Users\Identity\Twitter"
 * })
 */
abstract class Identity extends Kdyby\Doctrine\Entities\IdentifiedEntity
{

	/**
	 * @ORM\ManyToOne(targetEntity="\Archivist\Users\User", inversedBy="identities", cascade={"persist"})
	 * @var User
	 */
	protected $user;

	/**
	 * @ORM\OneToMany(targetEntity="\Archivist\Forum\Post", mappedBy="author", cascade={"persist"})
	 * @var \Archivist\Forum\Post[]
	 */
	protected $posts;

}
