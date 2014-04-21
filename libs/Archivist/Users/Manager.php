<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Archivist\Users;

use Archivist\Security\Role;
use Archivist\Users\Identity\EmailPassword;
use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class Manager extends Nette\Object implements Nette\Security\IAuthenticator
{

	/**
	 * @var \Kdyby\Doctrine\EntityManager
	 */
	private $em;

	/**
	 * @var \Kdyby\Doctrine\EntityDao
	 */
	private $users;

	/**
	 * @var \Kdyby\Doctrine\EntityDao
	 */
	private $passwordIdentities;

	/**
	 * @var \Kdyby\Doctrine\EntityDao
	 */
	private $roles;



	public function __construct(Kdyby\Doctrine\EntityManager $em)
	{
		$this->em = $em;
		$this->users = $em->getDao(User::class);
		$this->passwordIdentities = $em->getDao(EmailPassword::class);
		$this->roles = $em->getDao(Role::class);
	}



	/**
	 * @param string $email
	 * @param string $password
	 * @return \Archivist\Users\Identity\EmailPassword
	 */
	public function registerWithPassword($email, $password)
	{
		$user = new User($email);
		$user->addIdentity($identity = new EmailPassword($email, $password));
		$user->addRole($this->roles->find(Role::USER));

		$this->users->add($user);
		$this->em->flush();

		return $identity;
	}



	/**
	 * @param array $credentials
	 * @return EmailPassword|Nette\Security\IIdentity
	 * @throws \Nette\Security\AuthenticationException
	 */
	public function authenticate(array $credentials)
	{
		/** @var EmailPassword $identity */
		if (!$identity = $this->passwordIdentities->findOneBy(['email' => $credentials[self::USERNAME]])) {
			throw new Nette\Security\AuthenticationException("User not found", self::IDENTITY_NOT_FOUND);
		}

		if (!$identity->verifyPassword($credentials[self::PASSWORD])) {
			throw new Nette\Security\AuthenticationException("User not found", self::INVALID_CREDENTIAL);
		}

		return $identity;
	}

}
