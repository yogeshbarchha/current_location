<?php

namespace Drupal\food;

abstract class Util {
    public static function getRestaurantIdFromUrl () {
		$restaurant_id = \Drupal::routeMatch()->getRawParameter('food_restaurant');
		if(empty($restaurant_id)) {
			$restaurant_id = \Drupal::routeMatch()->getRawParameter('restaurant_id');
		}
		
		return ($restaurant_id);
	}

    public static function getServiceAreaIdFromUrl () {
		$service_area_id = \Drupal::routeMatch()->getRawParameter('food_service_area');
		if(empty($service_area_id)) {
			$service_area_id = \Drupal::routeMatch()->getRawParameter('service_area_id');
		}
		
		return ($service_area_id);
	}

    public static function getDishIdFromUrl () {
		$service_area_id = \Drupal::routeMatch()->getRawParameter('food_dish');
		if(empty($service_area_id)) {
			$service_area_id = \Drupal::routeMatch()->getRawParameter('dish_id');
		}
		
		return ($service_area_id);
	}
	
	public static function getDistanceBetweenCoordinates($lat1, $lng1, $lat2, $lng2) {
		$platform_settings = \Drupal\food\Core\PlatformController::getPlatformSettings();
		$multiplier = $platform_settings->use_miles ? 3959 : 6371;

		$distance = 
			(
				$multiplier *
				acos(
					cos(deg2rad($lat1))
					* cos(deg2rad($lat2))
					* cos(deg2rad($lng2) - deg2rad($lng1))
					+ sin(deg2rad($lat1)) * sin(deg2rad($lat2))
				)
			);
		
		return ($distance);
	}

	public static function getDrupalMenuItemsByMenu($menu_name) {
		$menu_tree = \Drupal::menuTree();
		
		// Build the typical default set of menu tree parameters.
		$parameters = $menu_tree->getCurrentRouteMenuTreeParameters($menu_name);
		// Load the tree based on this set of parameters.
		$tree = $menu_tree->load($menu_name, $parameters);
		
		// Transform the tree using the manipulators you want.
		$manipulators = array(
			// Only show links that are accessible for the current user.
			array('callable' => 'menu.default_tree_manipulators:checkAccess'),
			// Use the default sorting of menu links.
			array('callable' => 'menu.default_tree_manipulators:generateIndexAndSort'),
		);
		$tree = $menu_tree->transform($tree, $manipulators);
		// Finally, build a renderable array from the transformed tree.
		$menu_tmp = $menu_tree->build($tree);
		
		//ksm ($menu_tmp);       
		$menu = array();
		foreach ($menu_tmp['#items'] as $item) {
			$url = $item['url'];
			$menu[] = [
				'title' => $item['title'],
				'routeName' => $url->getRouteName(),
				'url' => $url->toString(),
			];
		}
		
		return ($menu);
	}
	
	public static function getAddOnModuleClassName($relativeModuleName, $relativeClassPath) {
		$moduleName = 'food_' . strtolower($relativeModuleName);
		
		$moduleHandler = \Drupal::service('module_handler');
		if ($moduleHandler->moduleExists($moduleName)){
			$className = '\\Drupal\\' . $moduleName . '\\' . $relativeClassPath;
			if(class_exists($className)) {
				return ($className);
			}
		}
		
		return (NULL);
	}
	
	public static function getAddOnModuleClassInstance($relativeModuleName, $relativeClassPath, $isController = FALSE) {
		$className = self::getAddOnModuleClassName($relativeModuleName, $relativeClassPath);
		if (empty($className)) {
			return ($className);
		}		

		if($isController) {
			$container = \Drupal::getContainer();
			$instance = call_user_func(array($className, 'create'), $container);
			return ($instance);
		} else {
			return new $className();
		}
	}
}
