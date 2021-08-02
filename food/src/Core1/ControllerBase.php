<?php

namespace Drupal\food\Core;
use Drupal\Core\Url;
use Drupal\Core\Link;

use Imbibe\Util\PhpHelper;

abstract class ControllerBase {
    public static function executeListQuery ($query, $config = array()) {
		$defaultSortField = PhpHelper::getNestedValue($config, ['defaultSortField'], NULL);
		$defaultSortDir = 'ASC';
		if(is_array($defaultSortField)) {
			$arr = $defaultSortField;
			$defaultSortField = $arr[0];
			$defaultSortDir = $arr[1];
		}
		
		$header = PhpHelper::getNestedValue($config, ['header'], NULL);
        if ($header != NULL) {
            $query = $query
                ->extend('Drupal\Core\Database\Query\TableSortExtender')
                ->orderByHeader($header);
        } else {
			if($defaultSortField != NULL) {
				$query = $query
					->orderBy($defaultSortField, $defaultSortDir);
			}
        }

		$pageSize = PhpHelper::getNestedValue($config, ['pageSize'], 25);
		if($pageSize != 0) {
			$query = $query
				->extend('Drupal\Core\Database\Query\PagerSelectExtender')
				->limit($pageSize);
		}

        $result = $query
            ->execute();

		$hydrateCallback = PhpHelper::getNestedValue($config, ['hydrateCallback'], NULL);
        $skipHydrateCallback = PhpHelper::getNestedValue($config, ['skipHydrateCallback'], FALSE);

        $rows = array();
        foreach ($result as $row) {
			if($hydrateCallback != NULL && $skipHydrateCallback !== TRUE) {
				call_user_func($hydrateCallback, $row);
			}
			
            $rows[] = $row;
        }
		
		return ($rows);
	}

    public static function executeRowQuery ($query, $config = NULL) {
        $result = $query
            ->execute();

		if($row = $result->fetchObject()) {
            $hydrateCallback = NULL;
            if(is_callable($config)) {
                $hydrateCallback = $config;
            } else {
        		$hydrateCallback = PhpHelper::getNestedValue($config, ['hydrateCallback'], NULL);
            }
            
            $skipHydrateCallback = PhpHelper::getNestedValue($config, ['skipHydrateCallback'], FALSE);
			if($hydrateCallback != NULL && $skipHydrateCallback !== TRUE) {
				call_user_func($hydrateCallback, $row);
			}

            return($row);
		} else {
            return(NULL);
		}
	}
	
	public static function prepareForUpdation($tableName, $fields, $config = array()) {
		if(is_object($fields)) {
			$fields = (array) $fields;
		}
		
		$schemaName = PhpHelper::getNestedValue($config, ['schemaName'], 'food');
		$schema = drupal_get_module_schema($schemaName, $tableName);
		$schemaFields = $schema['fields'];
		
		$jsonEncodeComplexValues = PhpHelper::getNestedValue($config, ['jsonEncodeComplexValues'], TRUE);
		$filteredFields = [];
		foreach($fields as $name => $value) {
			if(isset($schemaFields[$name])) {
				if(is_object($value) || is_array($value)) {
					$value = json_encode($value);
				}
				
				$filteredFields[$name] = $value;
			}
		}
		
		return ($filteredFields);
	}
    
    public static function assignImageUrl($entity) {
        if ($entity->image_fid != NULL && is_numeric($entity->image_fid)) {
            $fids = $entity->image_fid;
        }
        $image = \Drupal\file\Entity\File::load($fids);

        if ($entity->image_fid != NULL && isset($image)) {
            $entity->image_url = $image->url();
        }
    }

    public static function assignImageUrls($entities) {
        $fids = [];
        foreach ($entities as $entity) {
            if ($entity->image_fid != NULL && is_numeric($entity->image_fid)) {
                $fids[] = $entity->image_fid;
            }
        }

        $images = \Drupal\file\Entity\File::loadMultiple($fids);
        foreach ($entities as $entity) {
            if ($entity->image_fid != NULL && isset($images[$entity->image_fid])) {
                $entity->image_url = $images[$entity->image_fid]->url();
            }
        }
    }
    
    public static function assignEntityRestaurants($entities) {
		$restaurant_ids = [];
        foreach ($entities as $entity) {
			if(!in_array($entity->restaurant_id, $restaurant_ids)) {
				$restaurant_ids[] = $entity->restaurant_id;
			}
        }
		
		if(count($restaurant_ids) == 0) {
			$restaurants = [];
		} else {
			$restaurants = \Drupal\food\Core\RestaurantController::searchRestaurants(function($query) use (&$restaurant_ids) {
				$query = $query
					->condition('fr.restaurant_id', $restaurant_ids, 'IN');

				return($query);
			});
		}

        foreach ($entities as $entity) {
			$restaurant = current(array_filter($restaurants, function($restaurant) use (&$entity) {
				return ($restaurant->restaurant_id == $entity->restaurant_id);
			}));
            $entity->restaurant = $restaurant;
            $entity->restaurant->restaurant_url = Url::fromRoute('entity.food_restaurant.canonical', ['food_restaurant' => $entity->restaurant_id])->toString();
        }
    }
}
