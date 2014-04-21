<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Archivist\DI;

use Kdyby;
use Nette;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class ArchivistExtension extends Nette\DI\CompilerExtension implements Kdyby\Doctrine\DI\IEntityProvider
{

	public function loadConfiguration()
	{
		$config = $this->getConfig();
		$builder = $this->getContainerBuilder();

		$res = $this->loadFromFile(__DIR__ . '/services.neon');
		$this->compiler->parseServices($builder, $res);
	}



	/**
	 * Returns associative array of Namespace => mapping definition
	 *
	 * @return array
	 */
	public function getEntityMappings()
	{
		return [
			'Archivist' => __DIR__ . '/..',
		];
	}

}
