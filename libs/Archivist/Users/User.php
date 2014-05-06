<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Archivist\Users;

use Archivist\Forum\Identified;
use Archivist\Security\Role;
use Archivist\Users\Identity\EmailPassword;
use Archivist\Users\Identity\Facebook;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine;
use Doctrine\ORM\Mapping as ORM;
use Kdyby;
use Nette;
use Nette\Utils\Strings;



/**
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @ORM\Entity()
 * @ORM\Table(name="users", uniqueConstraints={
 *		@ORM\UniqueConstraint(columns={"name"})
 * })
 *
 * @property string $name
 */
class User extends Identified
{

	/**
	 * @ORM\Column(type="string", nullable=FALSE)
	 * @var string
	 */
	protected $name;

	/**
	 * @ORM\Column(type="string", nullable=TRUE)
	 * @var string
	 */
	private $email;

	/**
	 * @ORM\OneToMany(targetEntity="\Archivist\Users\Identity", mappedBy="user", cascade={"persist"})
	 * @var \Archivist\Users\Identity[]|ArrayCollection
	 */
	protected $identities;

	/**
	 * @ORM\ManyToMany(targetEntity="\Archivist\Security\Role", cascade={"persist"})
	 * @ORM\JoinTable(name="user_roles",
	 *        joinColumns={@ORM\JoinColumn(name="user_id")},
	 *        inverseJoinColumns={@ORM\JoinColumn(name="role")}
	 *    )
	 * @var \Archivist\Security\Role[]|ArrayCollection
	 */
	protected $roles;



	public function __construct($email)
	{
		$this->setEmail($email);
		$this->identities = new ArrayCollection();
		$this->roles = new ArrayCollection();
	}



	public function addIdentity(Identity $identity)
	{
		$this->identities[] = $identity;
		$identity->setUser($this);
	}



	/**
	 * @param string $type
	 * @return NULL|Identity|Facebook|Identity\Github|Identity\Google
	 */
	public function getIdentity($type = NULL)
	{
		if ($type === NULL) {
			return $this->getIdentity(EmailPassword::class)
				?: $this->identities->first();
		}

		return $this->identities->filter(function (Identity $identity) use ($type) {
			return $identity instanceof $type;
		})->first();
	}



	public function addRole(Role $role)
	{
		$this->roles[] = $role;
	}



	/**
	 * @return string
	 */
	public function getEmail()
	{
		return $this->email;
	}



	/**
	 * @param string $email
	 * @return User
	 */
	public function setEmail($email)
	{
		$this->email = Strings::lower($email);
		return $this;
	}

}
