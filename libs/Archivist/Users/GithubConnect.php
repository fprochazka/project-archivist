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
use Kdyby;
use Nette;
use Nette\Utils\Validators;
use Tracy\Debugger;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class GithubConnect extends Nette\Object implements ISocialConnect
{

	/**
	 * @var \Kdyby\Github\Client
	 */
	private $github;

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



	public function __construct(Kdyby\Doctrine\EntityManager $em, Kdyby\Github\Client $github, Manager $manager, UserContext $user)
	{
		$this->em = $em;
		$this->github = $github;
		$this->manager = $manager;
		$this->user = $user;
	}



	/**
	 * @return bool|\Nette\Utils\ArrayHash|NULL
	 * @throws PermissionsNotProvidedException
	 */
	public function readUserData()
	{
		if (!$uid = $this->github->getUser()) {
			throw new PermissionsNotProvidedException();
		}

		try {
			$user = $this->github->api('/user');

			if (empty($user['name'])) {
				if (empty($user['login'])) {
					throw new UnknownUsernameException("User name cannot be resolved.");
				}

				$user['name'] = $user['login'];
			}

			if (empty($user['email'])) {
				$user['email'] = $this->github->getProfile()->getPrimaryEmail();

				if (empty($user['email'])) {
					throw new MissingEmailException("User email cannot be resolved.");
				}
			}

		} catch (\Exception $e) {
			Debugger::log($e, 'github-auth');
			throw new PermissionsNotProvidedException($e->getMessage(), 0, $e);
		}

		return $user;
	}



	public function tryLogin()
	{
		$user = $this->readUserData();

		if ($identity = $this->manager->findOneByGithub($user['id'])) {
			return $this->completeLogin($identity->getUser(), $identity);

		} elseif ($this->user->isLoggedIn()) {
			$user = $this->user->getUserEntity();

			if (!($identity = $user->getIdentity(Github::class)) || $identity->getUid() == $user['id']) {
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
		$this->readUserData(); // ensure the user is loggedin on github
		return $this->completeLogin($this->user->getUserEntity());
	}



	/**
	 * @param string $username
	 * @throws PermissionsNotProvidedException
	 * @throws MissingEmailException
	 * @return bool
	 */
	public function register($username)
	{
		$this->readUserData(); // check permissions

		/** @var \Exception|User $result */
		$result = $this->em->transactional(function () use ($username) {
			try {
				$identity = $this->manager->registerFromGithub($this->github->getProfile());
				$user = $identity->getUser();
				$this->manager->updateUsername($user, $username);

				return $user;

			} catch (\Exception $e) {
				if (!empty($identity)) {
					$this->em->remove($identity);
				}

				if (!empty($user)) {
					$this->em->remove($user);
				}

				return $e;
			}
		});

		if ($result instanceof \Exception) {
			throw $result;
		}

		return $this->completeLogin($result);
	}



	private function completeLogin(User $user, Github $identity = NULL)
	{
		if (!$identity = $identity ?: $user->getIdentity(Github::class)) {
			$identity = new Github($this->github->getProfile());
			$user->addIdentity($identity);
		}

		$identity->updateToken($this->github);
		$this->em->flush();

		if (!$this->user->isLoggedIn() || $this->user->getUserEntity() !== $user) {
			$this->user->login($identity);
			$this->github->setAccessToken($identity->getToken()); // it must be fixed after login
		}

		return TRUE;
	}

}
