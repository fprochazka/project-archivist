<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Archivist\Users;

use Archivist\Exception;
use Archivist\InvalidArgumentException;
use Archivist\InvalidStateException;
use Nette\Security\AuthenticationException;



class EmailAlreadyTakenException extends InvalidArgumentException
{

}



class UsernameAlreadyTakenException extends InvalidArgumentException
{

}



class UnknownUsernameException extends InvalidArgumentException
{

}



class UserNotFoundException extends AuthenticationException implements Exception
{

}



class EmailIsNotValidException extends InvalidArgumentException
{

}



class PermissionsNotProvidedException extends InvalidStateException
{

}



class AccountConflictException extends InvalidStateException
{

}



class ManualMergeRequiredException extends InvalidStateException
{

}



class MissingEmailException extends InvalidArgumentException
{

}



class PasswordRequiredException extends InvalidArgumentException
{

}
