<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace app\components\SignDialog;

use Kdyby;
use Nette;
use Nextras\Application\UI\SecuredLinksControlTrait;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class SecuredFacebookLoginDialog extends Kdyby\Facebook\Dialog\LoginDialog
{
	use SecuredLinksControlTrait;

	/**
	 * @secured
	 */
	public function handleOpen()
	{
		parent::handleOpen();
	}

}
