<?php

namespace Archivist;

use Archivist\SignDialog\ISingInControlFactory;
use Archivist\SignDialog\SingInControl;
use Kdyby\Autowired\AutowireComponentFactories;
use Kdyby\Autowired\AutowireProperties;
use Nette;
use Nextras;



/**
 * @property \Nette\Templating\FileTemplate|\stdClass $template
 * @method \Nette\Templating\FileTemplate|\stdClass getTemplate()
 * @method \Nette\Http\Session|\Nette\Http\SessionSection|\stdClass getSession($section = NULL)
 * @method \Archivist\Security\UserContext getUser()
 * @property-read \Archivist\Security\UserContext $user
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter
{
	use AutowireProperties;
	use AutowireComponentFactories;
	use Nextras\Application\UI\SecuredLinksPresenterTrait;

	/**
	 * @var \WebLoader\Nette\LoaderFactory
	 * @inject
	 */
	public $webloader;

	/**
	 * @var \Nette\DI\Container
	 * @autowire
	 */
	protected $serviceLocator;



	protected function startup()
	{
		parent::startup();
		$this->getSession()->start();
	}



	protected function createComponentWebloaderCss()
	{
		return $this->webloader->createCssLoader('default');
	}



	protected function createComponentWebloaderJs()
	{
		return $this->webloader->createJavaScriptLoader('default');
	}



	protected function createComponentLogin(ISingInControlFactory $factory)
	{
		$control = $factory->create()->setView('modal');
		$control->onSingIn[] = function (SingInControl $control) {
			$this->redirect('this');
		};

		return $control;
	}



	public function handleSignOut()
	{
		$this->getUser()->logout();
		$this->flashMessage('You have been signed out.');
		$this->redirect('this');
	}



	protected function beforeRender()
	{
		parent::beforeRender();
		$this->template->productionMode = !$this->serviceLocator->expand('%debugMode%');
		$this->template->analytics = $this->serviceLocator->expand('%googleAnalytics%');
	}



	/**
	 * Saves the message to template, that can be displayed after redirect.
	 * @param  string
	 * @param  string
	 * @return \stdClass
	 */
	public function flashMessage($message, $type = 'info')
	{
		$flash = parent::flashMessage($message, $type);
		$flash->count = NULL;
		$flash->parameters = [];
		return $flash;
	}



	/**
	 * @param string $message
	 * @throws \Nette\Application\ForbiddenRequestException
	 */
	public function notAllowed($message = NULL)
	{
		throw new Nette\Application\ForbiddenRequestException($message);
	}

}
