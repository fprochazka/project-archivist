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
	 * @ORM\Column(type="text", name="token", nullable=TRUE)
	 * @var string
	 */
	protected $password;



	public function __construct($email, $rawPassword)
	{
		parent::__construct();

		$this->setEmail($email);
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



	public function changePassword($newPassword, $oldPassword = NULL)
	{
		if ($oldPassword !== NULL && !$this->verifyPassword($oldPassword)) {
			throw new Nette\Security\AuthenticationException();
		}

		$this->hashPassword($newPassword);

		return $this;
	}



	private function hashPassword($rawPassword)
	{
		$this->password = Passwords::hash($rawPassword, ['cost' => 10]);
	}

}
