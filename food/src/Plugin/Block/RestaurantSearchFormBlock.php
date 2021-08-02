<?php

namespace Drupal\food\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Imbibe\Util\PhpHelper;

/**
 * Provides a 'RestaurantSearchFormBlock' Block.
 *
 * @Block(
 *   id = "restaurant_search_form_block",
 *   admin_label = @Translation("Restaurant Search Form"),
 *   category = @Translation("Food"),
 * )
 */
class RestaurantSearchFormBlock extends BlockBase {

	/**
	* {@inheritdoc}
	*/
	public function build() {
		$form = \Drupal::formBuilder()->getForm('Drupal\food\Form\Restaurant\RestaurantSearchForm');
		$platform_settings = \Drupal\food\Core\PlatformController::getPlatformSettings();

		$restaurants = \Drupal\food\Core\RestaurantController::getAllRestaurantsByStatus(\Drupal\food\Core\EntityStatus::Enabled);
		$restaurants_processed = [];
		foreach($restaurants as $restaurant) {
			$restaurants_processed[] = [
				'data' => array(
					'restaurant_id' => $restaurant->restaurant_id,
					'url' => Url::fromRoute('entity.food_restaurant.canonical', array('food_restaurant' => $restaurant->restaurant_id))->toString(),
				),
				'value' => $restaurant->name
			];
		}

		$dishes = \Drupal\food\Core\DishController::getActiveDishes();
		$dishes_processed = [];
		foreach($dishes as $dish) {
			$dishes_processed[] = [
				'data' => array(
					'dish_id' => $dish->dish_id,
					'url' => Url::fromRoute('entity.food_dish.canonical', array('food_dish' => $dish->dish_id))->toString(),
				),
				'value' => $dish->name
			];
		}
		
		//$form['#theme'] = 'my_awesome_form';
		//$form['#cache']['max-age'] = 0;

		$form['#attached']['library'][] = 'food/form.restaurant.restaurantsearchformblock';
        $form['#attached']['drupalSettings']['food'] = array(
			'isHomePage' => \Drupal::service('path.matcher')->isFrontPage(),
			'restaurants' => $restaurants_processed,
			'dishes' => $dishes_processed,
			'restaurantSearchPageUrl' => Url::fromRoute('food.search.restaurants.page')->toString(),
			'delivery_lookup_settings_json' => PhpHelper::getNestedValue($platform_settings, ['platform_google_settings', 'delivery_lookup_settings_json'], ''),
			'registerDirectRestaurantSearchUrl' => Url::fromRoute('food.cart.registerdirectrestaurantsearch')->toString(),
        );
		
		return ($form);
	}
}
