<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Archivist\Users;

use Archivist\NotImplementedException;
use Archivist\Security\UserContext;
use Archivist\UnexpectedValueException;
use Archivist\Users\Identity\Facebook;
use Kdyby;
use Kdyby\Facebook as Fb;
use Nette;
use Nette\Utils\Validators;
use Tracy\Debugger;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class FacebookConnect extends Nette\Object
{

	/**
	 * @var Fb\Facebook
	 */
	private $facebook;

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



	public function __construct(Kdyby\Doctrine\EntityManager $em, Fb\Facebook $facebook, Manager $manager, UserContext $user)
	{
		$this->facebook = $facebook;
		$this->manager = $manager;
		$this->user = $user;
		$this->em = $em;
	}



	/**
	 * @return bool|\Nette\Utils\ArrayHash|NULL
	 * @throws PermissionsNotProvidedException
	 */
	public function readUserData()
	{
		if (!$fbUid = $this->facebook->getUser()) {
			throw new PermissionsNotProvidedException();
		}

		try {
			$user = $this->facebook->api('/me');

			if (empty($user['email']) || empty($user['name'])) {
				throw new UnexpectedValueException("Missing important information");
			}

		} catch (Fb\FacebookApiException $e) {
			Debugger::log($e, 'facebook-auth');
			throw new PermissionsNotProvidedException($e->getMessage(), 0, $e);
		}

		return $user;
	}



	/**
	 * @throws PermissionsNotProvidedException
	 * @throws ManualMergeRequiredException
	 * @throws AccountConflictException
	 * @return bool
	 */
	public function tryLogin()
	{
		$fbUser = $this->readUserData();

		if ($identity = $this->manager->findOneByFacebook($fbUser['id'])) {
			return $this->completeLogin($identity->getUser());

		} elseif ($this->user->isLoggedIn()) {
			$user = $this->user->getUserEntity();

			if (!($identity = $user->getIdentity(Facebook::class)) || $identity->getUid() == $fbUser['id']) {
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
		$this->readUserData(); // ensure the user is loggedin on facebook
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

		$identity = $this->manager->registerFromFacebook($this->facebook->getProfile());
		$user = $identity->getUser();
		$user->name = $username;

		return $this->completeLogin($user);
	}



	/**
	 * @param User $user
	 * @return bool
	 */
	private function completeLogin(User $user)
	{
		if (!$identity = $user->getIdentity(Facebook::class)) {
			$identity = new Facebook($this->facebook->getProfile());
			$user->addIdentity($identity);
		}

		$identity->updateToken($this->facebook);
		$this->em->flush();

		$this->user->login($identity);
		$this->facebook->setAccessToken($identity->getToken()); // it must be fixed after login

		return TRUE;
	}

}
