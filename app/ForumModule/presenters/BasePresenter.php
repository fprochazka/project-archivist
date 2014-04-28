<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Archivist\ForumModule;

use IPub;
use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
abstract class BasePresenter extends \Archivist\BasePresenter
{

	use IPub\Gravatar\TGravatar;



	protected function createTemplate($class = NULL)
	{
		/** @var Nette\Bridges\ApplicationLatte\Template|\stdClass $template */
		$template = parent::createTemplate($class);

		// Add gravatar to template
		$template->_gravatar = $this->gravatar;

		// Register template helpers
		$template->addFilter('gravatar', function ($email, $size = NULL) {
			return $this->gravatar->buildUrl($email, $size);
		});

		return $template;
	}

}
