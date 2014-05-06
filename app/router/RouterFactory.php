<?php

namespace App;

use Nette,
	Nette\Application\Routers\RouteList,
	Nette\Application\Routers\Route;
use Nette\Utils\Strings;



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

		$router[] = new Route('oauth-[!<type=google>]', [
			'module' => 'Forum',
			'presenter' => 'Categories',
			'action' => 'default',
			NULL => array(
				Route::FILTER_IN => function (array $params) {
					$params['do'] = 'login-' . $params['type'] . '-response';
					unset($params['type']);

					return $params;
				},
				Route::FILTER_OUT => function (array $params) {
					if (empty($params['do']) || !preg_match('~^login\\-([^-]+)\\-response$~', $params['do'], $m)) {
						return NULL;
					}

					$params['type'] = Strings::lower($m[1]);
					unset($params['do']);

					return $params;
				},
			),
		], $flags);

		$router[] = new Route('login/<action=in>', ['presenter' => 'Sign'], $flags);
		$router[] = new Route('p<permalinkId [0-9]+>', ['module' => 'Forum', 'presenter' => 'Question', 'action' => 'default'], $flags);
		$router[] = new Route('<presenter=Categories>/<action=default>?page=<threads-vp-page>', ['module' => 'Forum'], $flags);

		return $router;
	}

}
