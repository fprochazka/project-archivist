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
use Archivist\UI\BaseForm;
use Archivist\Users\Identity;
use Archivist\Users\ISocialConnect;
use Archivist\Users\Manager;
use Archivist\Users\MissingEmailException;
use Archivist\Users\PermissionsNotProvidedException;
use Archivist\Users\UsernameAlreadyTakenException;
use Kdyby;
use Nette;
use Nette\Application\UI\PresenterComponent;



/**
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @method onSingIn(MergeWithSocialNetworkControl $self, Identity $identity)
 * @method onFailure(MergeWithSocialNetworkControl $self, \Exception $exception)
 */
class MergeWithSocialNetworkControl extends BaseForm
{

	/**
	 * @var array
	 */
	public $onSingIn = [];

	/**
	 * @var array
	 */
	public $onFailure = [];

	/**
	 * @var string
	 */
	public $translationDomain = 'front.mergeWith';

	/**
	 * @var ISocialConnect
	 */
	private $connect;

	/**
	 * @var PresenterComponent|Kdyby\Facebook\Dialog\LoginDialog|Kdyby\Github\UI\LoginDialog|Kdyby\Google\Dialog\LoginDialog
	 */
	private $dialog;

	/**
	 * @var Nette\Utils\ArrayHash
	 */
	private $profile;

	/**
	 * @var \Archivist\Users\Manager
	 */
	private $manager;

	/**
	 * @var \Nette\Http\IResponse
	 */
	private $httpResponse;

	/**
	 * @var \Archivist\Security\UserContext
	 */
	private $user;



	public function __construct(Manager $manager, UserContext $user, Nette\Http\IResponse $httpResponse)
	{
		parent::__construct();
		$this->manager = $manager;
		$this->user = $user;
		$this->httpResponse = $httpResponse;

		/** @var MergeWithSocialNetworkControl|Nette\Forms\Controls\BaseControl[] $this */

		$this->addText('username', 'username.title')
			->setRequired('username.required');

		$this->addCheckbox('merge', 'merge.title');

		$this->addText('email', 'email.title')
			->addConditionOn($this['merge'], $this::EQUAL, TRUE)
			->addRule($this::FILLED, 'email.required')
			->addRule($this::EMAIL, 'email.invalid');

		$this->addPassword('password', 'password.title')
			->addConditionOn($this['merge'], $this::EQUAL, TRUE)
			->addRule($this::FILLED, 'password.required');

		$this->addSubmit('connect');
		$this->onSuccess[] = [$this, 'processSuccess'];

		$this->setupBootstrap3Rendering();
	}



	public function setSocialConnect(ISocialConnect $connect)
	{
		$this->connect = $connect;
		return $this;
	}



	public function setDialog(PresenterComponent $dialog)
	{
		$this->dialog = $dialog;
		return $this;
	}



	public function processSuccess(BaseForm $form)
	{
		try {
			$vals = $this->values;

			if (!$vals->merge && $this->profile) {
				$this->connect->register($vals->username);

			} else {
				$this->connect->mergeAndLogin($vals->email, $vals->password);
			}

			$this->onSingIn($this, $this->user->getIdentity());

		} catch (Nette\Security\AuthenticationException $e) {
			$this->addError('validation.loginFailed');
			$this->onFailure($this, $e);

		} catch (UsernameAlreadyTakenException $e) {
			$this->addError('validation.username.taken');
			$this->onFailure($this, $e);

		} catch (PermissionsNotProvidedException $e) {
			$this->dialog->open();

		} catch (MissingEmailException $e) {
			$this->onFailure($this, $e);
		}
	}



	/**
	 * @param \Nette\ComponentModel\Container $obj
	 */
	protected function attached($obj)
	{
		parent::attached($obj);

		if (!$obj instanceof Nette\Application\UI\Presenter) {
			return;
		}

		/** @var MergeWithSocialNetworkControl|Nette\Forms\Controls\BaseControl[] $this */
		$this->setTranslator($this->getTranslator()->domain($this->translationDomain));

		try {
			$this->profile = $this->connect->readUserData();

		} catch (PermissionsNotProvidedException $e) {
			if (!$this->httpResponse->isSent()) {
				$this->dialog->open();
			}

			$this->addError($this->translationDomain . '.missingPermissions');
		}

		$this['merge']->addCondition($this::EQUAL, TRUE)
			->toggle('merge-password')
			->toggle('merge-email');

		if ($this->profile) {
			$this->setDefaults([
				'username' => $this->profile['name'],
				'email' => $this->profile['email'],
			]);

			if ($this->manager->identityWithEmailExists($this->profile['email'])) { // todo: check profile uid
				$this['merge']->setDefaultValue(TRUE)
					->addRule($this::EQUAL, 'merge.forced', TRUE);
			}
		}
	}

}



interface IMergeWithSocialNetworkControlFactory
{

	/** @return MergeWithSocialNetworkControl */
	function create();
}
