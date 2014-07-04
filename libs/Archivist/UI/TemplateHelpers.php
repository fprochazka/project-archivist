<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Archivist\UI;

use Archivist\Forum\IRenderer;
use Archivist\Forum\Post;
use Archivist\NotSupportedException;
use Kdyby;
use Latte\Engine;
use Nette;
use Nette\Utils\Callback;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class TemplateHelpers extends Nette\Object
{

	/**
	 * @var \Archivist\Forum\IRenderer
	 */
	private $postRenderer;



	public function __construct(IRenderer $postRenderer)
	{
		$this->postRenderer = $postRenderer;
	}



	public function texifyForumPost($post, $options = NULL)
	{
		$processor = Callback::closure($this->postRenderer, 'toHtml');
		if ($options === 'inline') {
			$processor = Callback::closure($this->postRenderer, 'toHtmlLine');
		}

		if ($post instanceof Post) {
			return $processor($post->getContent(), (string) $post);

		} elseif (isset($post->p_id, $post->p_type, $post->p_content, $post->p_created_at)) {
			$id = ucfirst($post->p_type) . ' ' . $post->p_id . '#' . Nette\Utils\DateTime::from(isset($post->p_edited_at) ? $post->p_edited_at : $post->p_created_at)->format('YmdHis');
			return $processor($post->p_content, $id);

		} else {
			throw new NotSupportedException;
		}
	}



	public static function register(Engine $engine, IRenderer $postRenderer)
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
