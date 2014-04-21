<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Archivist\UI;

use Archivist\Forum\PostContentRenderer;
use Kdyby;
use Latte\Engine;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class TemplateHelpers extends Nette\Object
{

	/**
	 * @var \Archivist\Forum\PostContentRenderer
	 */
	private $postRenderer;



	public function __construct(PostContentRenderer $postRenderer)
	{
		$this->postRenderer = $postRenderer;
	}


	public function texifyForumPost($content)
	{
		$this->postRenderer->parse($content);
		return $this->postRenderer->html;
	}


	public static function register(Engine $engine, PostContentRenderer $postRenderer)
	{
		$helpers = new static($postRenderer);

		foreach (get_class_methods($helpers) as $method) {
			if (method_exists('Nette\Object', $method)) {
				continue;
			}

			$engine->addFilter($method, array($helpers, $method));
		}

		return function () {};
	}

}
