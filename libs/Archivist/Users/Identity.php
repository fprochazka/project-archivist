<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Archivist\Users;

use Archivist\InvalidStateException;
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
abstract class Identity extends Kdyby\Doctrine\Entities\IdentifiedEntity implements Nette\Security\IIdentity
{

	/**
	 * @ORM\ManyToOne(targetEntity="\Archivist\Users\User", inversedBy="identities", cascade={"persist"})
	 * @var User
	 */
	private $user;

	/**
	 * @ORM\OneToMany(targetEntity="\Archivist\Forum\Post", mappedBy="author", cascade={"persist"})
	 * @var \Archivist\Forum\Post[]
	 */
	protected $posts;



	/**
	 * @return User
	 */
	public function getUser()
	{
		return $this->user;
	}



	/**
	 * @param User $user
	 * @return Identity
	 */
	public function setUser(User $user)
	{
		if ($this->user !== NULL) {
			throw new InvalidStateException();
		}

		$this->user = $user;
		return $this;
	}



	/**
	 * Returns a list of roles that the user is a member of.
	 * @return array|Nette\Security\IRole[]
	 */
	public function getRoles()
	{
		return $this->user->roles;
	}

}