<?php

namespace Archivist;

use Kdyby\Autowired\AutowireComponentFactories;
use Kdyby\Autowired\AutowireProperties;
use Nette;



/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter
{
	use AutowireProperties;
	use AutowireComponentFactories;



	/**
	 * @var \WebLoader\LoaderFactory
	 * @inject
	 */
	public $webloader;



	protected function createComponentWebloaderCss()
	{
		return $this->webloader->createCssLoader('default');
	}



	protected function createComponentWebloaderJs()
	{
		return $this->webloader->createJavaScriptLoader('default');
	}

}
