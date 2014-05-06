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
use Archivist\Users\Identity\Facebook;
use Archivist\Users\Identity\Github;
use Archivist\Users\Identity\Google;
use Doctrine\ORM\NoResultException;
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
	private $facebookIdentities;

	/**
	 * @var \Kdyby\Doctrine\EntityDao
	 */
	private $githubIdentities;

	/**
	 * @var \Kdyby\Doctrine\EntityDao
	 */
	private $googleIdentities;

	/**
	 * @var \Kdyby\Doctrine\EntityDao
	 */
	private $roles;



	public function __construct(Kdyby\Doctrine\EntityManager $em)
	{
		$this->em = $em;
		$this->users = $em->getDao(User::class);
		$this->passwordIdentities = $em->getDao(EmailPassword::class);
		$this->facebookIdentities = $em->getDao(Facebook::class);
		$this->githubIdentities = $em->getDao(Github::class);
		$this->googleIdentities = $em->getDao(Google::class);
		$this->roles = $em->getDao(Role::class);
	}



	/**
	 * @param string $email
	 * @param string $password
	 * @param string $username
	 * @throws UsernameAlreadyTakenException
	 * @return \Archivist\Users\Identity\EmailPassword
	 */
	public function registerWithPassword($email, $password, $username)
	{
		if ($this->users->findOneBy(['email' => $email]) || $this->passwordIdentities->findOneBy(['email' => $email])) {
			throw new EmailAlreadyTakenException();
		}

		$user = new User($email);
		$this->updateUsername($user, $username);

		$user->addIdentity($identity = new EmailPassword($email, $password));
		$user->addRole($this->roles->find(Role::USER));

		$this->em->persist($user)->flush();

		return $identity;
	}



	/**
	 * @param Kdyby\Facebook\Profile $profile
	 * @return Facebook
	 */
	public function registerFromFacebook(Kdyby\Facebook\Profile $profile)
	{
		$email = $profile->getDetails('email');

		if ($this->users->findOneBy(['email' => $email]) || ($identity = $this->identityWithEmailExists($email))) {
			if (!empty($identity) && (!$identity instanceof Facebook || $identity->getUid() != $profile->getId())) {
				throw new EmailAlreadyTakenException();
			}
		}

		if (empty($identity)) {
			$user = new User($email);
			$user->addIdentity($identity = new Facebook($profile));
			$user->addRole($this->roles->find(Role::USER));

			$this->em->persist($user)->flush();
		}

		return $identity;
	}



	/**
	 * @param Kdyby\Github\Profile $profile
	 * @return Identity|Github|NULL
	 */
	public function registerFromGithub(Kdyby\Github\Profile $profile)
	{
		$email = $profile->getDetails('email');

		if ($this->users->findOneBy(['email' => $email]) || ($identity = $this->identityWithEmailExists($email))) {
			if (!empty($identity) && (!$identity instanceof Github || $identity->getUid() != $profile->getId())) {
				throw new EmailAlreadyTakenException();
			}
		}

		if (empty($identity)) {
			$user = new User($email);
			$user->addIdentity($identity = new Github($profile));
			$user->addRole($this->roles->find(Role::USER));

			$this->em->persist($user)->flush();
		}

		return $identity;
	}



	/**
	 * @param \Google_Service_Oauth2_Userinfoplus $profile
	 * @return Identity|Google|NULL
	 */
	public function registerFromGoogle(\Google_Service_Oauth2_Userinfoplus $profile)
	{
		$email = $profile->getEmail();

		if ($this->users->findOneBy(['email' => $email]) || ($identity = $this->identityWithEmailExists($email))) {
			if (!empty($identity) && (!$identity instanceof Google || $identity->getUid() != $profile->getId())) {
				throw new EmailAlreadyTakenException();
			}
		}

		if (empty($identity)) {
			$user = new User($email);
			$user->addIdentity($identity = new Google($profile));
			$user->addRole($this->roles->find(Role::USER));

			$this->em->persist($user)->flush();
		}

		return $identity;
	}



	/**
	 * @param User $user
	 * @param string $username
	 * @throws UsernameAlreadyTakenException
	 * @return User
	 */
	public function updateUsername(User $user, $username)
	{
		$usernameQuery = $this->users->createQueryBuilder('u')
			->andWhere('LOWER(u.name) = :username')->setParameter('username', Nette\Utils\Strings::lower($username))
			->getQuery()
			->setMaxResults(1);

		try {
			if ($isTaken = $usernameQuery->getSingleResult()) {
				throw new UsernameAlreadyTakenException();
			}
		} catch (NoResultException $e) {
		}

		$user->name = $username;
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
			throw new UserNotFoundException('User not found', self::IDENTITY_NOT_FOUND);
		}

		if (!$identity->verifyPassword($credentials[self::PASSWORD])) {
			throw new Nette\Security\AuthenticationException('Invalid password', self::INVALID_CREDENTIAL);
		}

		$this->em->flush(); // save new password if it was regenerated

		return $identity;
	}



	/**
	 * @param int $id
	 * @return null|object
	 */
	public function find($id)
	{
		return $this->users->find($id);
	}



	/**
	 * @param int $id
	 * @return Facebook|NULL
	 */
	public function findOneByFacebook($id)
	{
		return $this->facebookIdentities->findOneBy(['uid' => $id]);
	}



	/**
	 * @param int $id
	 * @return Github|NULL
	 */
	public function findOneByGithub($id)
	{
		return $this->githubIdentities->findOneBy(['uid' => $id]);
	}



	/**
	 * @param int $id
	 * @return Google|NULL
	 */
	public function findOneByGoogle($id)
	{
		return $this->googleIdentities->findOneBy(['uid' => $id]);
	}



	/**
	 * @param string $email
	 * @return Identity|NULL
	 * @throws \Doctrine\ORM\NonUniqueResultException
	 */
	public function identityWithEmailExists($email)
	{
		$emailsQuery = $this->em->getDao(Identity::class)->createQueryBuilder('i')
			->where('i.email = :email')->setParameter('email', Nette\Utils\Strings::lower($email))
			->getQuery();

		try {
			return $emailsQuery->setMaxResults(1)->getSingleResult();

		} catch (NoResultException $e) {
			return NULL;
		}
	}

}
