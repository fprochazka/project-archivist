<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Archivist\SignDialog;

use app\components\SignDialog\SecuredFacebookLoginDialog;
use Archivist\Security\UserContext;
use Archivist\UI\BaseControl;
use Archivist\UI\BaseForm;
use Archivist\Users\AccountConflictException;
use Archivist\Users\EmailAlreadyTakenException;
use Archivist\Users\FacebookConnect;
use Archivist\Users\Identity;
use Archivist\Users\Manager;
use Archivist\Users\ManualMergeRequiredException;
use Archivist\Users\MissingEmailException;
use Archivist\Users\PermissionsNotProvidedExceptions;
use Kdyby;
use Kdyby\Facebook\Dialog\LoginDialog;
use Kdyby\Facebook\Facebook;
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

	/**
	 * @var Facebook
	 */
	private $facebook;

	/**
	 * @var \Archivist\Users\FacebookConnect
	 */
	private $facebookConnect;

	/**
	 * @var \Nette\Http\IResponse
	 */
	private $httpResponse;



	public function __construct(Manager $manager, UserContext $user, Nette\Http\IResponse $httpResponse,
		Facebook $facebook, FacebookConnect $facebookConnect)
	{
		$this->manager = $manager;
		$this->user = $user;
		$this->facebook = $facebook;
		$this->facebookConnect = $facebookConnect;
		$this->httpResponse = $httpResponse;
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

		$this->template->renderModal = $this->isSignalReceiver();
		$this->redrawControl();
	}



	public function handleShowModal()
	{
		$this->view = 'modal';
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
			$this->user->setExpiration($values->remember ? '14 days' : '2 hours', FALSE);

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



	public function handleFacebookConnect()
	{
		/** @var LoginDialog $dialog */
		$dialog = $this['facebook'];

		try {
			$this->facebookConnect->tryLogin();

			if ($this->user->isLoggedIn() && $this->facebook->getUser()) {
				$this->getPresenter()->flashMessage('front.login.facebook.success', 'success');
				$this->onSingIn($this, $this->user->getIdentity());
			}

		} catch (PermissionsNotProvidedExceptions $e) {
			$dialog->open();

		} catch (AccountConflictException $e) {
			$this->view = 'facebook/connect';

		} catch (ManualMergeRequiredException $e) {
			$this->view = 'facebook/connect';
		}
	}



	protected function createComponentFacebook()
	{
		$dialog = new SecuredFacebookLoginDialog($this->facebook);
		$dialog->onResponse[] = function (LoginDialog $dialog) {
			try {
				$this->facebookConnect->tryLogin();

				if (!$this->user->isLoggedIn()) {
					$this->getPresenter()->flashMessage('front.login.facebook.failed', 'danger');

				} else {
					$this->getPresenter()->flashMessage('front.login.facebook.success', 'success');
				}

				$this->onSingIn($this, $this->user->getIdentity());

			} catch (PermissionsNotProvidedExceptions $e) {
				$this->getPresenter()->flashMessage('front.login.facebook.permission.missingEmail', 'info');
				return;

			} catch (AccountConflictException $e) {
				$this->redirect('facebookConnect!');

			} catch (ManualMergeRequiredException $e) {
				$this->redirect('facebookConnect!');
			}
		};

		return $dialog;
	}



	/**
	 * @return BaseForm
	 */
	protected function createComponentMergeWithFacebook()
	{
		/** @var BaseForm|Nette\Forms\Controls\BaseControl[] $form */
		$form = new BaseForm();

		$form->addText('username', 'Your name')
			->setRequired();

		$form->addCheckbox('merge', "I have an existing account that I'd like to connect");

		$form->addText('email', 'Email')
			->addConditionOn($form['merge'], $form::EQUAL, TRUE)
				->addRule($form::FILLED, "To be able to merge two accounts, you must fill in your current email")
				->addRule($form::EMAIL);

		$form->addPassword('password', 'Password')
			->addConditionOn($form['merge'], $form::EQUAL, TRUE)
				->addRule($form::FILLED, "To be able to merge two accounts, you must fill in your current password");

		$profile = NULL;
		try {
			$profile = $this->facebookConnect->readUserData();

		} catch (PermissionsNotProvidedExceptions $e) {
			$error = "The required facebook permissions were not provided, you have to allow us to access your profile before logging in.";

			if (!$this->httpResponse->isSent()) {
				$this->getPresenter()->flashMessage($error, 'warning');
				$this->redirect('facebookConnect!');
			}

			$form->addError($error);
		}

		$form->onAttached[] = function (BaseForm $form) use ($profile) {
			/** @var BaseForm|Nette\Forms\Controls\BaseControl[] $form */
			$form['merge']->addCondition($form::EQUAL, TRUE)
				->toggle('mergeWithFacebook-password')
				->toggle('mergeWithFacebook-email');


			if ($profile = $this->facebookConnect->readUserData()) {
				$form->setDefaults([
					'username' => $profile['name'],
					'email' => $profile['email'],
				]);

				if ($this->manager->identityWithEmailExists($profile['email'])) { // todo: check profile uid
					$form['merge']->setDefaultValue(TRUE)
						->addRule($form::EQUAL, "There is already an account with that email, sorry but you'll have to merge them.", TRUE);
				}
			}
		};

		$form->addSubmit("connect");
		$form->onSuccess[] = function (BaseForm $form) use ($profile) {
			/** @var BaseForm|Nette\Forms\Controls\BaseControl[] $form */

			/** @var LoginDialog $dialog */
			$dialog = $this['facebook'];

			try {
				$vals = $form->values;

				if (!$vals->merge && $profile) {
					$this->facebookConnect->registerWithProvidedEmail($profile['email'], $vals->username);

				} else {
					if (empty($vals->email)) {
						$form['email']->addError('front.userForm.emailRequired');

					} elseif (!Nette\Utils\Validators::isEmail($vals->email)) {
						$form['email']->addError('front.userForm.emailValid');

					} elseif (empty($vals->password)) {
						$form['password']->addError('front.userForm.passwordRequired');

					} else {
						$this->facebookConnect->mergeAndLogin($vals->email, $vals->password);
					}
				}

				$this->getPresenter()->flashMessage('front.login.youAreNowLoggedIn', 'success');
				$this->onSingIn($this, $this->user->getIdentity());

			} catch (Nette\Security\AuthenticationException $e) {
				$form->addError($e->getMessage());
				$this->view = 'facebook/connect';

			} catch (PermissionsNotProvidedExceptions $e) {
				$dialog->open();

			} catch (MissingEmailException $e) {
				$this->view = 'facebook/connect';
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
