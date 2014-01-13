<?php

namespace App\Factories;

use Nette,
	Nette\Application\Routers\RouteList,
	Nette\Application\Routers\Route,
	Nette\Application\Routers\SimpleRouter;


/**
 *  @author Jan Mikes <j.mikes@me.com>
 *  @copyright Jan Mikes - janmikes.cz
 */
final class RouterFactory extends Nette\Object
{

	/**
	 * @return \Nette\Application\IRouter
	 */
	public function create()
	{
		if (function_exists("apache_get_modules") && in_array("mod_rewrite", apache_get_modules())) {
			$router = new RouteList();

			// Backend router
			$backendRouter = new RouteList("Backend");
			$backendRouter[] = new Route("backend/<presenter>/<action>[/<id>]", "Dashboard:default");
			$router[] = $backendRouter;

			// Frontend router
			$frontendRouter = new RouteList("Frontend");
			$frontendRouter[] = new Route("<presenter>/<action>[/<id>]", "Homepage:default");
			$router[] = $frontendRouter;
		} else {
			$router = new SimpleRouter("Frontend:Homepage:default");
		}
			return $router;
	}

}
