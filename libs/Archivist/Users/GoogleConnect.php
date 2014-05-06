<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Archivist\Users;

use Archivist\Security\UserContext;
use Archivist\UnexpectedValueException;
use Archivist\Users\Identity\Github;
use Archivist\Users\Identity\Google;
use Kdyby;
use Nette;
use Nette\Utils\Validators;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class GoogleConnect extends Nette\Object
{

	/**
	 * @var \Kdyby\Google\Google
	 */
	private $google;

	/**
	 * @var Manager
	 */
	private $manager;

	/**
	 * @var \Archivist\Security\UserContext
	 */
	private $user;

	/**
	 * @var \Kdyby\Doctrine\EntityManager
	 */
	private $em;



	public function __construct(Kdyby\Doctrine\EntityManager $em, Kdyby\Google\Google $google, Manager $manager, UserContext $user)
	{
		$this->em = $em;
		$this->google = $google;
		$this->manager = $manager;
		$this->user = $user;
	}



	/**
	 * @return bool|\Nette\Utils\ArrayHash|NULL
	 * @throws PermissionsNotProvidedException
	 */
	public function readUserData()
	{
		if (!$uid = $this->google->getUser()) {
			throw new PermissionsNotProvidedException();
		}

		try {
			if (!$user = $this->google->getProfile()) {
				throw new UnexpectedValueException();
			}

		} catch (\Exception $e) {
			throw new PermissionsNotProvidedException($e->getMessage(), 0, $e);
		}

		return Nette\Utils\ArrayHash::from($user->toSimpleObject());
	}



	public function tryLogin()
	{
		$user = $this->readUserData();

		if ($identity = $this->manager->findOneByGoogle($user['id'])) {
			return $this->completeLogin($identity->getUser());

		} elseif ($this->user->isLoggedIn()) {
			$user = $this->user->getUserEntity();

			if (!($identity = $user->getIdentity(Google::class)) || $identity->getUid() == $user['id']) {
				return $this->completeLogin($user);
			}

			throw new AccountConflictException();
		}

		throw new ManualMergeRequiredException();
	}



	/**
	 * @param string $email
	 * @param string $password
	 * @throws PermissionsNotProvidedException
	 * @throws \Nette\Security\AuthenticationException
	 * @return bool
	 */
	public function mergeAndLogin($email, $password)
	{
		$this->user->login($email, $password);
		$this->readUserData(); // ensure the user is loggedin on google
		return $this->completeLogin($this->user->getUserEntity());
	}



	/**
	 * @param string $email
	 * @param string $username
	 * @throws PermissionsNotProvidedException
	 * @throws MissingEmailException
	 * @return bool
	 */
	public function registerWithProvidedEmail($email, $username)
	{
		$fbUser = $this->readUserData();

		if (empty($fbUser['email'])) {
			if ((empty($email) || !Validators::isEmail($email))) {
				throw new MissingEmailException();
			}

			$fbUser['email'] = $email;
		}

		$identity = $this->manager->registerFromGoogle($this->google->getProfile());
		$user = $identity->getUser();
		$user->name = $username;

		return $this->completeLogin($user);
	}



	private function completeLogin(User $user)
	{
		if (!$identity = $user->getIdentity(Google::class)) {
			$identity = new Google($this->google->getProfile());
			$user->addIdentity($identity);
		}

		$identity->updateToken($this->google);
		$this->em->flush();

		$this->user->login($identity);
		$this->google->setAccessToken($identity->getToken()); // it must be fixed after login

		return TRUE;
	}

}
