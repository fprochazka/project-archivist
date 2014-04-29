<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Archivist\Forum\Preprocessor;

use Archivist\Forum\IRenderer;
use Kdyby;
use Nette;
use Text\Converter;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class TexyRenderer extends Converter implements IRenderer
{

	public function __construct()
	{
		parent::__construct("Help", "en", "");
	}



	public function toHtml($texy)
	{
		$this->parse($texy);
		return $this->html;
	}



	public function createTexy()
	{
		$texy = parent::createTexy();
		$texy->headingModule->top = 2;

		$texy->registerBlockPattern(
			array($texy->blockModule, 'pattern'),
			'#^```[\t ]*(.*)' . \TexyPatterns::MODIFIER_H . '?$((?:\n(?0)|\n.*+)*)(?:\n```.*$|\z)#mUi',
			'mdBlocks'
		);

		$texy->addHandler('image', function (\TexyHandlerInvocation $invocation, \TexyImage $image, $link) {
			if ($image->width !== NULL) {
				$image->width = min($image->width, 900);
			}
			if ($image->height !== NULL) {
				$image->height = min($image->height, 1000);
			}

			// todo: fix ratio

			return $invocation->proceed();
		});

		$texy::$advertisingNotice = FALSE;

		return $texy;
	}

}
