<?php

/**
 * @category    Pimcore
 * @package     Plugin_Feed
 * @author      John Hoogstrate <jhoogstrate@schuttelaar.nl>
 * @copyright   Copyright (c) 2013 Organic Software (http://organicsoftware.nl)
 */
class Feed_Plugin_Install {

	public static function createStaticRoutes() {
		$conf = self::getStaticRoutesConfig();
		foreach($conf->routes->route as $def) {
			$route = Staticroute::create();
			$route->setName($def->name);
			$route->setPattern($def->pattern);
			$route->setReverse($def->reverse);
			$route->setModule($def->module);
			$route->setController($def->controller);
			$route->setAction($def->action);
			$route->setVariables($def->variables);
			$route->setPriority($def->priority);
			$route->save();
		}
	}

	public static function removeStaticRoutes() {
		$conf = self::getStaticRoutesConfig();
		foreach($conf->routes->route as $def) {
			$route = Staticroute::getByName($def->name);
			if($route) {
				$route->delete();
			}
		}
	}

	/**
	 * Check if at least one static route is present.
	 * This is the minimum required for the plugin to work.
	 * @return bool TRUE if at least one static route is present.
	*/
	public static function hasStaticRoutes() {
		$conf = self::getStaticRoutesConfig();
		foreach($conf->routes->route as $def) {
			$route = Staticroute::getByName($def->name);
			if($route) {
				return true;
			}
		}

		return false;
	}

	protected static function getStaticRoutesConfig() {
		return new Zend_Config_Xml(PIMCORE_PLUGINS_PATH.'/Feed/install/staticroutes.xml');
	}
}
