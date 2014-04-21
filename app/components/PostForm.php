<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Archivist;

use Archivist\Security\UserContext;
use Archivist\UI\BaseForm;
use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class PostForm extends BaseForm
{

	/**
	 * @var Security\UserContext
	 */
	private $user;



	public function __construct(UserContext $user)
	{
		parent::__construct();
		$this->user = $user;

		$this->addText('username', 'Your name')
			->setDefaultValue($this->user->loggedIn ? $this->user->getIdentity()->name : '')
			->setRequired();

		$this->addTextArea('content', 'Question')
			->setAttribute('rows', 10)
			->setRequired();

		$this->addSubmit("send", "Post");

		$this->onValidate[] = function (PostForm $form) {
			if (!$this->user->isLoggedIn()) {
				$form->addError("Please login first before posting");
				return;
			}
		};

		$this->onSuccess[] = function (PostForm $form, $values) {
			$identity = $this->user->getIdentity();
			$user = $identity->getUser();
			$user->name = $values->username;
		};


	}

}



interface IPostFormFactory
{

	/** @return PostForm */
	function create();
}
