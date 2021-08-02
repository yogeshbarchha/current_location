<?php

namespace Drupal\food\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'RestaurantFilterFormBlock' Block.
 *
 * @Block(
 *   id = "restaurant_filter_form_block",
 *   admin_label = @Translation("Restaurant Filter Form"),
 *   category = @Translation("Food"),
 * )
 */
class RestaurantFilterFormBlock extends BlockBase {

	/**
	* {@inheritdoc}
	*/
	public function build() {
		$form = \Drupal::formBuilder()->getForm('Drupal\food\Form\Restaurant\RestaurantFilterForm');
		
		$form['cuisines'] = \Drupal\food\Core\CuisineController::getAllCuisines();
		$form['service_areas'] = \Drupal\food\Core\ServiceAreaController::getAllServiceAreas();
		$form['dishes'] = \Drupal\food\Core\DishController::getAllDishes();
		
		//$form['#theme'] = 'my_awesome_form';
		//$form['#cache']['max-age'] = 0;
		$form['#attached']['library'][] = 'food/form.restaurant.restaurantfilterformblock';
        $form['#attached']['drupalSettings']['food'] = array(
        );
		
		return ($form);
	}
}
