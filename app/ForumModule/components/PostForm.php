<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Archivist\ForumModule;

use Archivist\Security\UserContext;
use Archivist\UI\BaseForm;
use Kdyby;
use Nette;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Controls\TextInput;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class PostForm extends BaseForm
{

	/**
	 * @var UserContext
	 */
	private $user;



	public function __construct(UserContext $user)
	{
		parent::__construct();
		$this->user = $user;

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
	}



	/**
	 * @param string $defaultValue
	 * @return TextInput
	 */
	public function addTitle($defaultValue = NULL)
	{
		$this->addComponent($title = new TextInput('Topic'), 'title', 'content');
		$title->setDefaultValue($defaultValue)->setRequired();
		return $title;
	}

}



interface IPostFormFactory
{

	/** @return PostForm */
	function create();
}
