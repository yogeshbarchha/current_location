<?php

namespace Drupal\food\Form\Restaurant;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Link;
use Drupal\Core\Routing;

class RestaurantDeals extends ControllerBase {

    public function show() {
        $restaurant_id = \Drupal\food\Util::getRestaurantIdFromUrl();
        $restaurant = \Drupal\food\Core\RestaurantController::getRestaurantById($restaurant_id);
		
		$build = array(
			'#markup' => '',
			'#theme' => 'food_restaurant_deal_list',
			'additionalData' => [
				'restaurant' => $restaurant,
			],
			//'#attached' => ['library' => ['food/form.user.cartblock', 'food/form.user.addcartitemform']],
		);
		
        return ($build);
    }

}
