<?php

namespace Archivist;

use Archivist\UI\BaseForm;
use Nette;


/**
 * Sign in/out presenters.
 */
class SignPresenter extends BasePresenter
{

	/**
	 * @var \Archivist\Users\Manager
	 * @autowire
	 */
	protected $usersManager;



	/**
	 * Sign-in form factory.
	 * @return Nette\Application\UI\Form
	 */
	protected function createComponentSignInForm()
	{
		$form = new BaseForm();

		$form->addText('email', 'Email:')
			->setRequired('Please enter your email.');

		$form->addPassword('password', 'Password:')
			->setRequired('Please enter your password.');

		$form->addCheckbox('remember', 'Keep me signed in');

		$form->addSubmit('send', 'Sign in');

		// call method signInFormSucceeded() on success
		$form->onSuccess[] = function (Baseform $form) {
			$values = $form->getValues();

			if ($values->remember) {
				$this->getUser()->setExpiration('14 days', FALSE);
			} else {
				$this->getUser()->setExpiration('20 minutes', TRUE);
			}

			try {
				$this->getUser()->login($values->email, $values->password);
				$this->redirect('Categories:');

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
		$form->addText('email', 'Email:');
		$form->addPassword('password', 'Password:');

	    $form->addSubmit("send", "Register");
		$form->onSuccess[] = function (BaseForm $form) {
			$values = $form->getValues();
			$this->user->login($this->usersManager->registerWithPassword($values->email, $values->password));
			$this->redirect('Categories:');
		};

		$form->setupBootstrap3Rendering();
		return $form;
	}



	public function actionOut()
	{
		$this->getUser()->logout();
		$this->flashMessage('You have been signed out.');
		$this->redirect('in');
	}

}
