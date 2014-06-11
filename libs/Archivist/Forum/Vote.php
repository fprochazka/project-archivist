<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Archivist\Forum;

use Archivist\Users\User;
use Doctrine;
use Doctrine\ORM\Mapping as ORM;
use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @ORM\Entity(readOnly=true)
 * @ORM\Table(name="forum_post_votes", uniqueConstraints={
 * 		@ORM\UniqueConstraint(columns={"user_id", "post_id"})
 * })
 */
class Vote extends Identified
{

	/**
	 * @ORM\ManyToOne(targetEntity="\Archivist\Forum\Post", inversedBy="votes", cascade={"persist"})
	 * @var Post
	 */
	private $post;

	/**
	 * @ORM\ManyToOne(targetEntity="\Archivist\Users\User", inversedBy="votes", cascade={"persist"})
	 * @var User
	 */
	private $user;

	/**
	 * @ORM\Column(type="smallint", nullable=FALSE, options={"default":1})
	 * @var boolean
	 */
	private $points;

	/**
	 * @ORM\Column(type="string", nullable=TRUE)
	 * @var string
	 */
	private $reason;



	public function __construct(Post $post, User $user, $up = TRUE, $reason = NULL)
	{
		$this->post = $post;
		$this->user = $user;
		$this->points = $up ? 1 : -1;
		$this->reason = $reason;
	}



	/**
	 * @return Post
	 */
	public function getPost()
	{
		return $this->post;
	}



	/**
	 * @return string
	 */
	public function getReason()
	{
		return $this->reason;
	}



	/**
	 * @return User
	 */
	public function getUser()
	{
		return $this->user;
	}



	/**
	 * @return bool
	 */
	public function isUp()
	{
		return $this->points > 0;
	}



	/**
	 * @return bool
	 */
	public function isDown()
	{
		return $this->points < 0;
	}

}
