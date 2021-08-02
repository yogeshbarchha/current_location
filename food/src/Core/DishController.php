<?php

namespace Drupal\food\Core;

use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Link;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Imbibe\Util\PhpHelper;

abstract class DishController extends ControllerBase {

    public static function getAllDishes($config = array()) {
		$query = db_select('food_dish', 'fd')
			->fields('fd');

		$config['defaultSortField'] = 'name';
		$config['pageSize'] = 0;
        $config['hydrateCallback'] = array('\Drupal\food\Core\DishController', 'hydrateDish');
		$rows = ControllerBase::executeListQuery($query, $config);
        return($rows);
    }
    
    public static function getActiveDishes($config = array()) {
		$query = db_select('food_dish', 'fd')
			->condition('status', \Drupal\food\Core\EntityStatus::Enabled)
			->fields('fd');

		$config['defaultSortField'] = 'name';
		$config['pageSize'] = 0;
        $config['hydrateCallback'] = array('\Drupal\food\Core\DishController', 'hydrateDish');
		$rows = ControllerBase::executeListQuery($query, $config);
        return($rows);
    }
    
    public static function getDishById($dish_id) {
        $query = db_select('food_dish', 'fd')
            ->condition('dish_id', $dish_id)
            ->fields('fd');

        $row = ControllerBase::executeRowQuery($query, array('\Drupal\food\Core\DishController', 'hydrateDish'));
        return($row);
    }

    public static function hydrateDish($row) {
		$row->derived_fields = new \StdClass();
    }

}
