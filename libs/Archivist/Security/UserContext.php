<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Archivist\Security;

use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @method \Archivist\Users\Identity getIdentity()
 */
class UserContext extends Nette\Security\User
{

	public function &__get($name)
	{
		if ($identity = $this->getIdentity()) {
			if (property_exists($identity, $name)) {
				$tmp = $identity->{$name};
				return $tmp;

			} elseif (property_exists($identityUser = $identity->getUser(), $name)) {
				$tmp = $identityUser->{$name};
				return $tmp;
			}
		}

		return parent::__get($name);
	}

}
