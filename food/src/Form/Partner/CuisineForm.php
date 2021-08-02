<?php

namespace Drupal\food\Form\Partner;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing;
use Drupal\Core\Url;

class CuisineForm extends FormBase {

    public function getFormId() {        
        return 'food_restaurant_cuisine_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state, $restaurant_id = NULL) {
        $rows = \Drupal\food\Core\CuisineController::getAllCuisines();
        $values = array();
        foreach ($rows as &$row) {
            $values[$row->cuisine_id] = $row->name;
        }
		
        $form['cuisine_id'] = array(
            '#type' => 'select',
            '#title' => $this->t('Select Cuisine'),
            '#required' => TRUE,
            '#options' => $values,
        );

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Submit'),
            '#button_type' => 'primary',
        );
        return ($form);
    }

    public function validateForm(array &$form, FormStateInterface $form_state) {
        $restaurant_id = \Drupal\food\Util::getRestaurantIdFromUrl();
        $cuisine_id = $form_state->getValue('cuisine_id');
		
        $query = db_select('food_restaurant_cuisine', 'fm')
            ->fields('fm')
            ->condition('fm.restaurant_id', $restaurant_id)
            ->condition('fm.cuisine_id', $cuisine_id);
			
        $validator = $query->countQuery()->execute()->fetchField();
        if ($validator > 0) {
            $form_state->setErrorByName('cuisine_id', $this->t('The cuisine already exists for the selected restaurant!'));
        }
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        $restaurant_id = \Drupal\food\Util::getRestaurantIdFromUrl();
		$entity = $this->getEntity();

        $entity = array_merge($entity, array(
			'cuisine_id' => $form_state->getValue('cuisine_id'),
        ));
		
		db_insert('food_restaurant_cuisine')
			->fields($entity)
			->execute();

		drupal_set_message(t('Cuisine added for restaurant successfully.'));

         \Drupal\food\Core\RestaurantController::updateRestaurant($restaurant_id);
		
		$url = Url::fromRoute('food.partner.restaurant.cuisine.list', array('restaurant_id' => $entity['restaurant_id']));
		$form_state->setRedirectUrl($url);
    }

    private function getEntity($createDefault = TRUE) {
        $restaurant_id = \Drupal\food\Util::getRestaurantIdFromUrl();
        $restaurant_cuisine_id = \Drupal::routeMatch()->getParameter('restaurant_cuisine_id');
		
		$entity = NULL;
		if($restaurant_cuisine_id != NULL) {
			$entity = \Drupal\food\Core\CuisineController::getRestaurantCuisine($restaurant_cuisine_id);
			$entity = (array) $entity;
		} else {
			if ($createDefault) {
				$restaurant = \Drupal\food\Core\RestaurantController::getRestaurantById($restaurant_id);
				$entity = array(
					'owner_user_id' => $restaurant->owner_user_id,
					'restaurant_id' => $restaurant_id,
				);
			}
		}

        return($entity);
    }

}
