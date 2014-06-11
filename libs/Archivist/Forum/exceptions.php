<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Archivist\Forum;


use Archivist\InsufficientPermissionsException;
use Archivist\InvalidArgumentException;
use Archivist\InvalidStateException;



class PostIsNotReadableException extends InvalidStateException
{

}

class PostQuestionCollisionException extends InvalidArgumentException
{

}

class ModificationsNotAllowedException extends InsufficientPermissionsException
{

}

class ThreadLockedException extends InsufficientPermissionsException
{

}

class CannotVoteOnOwnPostException extends InsufficientPermissionsException
{

}
