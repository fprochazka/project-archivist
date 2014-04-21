<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Archivist\Users;

use Doctrine;
use Doctrine\ORM\Mapping as ORM;
use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @ORM\Entity(readOnly=TRUE)
 * @ORM\Table(name="roles")
 */
class Role extends Kdyby\Doctrine\Entities\BaseEntity implements Nette\Security\IRole
{

	const USER = 'user';
	const MODERATOR = 'moderator';

	/**
	 * @ORM\Id()
	 * @ORM\Column(type="string", nullable=TRUE)
	 * @var string
	 */
	protected $id;

	/**
	 * @ORM\ManyToMany(targetEntity="Role", cascade={"persist"})
	 * @ORM\JoinTable(name="role_inherits",
	 *        joinColumns={@ORM\JoinColumn(name="role")},
	 *        inverseJoinColumns={@ORM\JoinColumn(name="inherit")}
	 *    )
	 * @var Role[]|\Doctrine\Common\Collections\ArrayCollection
	 */
	protected $inherits;



	/**
	 * @return string
	 */
	final public function getId()
	{
		return $this->id;
	}



	/**
	 * @return string
	 */
	final public function getRoleId()
	{
		return $this->id;
	}

}
