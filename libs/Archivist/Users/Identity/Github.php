<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Archivist\Users\Identity;

use Archivist\InvalidArgumentException;
use Archivist\Users\Identity;
use Doctrine;
use Doctrine\ORM\Mapping as ORM;
use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @ORM\Entity()
 */
class Github extends Identity
{

	/**
	 * @ORM\Column(type="string", nullable=TRUE)
	 * @var string
	 */
	protected $uid;

	/**
	 * @ORM\Column(type="text", nullable=TRUE)
	 * @var string
	 */
	protected $token;



	public function __construct(Kdyby\Github\Profile $profile)
	{
		$this->uid = $profile->getId();
		$this->setEmail($profile->getDetails('email'));

		if (!$this->getEmail() || !$this->uid) {
			throw new InvalidArgumentException();
		}

		$this->verified = TRUE;
	}



	/**
	 * @return string
	 */
	public function getUid()
	{
		return $this->uid;
	}



	/**
	 * @return string
	 */
	public function getToken()
	{
		return $this->token;
	}



	public function updateToken(Kdyby\Github\Client $github)
	{
		$this->token = $github->getAccessToken();
	}

}
