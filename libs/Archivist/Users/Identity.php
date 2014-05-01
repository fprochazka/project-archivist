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
use Archivist\InvalidStateException;
use Doctrine;
use Doctrine\ORM\Mapping as ORM;
use Kdyby;
use Nette;
use Nette\Utils\Strings;



/**
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @ORM\Entity()
 * @ORM\Table(name="user_identities", uniqueConstraints={
 * 		@ORM\UniqueConstraint(columns={"type", "email"})
 * })
 *
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string", length=10)
 * @ORM\DiscriminatorMap({
 *    "base" = "Archivist\Users\Identity",
 *    "password" = "Archivist\Users\Identity\EmailPassword",
 *    "github" = "Archivist\Users\Identity\Github",
 *    "google" = "Archivist\Users\Identity\Google",
 *    "facebook" = "Archivist\Users\Identity\Facebook",
 *    "twitter" = "Archivist\Users\Identity\Twitter"
 * })
 *
 * @property-read string $name
 */
abstract class Identity extends Identified implements Nette\Security\IIdentity
{

	/**
	 * @ORM\ManyToOne(targetEntity="\Archivist\Users\User", inversedBy="identities", cascade={"persist"})
	 * @ORM\JoinColumn(nullable=FALSE)
	 * @var User
	 */
	private $user;

	/**
	 * @ORM\Column(type="string", nullable=TRUE)
	 * @var string
	 */
	private $email;

	/**
	 * @ORM\OneToMany(targetEntity="\Archivist\Forum\Post", mappedBy="author", cascade={"persist"})
	 * @var \Archivist\Forum\Post[]
	 */
	protected $posts;

	/**
	 * @ORM\Column(type="boolean", nullable=FALSE, options={"default":"0"})
	 * @var boolean
	 */
	protected $invalid = FALSE;

	/**
	 * @ORM\Column(type="boolean", nullable=FALSE, options={"default":"0"})
	 * @var boolean
	 */
	protected $verified = FALSE;



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
	 * @return string
	 */
	public function getEmail()
	{
		return $this->email;
	}



	/**
	 * @param string $email
	 * @return Identity
	 */
	public function setEmail($email)
	{
		$this->email = Strings::lower($email);
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



	public function &__get($name)
	{
		if (!property_exists($this, $name) && property_exists($this->user, $name)) {
			$tmp = $this->getUser()->{$name};
			return $tmp;
		}

		return parent::__get($name);
	}



	/**
	 * @return boolean
	 */
	public function isVerified()
	{
		return $this->verified;
	}

}
