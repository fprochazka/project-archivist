<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Archivist\Users\Identity;

use Archivist\Users\Identity;
use Doctrine;
use Doctrine\ORM\Mapping as ORM;
use Kdyby;
use Nette;
use Nette\Security\Passwords;



/**
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @ORM\Entity()
 */
class EmailPassword extends Identity
{

	/**
	 * @ORM\Column(type="string", nullable=TRUE)
	 * @var string
	 */
	protected $email;

	/**
	 * @ORM\Column(type="string", length=60, nullable=TRUE)
	 * @var string
	 */
	protected $password;

	/**
	 * @ORM\Column(type="boolean", nullable=FALSE, options={"default":"0"})
	 * @var boolean
	 */
	protected $invalid = FALSE;



	public function __construct($email, $rawPassword)
	{
		$this->email = $email;
		$this->hashPassword($rawPassword);
	}



	/**
	 * @param string $rawPassword
	 * @return bool
	 */
	public function verifyPassword($rawPassword)
	{
		$verified = Passwords::verify($rawPassword, $this->password);

		if ($verified && Passwords::needsRehash($this->password)) {
			$this->hashPassword($rawPassword);
		}

		return $verified;
	}



	private function hashPassword($rawPassword)
	{
		$this->password = Passwords::hash($rawPassword, ['cost' => 10]);
	}

}
