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
use Nette;
use Nette\Forms\Controls;



/**
 * @author Filip Procházka <filip@prochazka.su>
 * @method onAttached(\Archivist\UI\BaseForm $form, \Archivist\BasePresenter $presenter)
 * @method onError(\Nette\Forms\Form $form)
 *
 * @property \Archivist\BasePresenter $presenter
 */
class BaseForm extends \Nette\Application\UI\Form
{

	/**
	 * @var array of function (BaseForm $form, Nette\Application\UI\Presenter $presenter)
	 */
	public $onAttached = array();



	public function __construct()
	{
		parent::__construct();
		$this->getElementPrototype()->novalidate = "novalidate";

		$this->onAttached[] = function () {
			$this->addProtection('csrfProtection');
		};
	}



	protected function signalLink($signal, $args = array())
	{
		$name = $this->lookupPath('Nette\Application\UI\Presenter');

		foreach ($args as $key => $val) {
			unset($args[$key]);
			$args[$name . '-' . $key] = $val;
		}

		return $this->getPresenter()->link($name . '-' . $signal, $args);
	}



	public function setupBootstrap3Rendering()
	{
		/** @var Nette\Forms\Rendering\DefaultFormRenderer $renderer */
		$renderer = $this->getRenderer();
		$renderer->wrappers['controls']['container'] = NULL;
		$renderer->wrappers['pair']['container'] = 'div class=form-group';
		$renderer->wrappers['pair']['.error'] = 'has-error';
		$renderer->wrappers['control']['container'] = 'div class=col-sm-9';
		$renderer->wrappers['label']['container'] = 'div class="col-sm-3 control-label"';
		$renderer->wrappers['control']['description'] = 'span class=help-block';
		$renderer->wrappers['control']['errorcontainer'] = 'span class=help-block';
		$renderer->wrappers['error']['container'] = 'div';
		$renderer->wrappers['error']['item'] = 'div class="alert alert-danger"';

		// make form and controls compatible with Twitter Bootstrap
		$this->getElementPrototype()->class('form-horizontal');

		foreach ($this->getControls() as $control) {
			if ($control instanceof Controls\Button) {
				$control->getControlPrototype()->addClass(empty($usedPrimary) ? 'btn btn-primary' : 'btn btn-default');
				$usedPrimary = TRUE;

			} elseif ($control instanceof Controls\TextBase || $control instanceof Controls\SelectBox || $control instanceof Controls\MultiSelectBox) {
				$control->getControlPrototype()->addClass('form-control');

			} elseif ($control instanceof Controls\Checkbox || $control instanceof Controls\CheckboxList || $control instanceof Controls\RadioList) {
				$control->getSeparatorPrototype()->setName('div')->addClass($control->getControlPrototype()->type);
			}
		}
	}



	protected function attached($parent)
	{
		parent::attached($parent);

		if (!$parent instanceof Nette\Application\UI\Presenter) {
			return;
		}

		if (!$this->getTranslator()) {
			/** @var Kdyby\Translation\Translator $translator */
			$translator = $this->presenter->context->getByType('Nette\Localization\ITranslator');
			$this->setTranslator($translator);
		}

		$this->onAttached($this, $parent);

		foreach ($this->getControls() as $control) {
			/** @var Controls\BaseControl $control */
			if ($placeholder = $control->getOption('placeholder')) {
				$control->setAttribute('placeholder', $placeholder);
				$control->setAttribute('title', $this->getTranslator()->translate($placeholder));

			} elseif ($control instanceof Controls\Button) {
				$el = $control->getControlPrototype();
				$caption = $el->getText();
				$el->setText($this->getTranslator()->translate($caption));
			}
		}

		$this->getElementPrototype()
			->data('name', $this->lookupPath('Nette\Application\UI\Presenter'));
	}

}
