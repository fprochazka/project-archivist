<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Archivist\SignDialog;

use Archivist\Security\UserContext;
use Archivist\UI\BaseControl;
use Archivist\UI\BaseForm;
use Archivist\Users\EmailAlreadyTakenException;
use Archivist\Users\Identity;
use Archivist\Users\Manager;
use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @method onSingIn(SingInControl $self, Identity $identity)
 */
class SingInControl extends BaseControl
{

	/**
	 * @var array
	 */
	public $onSingIn = [];

	/**
	 * @var Manager
	 */
	private $manager;

	/**
	 * @var string
	 */
	private $view = 'default';

	/**
	 * @var \Archivist\Security\UserContext
	 */
	private $user;



	public function __construct(Manager $manager, UserContext $user)
	{
		$this->manager = $manager;
		$this->user = $user;
	}



	/**
	 * @param string $view
	 * @return SingInControl
	 */
	public function setView($view)
	{
		$this->view = $view;
		return $this;
	}



	/**
	 * @param \Nette\ComponentModel\Container $obj
	 */
	protected function attached($obj)
	{
		parent::attached($obj);

		if (!$obj instanceof \Nette\Application\UI\Presenter) {
			return;
		}

		$this->template->renderModal = $this->presenter->isSignalReceiver($this)
			|| $this->presenter->isSignalReceiver($this['signInForm'])
			|| $this->presenter->isSignalReceiver($this['registerForm']);

		$this->redrawControl();
	}



	public function handleShowModal()
	{
		$this->view = 'modal';
		$this->template->renderModal = TRUE;
	}



	public function render()
	{
		$this->template->renderModal = !empty($this->template->renderModal) || FALSE;
		$this->template->setFile(__DIR__ . '/' . $this->view . '.latte')->render();
	}



	/**
	 * Sign-in form factory.
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentSignInForm()
	{
		$form = new BaseForm();

		$form->addText('email', 'Email:')
			->setRequired('Please enter your email.')
			->addRule($form::EMAIL);

		$form->addPassword('password', 'Password:')
			->setRequired('Please enter your password.');

		$form->addCheckbox('remember', 'Keep me signed in')
			->setDefaultValue(TRUE);

		$form->addSubmit('signIn', 'Sign in');
		$form->addSubmit('register', 'Register');

		// call method signInFormSucceeded() on success
		$form->onSuccess[] = function (Baseform $form, $values) {
			if ($values->remember) {
				$this->user->setExpiration('14 days', FALSE);
			} else {
				$this->user->setExpiration('2 hours', TRUE);
			}

			if ($form->isSubmitted() === $form['register']) {
				try {
					$this->user->login($this->manager->registerWithPassword($values->email, $values->password));
					$this->onSingIn($this, $this->user->getIdentity());

				} catch (EmailAlreadyTakenException $e) {
					try {
						$this->user->login($values->email, $values->password);
						$this->onSingIn($this, $this->user->getIdentity());
						return;
					} catch (Nette\Security\AuthenticationException $e) { }

					$form->addError("Account with this email already exists");
					return;
				}
			}

			try {
				$this->user->login($values->email, $values->password);
				$this->onSingIn($this, $this->user->getIdentity());

			} catch (Nette\Security\AuthenticationException $e) {
				$form->addError($e->getMessage());
			}
		};

		$form->setupBootstrap3Rendering();
		return $form;
	}



	/**
	 * @return BaseForm
	 */
	protected function createComponentRegisterForm()
	{
		$form = new BaseForm();
		$form->addText('email', 'Email:')->addRule($form::EMAIL);
		$form->addPassword('password', 'Password:');

		$form->addSubmit("send", "Register");
		$form->onSuccess[] = function (BaseForm $form, $values) {
			try {
				$this->user->login($this->manager->registerWithPassword($values->email, $values->password));
				$this->onSingIn($this, $this->user->getIdentity());

			} catch (EmailAlreadyTakenException $e) {
				$form->addError("Account with this email already exists");
				return;
			}
		};

		$form->setupBootstrap3Rendering();
		return $form;
	}

}



interface ISingInControlFactory
{

	/** @return SingInControl */
	function create();
}
