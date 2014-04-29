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



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class CachingRenderer extends Nette\Object implements IRenderer
{

	/**
	 * @var \Archivist\Forum\IRenderer
	 */
	private $renderer;

	/**
	 * @var \Nette\Caching\Cache
	 */
	private $cache;

	/**
	 * @var bool
	 */
	private $productionMode;



	public function __construct($productionMode, IRenderer $renderer, Nette\Caching\IStorage $storage)
	{
		$this->productionMode = $productionMode;
		$this->renderer = $renderer;
		$this->cache = new Nette\Caching\Cache($storage, get_class($this));
	}



	/**
	 * Renders content provided by the user to sanitized HTML.
	 *
	 * @param string $content
	 * @param string|array $cacheKey
	 * @return string
	 */
	public function toHtml($content, $cacheKey = NULL)
	{
		if (!$this->productionMode) {
			return $this->renderer->toHtml($content);
		}

		return $this->cache->load($cacheKey ?: md5($content), function (&$dp) use ($content) {
			return $this->renderer->toHtml($content);
		});
	}

}
