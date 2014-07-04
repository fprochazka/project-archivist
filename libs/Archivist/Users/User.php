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
use Archivist\Forum\Vote;
use Archivist\Security\Role;
use Archivist\Users\Identity\EmailPassword;
use Archivist\Users\Identity\Facebook;
use Archivist\Users\Identity\Github;
use Archivist\Users\Identity\Google;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby;
use Kdyby\Doctrine\Collections\ReadOnlyCollectionWrapper;
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
	 * @ORM\Column(type="string", nullable=TRUE)
	 * @var string
	 */
	protected $name;

	/**
	 * @ORM\Column(type="string", nullable=TRUE)
	 * @var string
	 */
	private $email;

	/**
	 * @ORM\OneToMany(targetEntity="\Archivist\Users\Identity", mappedBy="user", orphanRemoval=true, cascade={"persist"})
	 * @var \Archivist\Users\Identity[]|ArrayCollection
	 */
	protected $identities;

	/**
	 * @ORM\ManyToMany(targetEntity="\Archivist\Security\Role", cascade={"persist"})
	 * @ORM\JoinTable(name="user_roles",
	 *        joinColumns={@ORM\JoinColumn(name="user_id", onDelete="cascade")},
	 *        inverseJoinColumns={@ORM\JoinColumn(name="role")}
	 *    )
	 * @var \Archivist\Security\Role[]|ArrayCollection
	 */
	protected $roles;

	/**
	 * @ORM\OneToMany(targetEntity="\Archivist\Forum\Vote", mappedBy="user", cascade={"persist"})
	 * @var Vote
	 */
	protected $votes;

	/**
	 * @ORM\OneToMany(targetEntity="\Archivist\Forum\Post", mappedBy="user", cascade={"persist"})
	 * @var Vote
	 */
	protected $posts;

	/**
	 * @ORM\Column(type="datetime", nullable=FALSE)
	 * @var \DateTime
	 */
	private $createdAt;



	public function __construct($email)
	{
		$this->setEmail($email);
		$this->identities = new ArrayCollection();
		$this->roles = new ArrayCollection();
		$this->votes = new ArrayCollection();
		$this->createdAt = new \DateTime();
	}



	public function changeActiveEmail($email)
	{
		if ($old = $this->getIdentityWithEmail($email)) { // activate existing identity
			if ($active = $this->getIdentity(EmailPassword::class)) {
				$active->invalidate();
			}

			$old->invalid = FALSE;

		} elseif ($active = $this->getIdentity(EmailPassword::class)) { // copy current password
			$new = clone $active;
			$this->addIdentity($new->setEmail($email));

		} else { // no existing identity? whatever...

		}

		$this->setEmail($email);

		return $this;
	}



	public function changePassword($newPassword, $oldPassword = NULL)
	{
		$active = $this->getIdentity(EmailPassword::class);
		if ($active && $oldPassword !== NULL && !$active->verifyPassword($oldPassword)) {
			throw new Nette\Security\AuthenticationException();
		}

		if ($active) {
			foreach ($this->getIdentities(EmailPassword::class) as $identity) {
				$identity->changePassword($newPassword);
			}

		} else {
			$this->addIdentity(new EmailPassword($this->email, $newPassword));
		}
	}



	public function addIdentity(Identity $identity)
	{
		if ($old = $this->getIdentity(get_class($identity))) {
			$old->invalidate(); // disable the old one
		}

		if ($identity instanceof EmailPassword) {
			$this->email = $identity->getEmail();
		}

		$this->identities[] = $identity;
		$identity->setUser($this);
	}



	/**
	 * Returns only one currently valid identity
	 *
	 * @param string $type
	 * @return NULL|Identity|Facebook|Identity\Github|Identity\Google|Identity\EmailPassword
	 */
	public function getIdentity($type = NULL)
	{
		$type = $type ?: EmailPassword::class;

		return $this->getIdentities($type)
			->filter(function (Identity $identity) { return $identity->invalid === FALSE; })
			->first();
	}



	/**
	 * Returns all identities, even the invalid ones
	 *
	 * @param string $type
	 * @return Collection|Identity|Facebook[]|Identity\Github[]|Identity\Google[]|Identity\EmailPassword[]
	 */
	public function getIdentities($type = NULL)
	{
		if ($type === NULL) {
			return new ReadOnlyCollectionWrapper($this->identities);
		}

		$identities = $this->identities
			->filter(function (Identity $identity) use ($type) { return $identity instanceof $type; });

		return new ReadOnlyCollectionWrapper($identities);
	}



	/**
	 * @param string $uid
	 * @return EmailPassword|NULL
	 */
	public function getFacebookIdentity($uid = NULL)
	{
		if ($uid === NULL) {
			return $this->getIdentity(Facebook::class);
		}

		return $this->getIdentities(Facebook::class)
			->filter(function (Facebook $identity) use ($uid) { return $identity->getUid() == $uid; })
			->first();
	}



	/**
	 * @param string $uid
	 * @return EmailPassword|NULL
	 */
	public function getGithubIdentity($uid = NULL)
	{
		if ($uid === NULL) {
			return $this->getIdentity(Github::class);
		}

		return $this->getIdentities(Github::class)
			->filter(function (Github $identity) use ($uid) { return $identity->getUid() == $uid; })
			->first();
	}



	/**
	 * @param string $uid
	 * @return EmailPassword|NULL
	 */
	public function getGoogleIdentity($uid = NULL)
	{
		if ($uid === NULL) {
			return $this->getIdentity(Google::class);
		}

		return $this->getIdentities(Google::class)
			->filter(function (Google $identity) use ($uid) { return $identity->getUid() == $uid; })
			->first();
	}



	/**
	 * @param string $email
	 * @return EmailPassword|NULL
	 */
	public function getIdentityWithEmail($email)
	{
		return $this->getIdentities(EmailPassword::class)
			->filter(function (EmailPassword $identity) use ($email) { return strcasecmp($identity->getEmail(), $email) == 0; })
			->first();
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
	protected function setEmail($email)
	{
		$this->email = Strings::lower($email);
		return $this;
	}

}
