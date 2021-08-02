<?php

namespace Drupal\food\Core;
use Imbibe\Util\PhpHelper;

abstract class MenuController extends ControllerBase {

    public static function getAllMenus($config = array()) {
		$query = db_select('food_menu', 'fm')
			->fields('fm');

		$config['defaultSortField'] = 'name';
		$config['pageSize'] = 0;
		$rows = ControllerBase::executeListQuery($query, $config);
        return($rows);
    }
    
    public static function getMenu($menu_id) {
		$query = db_select('food_menu', 'fm')
			->fields('fm')
			->condition('menu_id', $menu_id);
					
		$row = ControllerBase::executeRowQuery($query);
		return($row);
    }

    public static function getRestaurantMenus($restaurant_id, $config = array()) {
		$query = db_select('food_restaurant_menu', 'frm')
			->fields('frm')
			->condition('frm.restaurant_id', $restaurant_id);
        
		$returnTableDataOnly = PhpHelper::getNestedValue($config, ['returnTableDataOnly'], FALSE);
        if($returnTableDataOnly !== TRUE) {
            $query->innerjoin('food_menu', 'fm', 'frm.menu_id = fm.menu_id');
            $query->fields('fm', ['name', 'description']);
            $config['defaultSortField'] = 'name';
        }
        
		$rows = ControllerBase::executeListQuery($query, $config);
        return($rows);
    }
    
    public static function getRestaurantMenu($restaurant_menu_id) {
		$query = db_select('food_restaurant_menu', 'frm')
			->fields('frm')
			->condition('restaurant_menu_id', $restaurant_menu_id);
					
		$row = ControllerBase::executeRowQuery($query);
		return($row);
    }
    
    public static function getRestaurantMenuSection($restaurant_menu_section_id) {
		$query = db_select('food_restaurant_menu_section', 'frm')
			->fields('frm')
			->condition('frm.restaurant_menu_section_id', $restaurant_menu_section_id);
					
		$row = ControllerBase::executeRowQuery($query);
		return($row);
    }
    
    public static function getRestaurantMenuSections($restaurant_id, $restaurant_menu_id = NULL, $config = array()) {
        if($restaurant_menu_id != NULL) {
			$query = db_select('food_restaurant_menu_section', 'fm')
				->fields('fm')
				->condition('fm.restaurant_id', $restaurant_id)
				->condition('fm.restaurant_menu_id', $restaurant_menu_id);
        } else {
			$query = db_select('food_restaurant_menu_section', 'fm')
				->fields('fm')
				->condition('fm.restaurant_id', $restaurant_id);
		}
		
		$config['defaultSortField'] = 'name';
		$rows = ControllerBase::executeListQuery($query, $config);
        return($rows);
    }
    
    public static function getRestaurantMenuItem($restaurant_menu_item_id) {
        $query = db_select('food_restaurant_menu_item', 'fr')
            ->fields('fr')
            ->condition('restaurant_menu_item_id', $restaurant_menu_item_id);

		$row = ControllerBase::executeRowQuery($query, array('\Drupal\food\Core\MenuController', 'hydrateRestaurantMenuItem'));
		return($row);
    }

    public static function getRestaurantMenuItems($restaurant_id, $restaurant_menu_id = NULL , $restaurant_menu_section_id = NULL, $config = array()) {

        if($restaurant_menu_id != NULL && $restaurant_menu_section_id != NULL){
			$query = db_select('food_restaurant_menu_item', 'fm')
				->fields('fm')
				->condition('fm.restaurant_id', $restaurant_id)
				->condition('fm.restaurant_menu_id', $restaurant_menu_id)
				 
				->condition('fm.restaurant_menu_section_id', $restaurant_menu_section_id);
        }else{
            $query = db_select('food_restaurant_menu_item', 'fm');
				 $query->fields('fm');
				 $query->condition('fm.restaurant_id', $restaurant_id);
				
	    }
        
		$config['defaultSortField'] = 'name';
		$config['hydrateCallback'] = array('\Drupal\food\Core\MenuController', 'hydrateRestaurantMenuItem');
		$rows = ControllerBase::executeListQuery($query, $config);
        return($rows);
    }


    public static function getRestaurantMenuItemstoshortcut($restaurant_id,$restaurant_menu, $restaurant_status, $restaurant_menu_id = NULL , $restaurant_menu_section_id = NULL, $config = array()) {

     $query = db_select('food_restaurant_menu_item', 'fm');
				 $query->fields('fm');
				 $query->condition('fm.restaurant_id', $restaurant_id);
				 if($restaurant_status != "2")
				 $query->condition('fm.status', $restaurant_status);
                 $query->condition('fm.name', '%' . db_like($restaurant_menu) . '%', 'LIKE');
	   
        
		$config['defaultSortField'] = 'name';
                $config['pageSize'] = 0;
		$config['hydrateCallback'] = array('\Drupal\food\Core\MenuController', 'hydrateRestaurantMenuItem');
		$rows = ControllerBase::executeListQuery($query, $config);
        return($rows);
    }

    public static function searchRestaurantMenuItems($conditionCallback = NULL, $config = array()) {
		$query = db_select('food_restaurant_menu_item', 'fm')
			->fields('fm');
        
        if ($conditionCallback != NULL) {
            $query = call_user_func_array($conditionCallback, [$query]);
        }

		$config['defaultSortField'] = 'name';
		$config['hydrateCallback'] = array('\Drupal\food\Core\MenuController', 'hydrateRestaurantMenuItem');
		$rows = ControllerBase::executeListQuery($query, $config);
        return($rows);
    }
	
	public static function hydrateRestaurantMenuItem($row) {
		$row->variations = \Imbibe\Json\JsonHelper::deserializeObject($row->variations, '\Drupal\food\Core\Menu\ItemVariations');
		
		$row->derived_fields = new \StdClass();
		$row->price_formatted = round($row->price, 2, PHP_ROUND_HALF_UP);
		
		$sizes = PhpHelper::getNestedValue($row, ['variations', 'sizes']);
		if($sizes) {
			foreach($sizes as $size) {
				$size->derived_fields = new \StdClass();
				$size->price_formatted = round($size->price, 2, PHP_ROUND_HALF_UP);				
			}
		}

		$categories = PhpHelper::getNestedValue($row, ['variations', 'categories']);
		if($categories) {
			foreach($categories as $category) {
				$options = PhpHelper::getNestedValue($category, ['options']);
				if($options) {
					foreach($options as $option) {
						$option->derived_fields = new \StdClass();
						$option->price_formatted = round($option->price, 2, PHP_ROUND_HALF_UP);
					}
				}
			}
		}
	}
    
    public static function getRestaurantMenusWithSectionsAndItems($restaurant_id, $config = NULL) {
		$activeOnly = PhpHelper::getNestedValue($config, ['havingItemsOnly'], TRUE);

        $restaurant = \Drupal\food\Core\RestaurantController::getRestaurantById($restaurant_id);
        $restaurant_menus = self::getRestaurantMenus($restaurant_id, ['pageSize' => 0]);
        $restaurant_menu_sections = self::getRestaurantMenuSections($restaurant_id, NULL, ['pageSize' => 0]);        
        $restaurant_menu_items = self::getRestaurantMenuItems($restaurant_id, NULL, NULL, ['pageSize' => 0]);
		
		if($activeOnly) {
			$restaurant_menu_items = array_values(array_filter($restaurant_menu_items, function($element) use (&$activeOnly) {
				return ($element->status == \Drupal\food\Core\EntityStatus::Enabled);
			}));
		}
		
		self::assignImageUrls($restaurant_menu_items);
		$restaurant_menu_ids = [];
		$restaurant_menu_section_ids = [];
		foreach($restaurant_menu_items as $restaurant_menu_item) {
			$restaurant_menu_ids[] = $restaurant_menu_item->restaurant_menu_id;
			$restaurant_menu_section_ids[] = $restaurant_menu_item->restaurant_menu_section_id;			
		}
		
		$havingItemsOnly = PhpHelper::getNestedValue($config, ['havingItemsOnly'], TRUE);
		if($havingItemsOnly || $activeOnly) {
			$restaurant_menus = array_values(array_filter($restaurant_menus, function($element) use (&$restaurant_menu_ids, &$havingItemsOnly, &$activeOnly) {
				if($havingItemsOnly && !in_array($element->restaurant_menu_id, $restaurant_menu_ids)) {
					return (FALSE);
				}
				
				if($activeOnly && $element->status != \Drupal\food\Core\EntityStatus::Enabled) {
					return (FALSE);
				}
				
				return (TRUE);
			}));

			$restaurant_menu_sections = array_values(array_filter($restaurant_menu_sections, function($element) use (&$restaurant_menu_section_ids, &$havingItemsOnly, &$activeOnly) {
				if($havingItemsOnly && !in_array($element->restaurant_menu_section_id, $restaurant_menu_section_ids)) {
					return (FALSE);
				}
				
				if($activeOnly && $element->status != \Drupal\food\Core\EntityStatus::Enabled) {
					return (FALSE);
				}
				
				return (TRUE);
			}));
		}
		
		return ([
			'restaurant' => $restaurant,
			'restaurant_menus' => $restaurant_menus,
            'restaurant_menu_sections' => $restaurant_menu_sections,
            'restaurant_menu_items' => $restaurant_menu_items,
		]);
    }
    
}

