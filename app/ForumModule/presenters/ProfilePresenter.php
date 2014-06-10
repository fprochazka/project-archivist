<?php

namespace Archivist\ForumModule;

use Archivist\Forum\Query\QuestionsQuery;
use Archivist\ForumModule\Questions\IThreadsControlFactory;
use Archivist\UI\BaseForm;
use Archivist\Users\EmailAlreadyTakenException;
use Archivist\Users\GithubConnect;
use Archivist\Users\Identity\EmailPassword;
use Archivist\Users\Manager;
use Archivist\Users\PasswordRequiredException;
use Archivist\Users\User;
use Archivist\Users\UsernameAlreadyTakenException;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Github\Client;
use Nette;



class ProfilePresenter extends BasePresenter
{

	/**
	 * @persistent
	 */
	public $userId;

	/**
	 * @var User
	 */
	private $userEntity;

	/**
	 * @var Manager
	 * @autowire
	 */
	protected $users;

	/**
	 * @var EntityManager
	 * @autowire
	 */
	protected $em;

	/**
	 * @var GithubConnect
	 * @autowire
	 */
	protected $githubConnect;

	/**
	 * @var Client
	 * @autowire
	 */
	protected $github;



	public function actionDefault($userId = 0)
	{
		if (!is_numeric($userId) || !($this->userEntity = $this->users->find($userId))) {
			$this->error();
		}

		if ($this->user->isLoggedIn() && $userId === $this->user->getUserEntity()->getId()) {
			$this->setView('edit');
		}
	}



	protected function beforeRender()
	{
		parent::beforeRender();
		$this->template->userEntity = $this->userEntity;
	}



	public function renderEdit()
	{
		$map = [
			'Github' => NULL,
			'Google' => NULL,
			'Facebook' => NULL,
		];

		foreach ($this->userEntity->getIdentities() as $identity) {
			if ($identity->invalid) { // ignore
				continue;
			}

			$shortname = $identity->getReflection()->getShortName();
			if (!array_key_exists($shortname, $map)) {
				continue;
			}

			$map[$shortname] = $identity;
		}

		$this->template->identities = $map;
	}



	protected function createComponentEditProfile()
	{
		/** @var BaseForm|Nette\Forms\Controls\BaseControl[] $form */
		$form = new BaseForm();
		$form->setupBootstrap3Rendering();

		$form->addText('name', 'Display name');

		$form->addText('email', 'Email')
			->setAttribute('autocomplete', 'off');

		$form->addSubmit('save', 'Save');

		if ($this->userEntity) {
			$form->setDefaults([
				'name' => $this->userEntity->name,
				'email' => $this->userEntity->getEmail(),
			]);
		}

		$form->onSuccess[] = function (BaseForm $form) {
			/** @var BaseForm|Nette\Forms\Controls\BaseControl[] $form */
			if (!$this->userEntity || !$this->user->isLoggedIn()) {
				$this->error();
			}

			try {
				if ($this->userEntity->name !== $form->values->name) {
					$this->users->updateUsername($this->userEntity, $form->values->name);
					$this->flashMessage("front.profile.edit.username.success", 'success');
				}

			} catch (UsernameAlreadyTakenException $e) {
				$form['name']->addError("That username is already taken");
			}

			try {
				if ($this->userEntity->getEmail() !== $form->values->email) {
					$this->users->changeEmail($this->userEntity, $form->values->email);
					$this->flashMessage("front.profile.edit.email.success", 'success');
				}

			} catch (EmailAlreadyTakenException $e) {
				$form['email']->addError("That email is already taken");
			}

			if (!$form->hasErrors()) {
				$this->em->flush();
				$this->redirect('this');
			}
		};

		return $form;
	}



	protected function createComponentChangePassword()
	{
		$form = new BaseForm();
		$form->setupBootstrap3Rendering();
		$form->getElementPrototype()->addAttributes(array('autocomplete' => 'off'));

		$form->addPassword('password', 'New password')
			->setAttribute('autocomplete', 'off')
			->setRequired();

		$form->addSubmit('save', 'Change');

		$form->onSuccess[] = function (BaseForm $form) {
			/** @var BaseForm|Nette\Forms\Controls\BaseControl[] $form */
			if (!$this->userEntity || !$this->user->isLoggedIn()) {
				$this->error();
			}

			$values = $form->getValues();
			$this->users->changePassword($this->userEntity, $values->password);
			$this->em->flush();

			$this->flashMessage("front.profile.edit.password.success", 'success');
			$this->redirect('this');
		};

		return $form;
	}



	/**
	 * @secured
	 */
	public function handleRevokeOauth($identity = 0)
	{
		if (!($identity = $this->users->findIdentity($identity)) || $identity->getUser() !== $this->userEntity) {
			$this->error();
		}

		$identity->invalidate();
		$this->em->flush();

		$this->redrawControl('oauth');
		if (!$this->presenter->isAjax()) {
			$this->redirect('this');
		}
	}



	protected function createComponentThreads(IThreadsControlFactory $factory)
	{
		$query = (new QuestionsQuery())
			->byUser($this->userEntity)
			->withAnswersCount()
			->withCategory();

		$control = $factory->create()->setQuery($query);
		$control->perPage = 10;
		return $control->setView('questions');
	}

}
