<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Archivist\Users;

use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
interface ISocialConnect
{

	/**
	 * @throws PermissionsNotProvidedException
	 * @return \Nette\Utils\ArrayHash|NULL
	 */
	function readUserData();



	/**
	 * Method calls the API of the social network at the background and tries to login the user.
	 * If the session with token is present, the user should be logged in.
	 *
	 * Otherwise, exception signalising missing permission should be thrown,
	 * meaning that the user have to be redirected to the social network's oauth connector to allow the app.
	 *
	 * @throws PermissionsNotProvidedException
	 * @throws ManualMergeRequiredException
	 * @return NULL
	 */
	function tryLogin();



	/**
	 * Accepts the desired username and tries if authorization token of social network exists in session.
	 * Throws exceptions if the username is already taken, if the email is already taken, or if the email cannot be
	 * fetched from the social network's api.
	 *
	 * If the process finishes successfully, the user is registered and logged in afterwards.
	 *
	 * @param string $username
	 * @throws PermissionsNotProvidedException
	 * @throws EmailAlreadyTakenException
	 * @throws UsernameAlreadyTakenException
	 * @throws MissingEmailException
	 * @return bool
	 */
	function register($username);



	/**
	 * Tries to log in the user and if it works out, then binds the profile to his account.
	 *
	 * @param string $email
	 * @param string $password
	 * @throws PermissionsNotProvidedException
	 * @throws Nette\Security\AuthenticationException
	 * @return bool
	 */
	function mergeAndLogin($email, $password);

}
