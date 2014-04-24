<?php

namespace App;

use Nette,
	Nette\Application\Routers\RouteList,
	Nette\Application\Routers\Route;


/**
 * Router factory.
 */
class RouterFactory
{

	/**
	 * @var bool
	 */
	private $productionMode;



	public function __construct($productionMode)
	{
		$this->productionMode = (bool) $productionMode;
	}



	/**
	 * @return \Nette\Application\IRouter
	 */
	public function createRouter()
	{
		$router = new RouteList();
		$flags = $this->productionMode ? Route::SECURED : 0;

		$router[] = new Route('login/<action=in>', ['presenter' => 'Sign'], $flags);
		$router[] = new Route('<presenter=Categories>/<action=default>', ['module' => 'Forum'], $flags);

		return $router;
	}

}
