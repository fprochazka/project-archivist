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
use Archivist\Users\PermissionsNotProvidedException;
use Archivist\Users\UsernameAlreadyTakenException;
use Archivist\Users\UserNotFoundException;
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

	/**
	 * @var \Nette\Http\SessionSection|\stdClass
	 */
	private $session;



	public function __construct(Manager $manager, UserContext $user, Nette\Http\IResponse $httpResponse, Nette\Http\Session $session,
		Facebook $facebook, FacebookConnect $facebookConnect)
	{
		$this->manager = $manager;
		$this->user = $user;
		$this->facebook = $facebook;
		$this->facebookConnect = $facebookConnect;
		$this->httpResponse = $httpResponse;
		$this->session = $session->getSection(get_class($this));
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
		$form->setTranslator($this->getTranslator()->domain('front.signInForm'));

		$form->addText('email', 'email.title')
			->setRequired('email.required')
			->addRule($form::EMAIL, 'email.invalid');

		$form->addPassword('password', 'password.title')
			->setRequired('password.required');

		$form->addCheckbox('remember', 'remember.title')
			->setDefaultValue(TRUE);

		$form->addSubmit('signIn', 'signIn.title');

		// call method signInFormSucceeded() on success
		$form->onSuccess[] = function (Baseform $form, $values) {
			$this->user->setExpiration($values->remember ? '14 days' : '2 hours', FALSE);

			try {
				$this->user->login($values->email, $values->password);
				$this->onSingIn($this, $this->user->getIdentity());

			} catch (UserNotFoundException $e) {
				$this->session->loginValues = $values;
				$this->session->setExpiration('+10 minutes');

				$this->view = 'password/completeRegistration';

			} catch (Nette\Security\AuthenticationException $e) {
				$form->addError('validation.loginFailed');
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
		$form->setTranslator($this->getTranslator()->domain('front.registerForm'));

		$form->addText('username', 'username.title')
			->setRequired('username.required');

		$form->addSubmit('register', 'register.title');

		$form->onSuccess[] = function (BaseForm $form, $values) {
			if (!$login = $this->session->loginValues) {
				$this->redirect('showModal!');
			}

			try {
				$this->user->login($this->manager->registerWithPassword($login->email, $login->password, $values->username));
				$this->session->remove();
				$this->onSingIn($this, $this->user->getIdentity());

			} catch (EmailAlreadyTakenException $e) {
				$form->addError('validation.email.taken');
				$this->view = 'password/completeRegistration';

			} catch (UsernameAlreadyTakenException $e) {
				$form->addError('validation.username.taken');
				$this->view = 'password/completeRegistration';
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

		} catch (PermissionsNotProvidedException $e) {
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

			} catch (PermissionsNotProvidedException $e) {
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
		$form->setTranslator($this->getTranslator()->domain('front.mergeWithFacebook'));

		$form->addText('username', 'username.title')
			->setRequired('username.required');

		$form->addCheckbox('merge', 'merge.title');

		$form->addText('email', 'email.title')
			->addConditionOn($form['merge'], $form::EQUAL, TRUE)
				->addRule($form::FILLED, 'email.required')
				->addRule($form::EMAIL, 'email.invalid');

		$form->addPassword('password', 'Password')
			->addConditionOn($form['merge'], $form::EQUAL, TRUE)
				->addRule($form::FILLED, 'password.required');

		$profile = NULL;
		try {
			$profile = $this->facebookConnect->readUserData();

		} catch (PermissionsNotProvidedException $e) {
			if (!$this->httpResponse->isSent()) {
				$this->getPresenter()->flashMessage('front.mergeWithFacebook.missingFacebookPermissions', 'warning');
				$this->redirect('facebookConnect!');
			}

			$form->addError('front.mergeWithFacebook.missingFacebookPermissions');
		}

		$form->onAttached[] = function (BaseForm $form) use ($profile) {
			/** @var BaseForm|Nette\Forms\Controls\BaseControl[] $form */
			$form['merge']->addCondition($form::EQUAL, TRUE)
				->toggle('mergeWithFacebook-password')
				->toggle('mergeWithFacebook-email');


			if ($profile) {
				$form->setDefaults([
					'username' => $profile['name'],
					'email' => $profile['email'],
				]);

				if ($this->manager->identityWithEmailExists($profile['email'])) { // todo: check profile uid
					$form['merge']->setDefaultValue(TRUE)
						->addRule($form::EQUAL, 'merge.forced', TRUE);
				}
			}
		};

		$form->addSubmit('connect');
		$form->onSuccess[] = function (BaseForm $form) use ($profile) {
			/** @var BaseForm|Nette\Forms\Controls\BaseControl[] $form */

			/** @var LoginDialog $dialog */
			$dialog = $this['facebook'];

			try {
				$vals = $form->values;

				if (!$vals->merge && $profile) {
					$this->facebookConnect->registerWithProvidedEmail($profile['email'], $vals->username);

				} else {
					$this->facebookConnect->mergeAndLogin($vals->email, $vals->password);
				}

				$this->getPresenter()->flashMessage('front.mergeWithFacebook.success', 'success');
				$this->onSingIn($this, $this->user->getIdentity());

			} catch (Nette\Security\AuthenticationException $e) {
				$form->addError('validation.loginFailed');
				$this->view = 'facebook/connect';

			} catch (PermissionsNotProvidedException $e) {
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
