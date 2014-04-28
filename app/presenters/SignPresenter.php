<?php

namespace Archivist;

use Archivist\SignDialog\ISingInControlFactory;
use Archivist\SignDialog\SingInControl;
use Nette;


/**
 * Sign in/out presenters.
 */
class SignPresenter extends BasePresenter
{

	protected function createComponentLoginOrRegister(ISingInControlFactory $factory)
	{
		$control = $factory->create();
		$control->onSingIn[] = function (SingInControl $control) {
			$this->redirect(':Forum:Categories:');
		};

		return $control;
	}

}
