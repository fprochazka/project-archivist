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
use Archivist\Users\GithubConnect;
use Archivist\Users\GoogleConnect;
use Archivist\Users\Identity;
use Archivist\Users\Manager;
use Archivist\Users\ManualMergeRequiredException;
use Archivist\Users\MissingEmailException;
use Archivist\Users\PermissionsNotProvidedException;
use Archivist\Users\UsernameAlreadyTakenException;
use Archivist\Users\UserNotFoundException;
use Kdyby;
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

	/**
	 * @var \Archivist\Users\GithubConnect
	 */
	private $githubConnect;

	/**
	 * @var \Kdyby\Github\Client
	 */
	private $github;

	/**
	 * @var \Kdyby\Google\Google
	 */
	private $google;

	/**
	 * @var \Archivist\Users\GoogleConnect
	 */
	private $googleConnect;



	public function __construct(
		Manager $manager, UserContext $user, Nette\Http\IResponse $httpResponse, Nette\Http\Session $session,
		Facebook $facebook, FacebookConnect $facebookConnect,
		Kdyby\Github\Client $github, GithubConnect $githubConnect,
		Kdyby\Google\Google $google, GoogleConnect $googleConnect)
	{
		$this->manager = $manager;
		$this->user = $user;
		$this->httpResponse = $httpResponse;
		$this->session = $session->getSection(get_class($this));

		$this->facebook = $facebook;
		$this->facebookConnect = $facebookConnect;

		$this->github = $github;
		$this->githubConnect = $githubConnect;

		$this->google = $google;
		$this->googleConnect = $googleConnect;
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
		/** @var SecuredFacebookLoginDialog $dialog */
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
		$dialog->onResponse[] = function (SecuredFacebookLoginDialog $dialog) {
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

		$form->addPassword('password', 'password.title')
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

			/** @var SecuredFacebookLoginDialog $dialog */
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



	public function handleGithubConnect()
	{
		/** @var Kdyby\Github\UI\LoginDialog $dialog */
		$dialog = $this['github'];

		try {
			$this->githubConnect->tryLogin();

			if ($this->user->isLoggedIn() && $this->github->getUser()) {
				$this->getPresenter()->flashMessage('front.login.github.success', 'success');
				$this->onSingIn($this, $this->user->getIdentity());
			}

		} catch (PermissionsNotProvidedException $e) {
			$dialog->open();

		} catch (AccountConflictException $e) {
			$this->view = 'github/connect';

		} catch (ManualMergeRequiredException $e) {
			$this->view = 'github/connect';
		}
	}



	protected function createComponentGithub()
	{
		$dialog = new Kdyby\Github\UI\LoginDialog($this->github);
		$dialog->onResponse[] = function (Kdyby\Github\UI\LoginDialog $dialog) {
			try {
				$this->githubConnect->tryLogin();

				if (!$this->user->isLoggedIn()) {
					$this->getPresenter()->flashMessage('front.login.github.failed', 'danger');

				} else {
					$this->getPresenter()->flashMessage('front.login.github.success', 'success');
				}

				$this->onSingIn($this, $this->user->getIdentity());

			} catch (PermissionsNotProvidedException $e) {
				$this->getPresenter()->flashMessage('front.login.github.permission.missingEmail', 'info');
				return;

			} catch (AccountConflictException $e) {
				$this->redirect('githubConnect!');

			} catch (ManualMergeRequiredException $e) {
				$this->redirect('githubConnect!');
			}
		};

		return $dialog;
	}



	/**
	 * @return BaseForm
	 */
	protected function createComponentMergeWithGithub()
	{
		/** @var BaseForm|Nette\Forms\Controls\BaseControl[] $form */
		$form = new BaseForm();
		$form->setTranslator($this->getTranslator()->domain('front.mergeWithGithub'));

		$form->addText('username', 'username.title')
			->setRequired('username.required');

		$form->addCheckbox('merge', 'merge.title');

		$form->addText('email', 'email.title')
			->addConditionOn($form['merge'], $form::EQUAL, TRUE)
			->addRule($form::FILLED, 'email.required')
			->addRule($form::EMAIL, 'email.invalid');

		$form->addPassword('password', 'password.title')
			->addConditionOn($form['merge'], $form::EQUAL, TRUE)
			->addRule($form::FILLED, 'password.required');

		$profile = NULL;
		try {
			$profile = $this->githubConnect->readUserData();

		} catch (PermissionsNotProvidedException $e) {
			if (!$this->httpResponse->isSent()) {
				$this->getPresenter()->flashMessage('front.mergeWithGithub.missingGithubPermissions', 'warning');
				$this->redirect('facebookConnect!');
			}

			$form->addError('front.mergeWithGithub.missingGithubPermissions');
		}

		$form->onAttached[] = function (BaseForm $form) use ($profile) {
			/** @var BaseForm|Nette\Forms\Controls\BaseControl[] $form */
			$form['merge']->addCondition($form::EQUAL, TRUE)
				->toggle('mergeWithGithub-password')
				->toggle('mergeWithGithub-email');


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

			/** @var Kdyby\Github\UI\LoginDialog $dialog */
			$dialog = $this['github'];

			try {
				$vals = $form->values;

				if (!$vals->merge && $profile) {
					$this->githubConnect->registerWithProvidedEmail($profile['email'], $vals->username);

				} else {
					$this->githubConnect->mergeAndLogin($vals->email, $vals->password);
				}

				$this->getPresenter()->flashMessage('front.mergeWithGithub.success', 'success');
				$this->onSingIn($this, $this->user->getIdentity());

			} catch (Nette\Security\AuthenticationException $e) {
				$form->addError('validation.loginFailed');
				$this->view = 'github/connect';

			} catch (PermissionsNotProvidedException $e) {
				$dialog->open();

			} catch (MissingEmailException $e) {
				$this->view = 'github/connect';
			}
		};

		$form->setupBootstrap3Rendering();
		return $form;
	}



	public function handleGoogleConnect()
	{
		/** @var Kdyby\Google\Dialog\LoginDialog $dialog */
		$dialog = $this['google'];

		try {
			$this->googleConnect->tryLogin();

			if ($this->user->isLoggedIn() && $this->google->getUser()) {
				$this->getPresenter()->flashMessage('front.login.google.success', 'success');
				$this->onSingIn($this, $this->user->getIdentity());
			}

		} catch (PermissionsNotProvidedException $e) {
			$dialog->open();

		} catch (AccountConflictException $e) {
			$this->view = 'google/connect';

		} catch (ManualMergeRequiredException $e) {
			$this->view = 'google/connect';
		}
	}



	protected function createComponentGoogle()
	{
		$dialog = new Kdyby\Google\Dialog\LoginDialog($this->google);
		$dialog->onResponse[] = function (Kdyby\Google\Dialog\LoginDialog $dialog) {
			try {
				$this->googleConnect->tryLogin();

				if (!$this->user->isLoggedIn()) {
					$this->getPresenter()->flashMessage('front.login.google.failed', 'danger');

				} else {
					$this->getPresenter()->flashMessage('front.login.google.success', 'success');
				}

				$this->onSingIn($this, $this->user->getIdentity());

			} catch (PermissionsNotProvidedException $e) {
				$this->getPresenter()->flashMessage('front.login.google.permission.missingEmail', 'info');
				return;

			} catch (AccountConflictException $e) {
				$this->redirect('googleConnect!');

			} catch (ManualMergeRequiredException $e) {
				$this->redirect('googleConnect!');
			}
		};

		return $dialog;
	}



	/**
	 * @return BaseForm
	 */
	protected function createComponentMergeWithGoogle()
	{
		/** @var BaseForm|Nette\Forms\Controls\BaseControl[] $form */
		$form = new BaseForm();
		$form->setTranslator($this->getTranslator()->domain('front.mergeWithGoogle'));

		$form->addText('username', 'username.title')
			->setRequired('username.required');

		$form->addCheckbox('merge', 'merge.title');

		$form->addText('email', 'email.title')
			->addConditionOn($form['merge'], $form::EQUAL, TRUE)
			->addRule($form::FILLED, 'email.required')
			->addRule($form::EMAIL, 'email.invalid');

		$form->addPassword('password', 'password.title')
			->addConditionOn($form['merge'], $form::EQUAL, TRUE)
			->addRule($form::FILLED, 'password.required');

		$profile = NULL;
		try {
			$profile = $this->googleConnect->readUserData();

		} catch (PermissionsNotProvidedException $e) {
			if (!$this->httpResponse->isSent()) {
				$this->getPresenter()->flashMessage('front.mergeWithGoogle.missingGooglePermissions', 'warning');
				$this->redirect('facebookConnect!');
			}

			$form->addError('front.mergeWithGoogle.missingGooglePermissions');
		}

		$form->onAttached[] = function (BaseForm $form) use ($profile) {
			/** @var BaseForm|Nette\Forms\Controls\BaseControl[] $form */
			$form['merge']->addCondition($form::EQUAL, TRUE)
				->toggle('mergeWithGoogle-password')
				->toggle('mergeWithGoogle-email');


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

			/** @var Kdyby\Google\Dialog\LoginDialog $dialog */
			$dialog = $this['google'];

			try {
				$vals = $form->values;

				if (!$vals->merge && $profile) {
					$this->googleConnect->registerWithProvidedEmail($profile['email'], $vals->username);

				} else {
					$this->googleConnect->mergeAndLogin($vals->email, $vals->password);
				}

				$this->getPresenter()->flashMessage('front.mergeWithGoogle.success', 'success');
				$this->onSingIn($this, $this->user->getIdentity());

			} catch (Nette\Security\AuthenticationException $e) {
				$form->addError('validation.loginFailed');
				$this->view = 'google/connect';

			} catch (PermissionsNotProvidedException $e) {
				$dialog->open();

			} catch (MissingEmailException $e) {
				$this->view = 'google/connect';
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
