<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Archivist\UI;

use Kdyby;
use Kdyby\Translation\Translator;
use Nette;
use Nextras;



/**
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @method onAttached(BaseControl $control, Nette\Application\UI\PresenterComponent $parent)
 * @method \Archivist\BasePresenter getPresenter()
 *
 * @property \Archivist\BasePresenter|BaseControl[] $presenter
 * @property-read \Archivist\BasePresenter|BaseControl[] $presenter
 * @property \Nette\Templating\FileTemplate|\stdClass $template
 * @property-read \Nette\Templating\FileTemplate|\stdClass $template
 */
class BaseControl extends Nette\Application\UI\Control
{

	use Kdyby\Autowired\AutowireComponentFactories;
	use Nextras\Application\UI\SecuredLinksControlTrait;

	/**
	 * @var array of function (BaseControl $control, Nette\ComponentModel\Container $parent)
	 */
	public $onAttached = array();

	/**
	 * @var Nette\DI\Container
	 */
	private $serviceLocator;

	/**
	 * @var Nette\Localization\ITranslator|Translator
	 */
	private $translator;



	public function __construct()
	{
		parent::__construct();
	}



	public function injectContext(Nette\DI\Container $serviceLocator)
	{
		$this->serviceLocator = $serviceLocator;
	}



	/**
	 * @return Nette\DI\Container|\SystemContainer
	 */
	protected function getServiceLocator()
	{
		if ($this->serviceLocator === NULL) {
			$this->serviceLocator = $this->getPresenter()->getContext();
		}

		return $this->serviceLocator;
	}



	/**
	 * @param string $class
	 * @return Nette\Templating\FileTemplate|Nette\Templating\ITemplate|\stdClass
	 */
	protected function createTemplate($class = NULL)
	{
		$template = parent::createTemplate($class);
		/** @var \Nette\Templating\FileTemplate|\stdClass $template */

		$sl = $this->getServiceLocator();
		$template->productionMode = !$sl->expand('%debugMode%');

		return $template;
	}



	/**
	 * @param \Nette\ComponentModel\Container $obj
	 */
	protected function attached($parent)
	{
		parent::attached($parent);
		$this->onAttached($this, $parent);
	}



	/**
	 * @param \Kdyby\Translation\Translator|\Nette\Localization\ITranslator $translator
	 * @return BaseControl
	 */
	public function setTranslator($translator)
	{
		$this->translator = $translator;
	}



	/**
	 * @return \Kdyby\Translation\Translator|\Nette\Localization\ITranslator
	 */
	public function getTranslator()
	{
		if ($this->translator === NULL) {
			$this->translator = $this->getServiceLocator()->getByType('Nette\Localization\ITranslator');
		}

		return $this->translator;
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



	protected function isSignalReceiver()
	{
		if ($this->presenter->isSignalReceiver($this)) {
			return TRUE;
		}

		if (!$signal = $this->presenter->getSignal()) {
			return FALSE;
		}

		try {
			$component = $signal[0] === '' ? $this->getPresenter() : $this->getPresenter()->getComponent($signal[0], FALSE);
		} catch (Nette\InvalidArgumentException $e) { }

		return !empty($component) ? $component->getParent() === $this : FALSE;
	}

}
