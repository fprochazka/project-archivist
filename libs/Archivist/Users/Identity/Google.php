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
use Nette\Utils\Json;



/**
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @ORM\Entity()
 */
class Google extends Identity
{

	/**
	 * @ORM\Column(type="string", nullable=TRUE)
	 * @var string
	 */
	protected $uid;

	/**
	 * @ORM\Column(type="string", nullable=TRUE)
	 * @var string
	 */
	protected $token;



	public function __construct(\Google_Service_Oauth2_Userinfoplus $profile)
	{
		parent::__construct();

		$this->uid = $profile->getId();
		$this->setEmail($profile->getEmail());

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



	public function updateToken(Kdyby\Google\Google $google)
	{
		$token = $google->getAccessToken();
		$previous = $this->token ? Json::decode($this->token, Json::FORCE_ARRAY) : array();

		if (!isset($token['id_token']) && isset($previous['id_token'])) {
			$token['id_token'] = $previous['id_token'];
		}

		if (!isset($token['refresh_token']) && isset($previous['refresh_token'])) {
			$token['refresh_token'] = $previous['refresh_token'];
		}

		$this->token = Json::encode($token);
		$this->invalid = FALSE;
	}



	/**
	 * @return Google
	 */
	public function invalidate()
	{
		$this->token = NULL;
		return parent::invalidate();
	}

}
