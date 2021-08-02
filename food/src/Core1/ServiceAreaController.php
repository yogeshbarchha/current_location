<?php

namespace Drupal\food\Core;

use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Link;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Imbibe\Util\PhpHelper;

abstract class ServiceAreaController extends ControllerBase {

    public static function getAllServiceAreas($config = array()) {
		$query = db_select('food_service_area', 'fsa')
			->fields('fsa');

		$config['defaultSortField'] = 'name';
		$config['pageSize'] = 0;
        $config['hydrateCallback'] = array('\Drupal\food\Core\ServiceAreaController', 'hydrateServiceArea');
		$rows = ControllerBase::executeListQuery($query, $config);
        return($rows);
    }
    
    public static function getServiceAreaById($service_area_id) {
        $query = db_select('food_service_area', 'fsa')
            ->condition('service_area_id', $service_area_id)
            ->fields('fsa');

        $row = ControllerBase::executeRowQuery($query, array('\Drupal\food\Core\ServiceAreaController', 'hydrateServiceArea'));
        return($row);
    }

    public static function hydrateServiceArea($row) {
		$row->derived_fields = new \StdClass();
    }

}
