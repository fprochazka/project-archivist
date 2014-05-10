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

		} catch (ManualMergeRequiredException $e) {

		}

		$this->view = 'social/connect';
		$this->template->form = $this['mergeWithFacebook'];
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



	protected function createComponentMergeWithFacebook(IMergeWithSocialNetworkControlFactory $factory)
	{
		$control = $factory->create();
		$control->translationDomain .= 'Facebook';
		$control->setDialog($this['facebook']);
		$control->setSocialConnect($this->facebookConnect);

		$control->onFailure[] = function () {
			$this->view = 'social/connect';
			$this->template->form = $this['mergeWithFacebook'];
		};
		$control->onSingIn[] = function ($self, Identity $identity) {
			$this->onSingIn($this, $identity);
		};

		return $control;
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

		} catch (ManualMergeRequiredException $e) {

		}

		$this->view = 'social/connect';
		$this->template->form = $this['mergeWithGithub'];
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



	protected function createComponentMergeWithGithub(IMergeWithSocialNetworkControlFactory $factory)
	{
		$control = $factory->create();
		$control->translationDomain .= 'Github';
		$control->setDialog($this['github']);
		$control->setSocialConnect($this->githubConnect);

		$control->onFailure[] = function () {
			$this->view = 'social/connect';
			$this->template->form = $this['mergeWithGithub'];
		};
		$control->onSingIn[] = function ($self, Identity $identity) {
			$this->onSingIn($this, $identity);
		};

		return $control;
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

		} catch (ManualMergeRequiredException $e) {

		}

		$this->view = 'social/connect';
		$this->template->form = $this['mergeWithGoogle'];
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

				try {
					$this->onSingIn($this, $this->user->getIdentity());

				} catch (Nette\Application\AbortException $e) {
					// nope
				}

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



	protected function createComponentMergeWithGoogle(IMergeWithSocialNetworkControlFactory $factory)
	{
		$control = $factory->create();
		$control->translationDomain .= 'Google';
		$control->setDialog($this['google']);
		$control->setSocialConnect($this->googleConnect);

		$control->onFailure[] = function () {
			$this->view = 'social/connect';
			$this->template->form = $this['mergeWithGoogle'];
		};
		$control->onSingIn[] = function ($self, Identity $identity) {
			$this->onSingIn($this, $identity);
		};

		return $control;
	}

}



interface ISingInControlFactory
{

	/** @return SingInControl */
	function create();
}
