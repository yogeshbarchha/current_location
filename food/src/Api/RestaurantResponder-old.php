<?php

namespace Drupal\food\Api;

abstract class RestaurantResponder extends ApiResponderBase {

    public static function searchRestaurants() {
        $search_params = \Imbibe\Json\JsonHelper::deserializeObject($_POST['search_params'], '\Drupal\food\Core\Cart\SearchParams');
        $restaurants = \Drupal\food\Core\RestaurantController::searchRestaurantsBySearchParams($search_params);

        return(array('success' => true, 'data' => $restaurants));
    }

    public static function getRestaurantMenus() {
        $restaurant_id = $_POST['restaurant_id'];
        $data = \Drupal\food\Core\MenuController::getRestaurantMenusWithSectionsAndItems($restaurant_id);

        return(array('success' => true, 'data' => $data));
    }

    public static function getRouletteDiscounts() {
        parent::initializeAuthenticatedRequest();

        $restaurant_id = self::getPostVariable('restaurant_id', TRUE);
        $data = \Drupal\food\Core\RestaurantController::getRestaurantRouletteDiscounts($restaurant_id);

        return(array('success' => true, 'data' => $data));
    }

    public static function getRestaurantOrderPlacementOptions() {
        //parent::initializeAuthenticatedRequest();

        $restaurant_id = self::getPostVariable('restaurant_id', TRUE);
		$tip_pcts = \Drupal\food\Core\RestaurantController::getRestaurantTipPercentages($restaurant_id);
		$condiments = \Drupal\food\Core\RestaurantController::getRestaurantCondiments($restaurant_id);

        return(array('success' => true, 'data' => ['tip_pcts' => $tip_pcts, 'condiments' => $condiments]));
    }

    public static function isAddressDeliverable() {
        $restaurant_id = $_POST['restaurant_id'];
        $latitude = $_POST['latitude'];
        $longitude = $_POST['longitude'];
        $isDeliverable = \Drupal\food\Core\RestaurantController::isDeliverableByRestaurantId($restaurant_id, $latitude, $longitude);

        return(array('success' => true, 'isDeliverable' => $isDeliverable));
    }

}
