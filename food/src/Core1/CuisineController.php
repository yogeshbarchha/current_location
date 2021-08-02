<?php

namespace Drupal\food\Core;

abstract class CuisineController extends ControllerBase {

    public static function getAllCuisines($config = array()) {
		$query = db_select('food_cuisine', 'fm')
			->fields('fm');

		$config['defaultSortField'] = 'name';
		$config['pageSize'] = 0;
		$rows = ControllerBase::executeListQuery($query, $config);
        return($rows);
    }
    
    public static function getFeaturedCuisines($config = array()) {
		$query = db_select('food_cuisine', 'fm')
			->fields('fm')
			->condition('featured', 1);

		$config['defaultSortField'] = 'name';
		$config['pageSize'] = 0;
		$rows = ControllerBase::executeListQuery($query, $config);
        return($rows);
    }
    
    public static function getCuisine($cuisine_id) {
		$query = db_select('food_cuisine', 'fm')
			->fields('fm')
			->condition('cuisine_id', $cuisine_id);
					
		$row = ControllerBase::executeRowQuery($query);
		return($row);
    }

    public static function getRestaurantCuisines($restaurant_id, $config = array()) {
		$query = db_select('food_restaurant_cuisine', 'frm')
			->fields('frm')
			->condition('frm.restaurant_id', $restaurant_id);
		$query->innerjoin('food_cuisine', 'fm', 'frm.cuisine_id = fm.cuisine_id');
		$query->fields('fm', ['name', 'description']);

		$config['defaultSortField'] = 'name';
		$rows = ControllerBase::executeListQuery($query, $config);
        return($rows);
    }
    
    public static function getRestaurantCuisine($restaurant_cuisine_id) {
		$query = db_select('food_restaurant_cuisine', 'frm')
			->fields('frm')
			->condition('restaurant_cuisine_id', $restaurant_cuisine_id);
					
		$row = ControllerBase::executeRowQuery($query);
		return($row);
    }    
    
}
