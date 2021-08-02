<?php

namespace Drupal\food\Form\Restaurant;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing;
use Drupal\Core\Url;
use Imbibe\Util\PhpHelper;

class RestaurantSearchForm extends FormBase {

    public function getFormId() {
        return 'food_restaurant_search_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
		$cart = \Drupal\food\Core\CartController::getCurrentCart(NULL, ['autoCreate' => FALSE]);
		$user_address = PhpHelper::getNestedValue($cart, ['search_params', 'user_address']);
		if(isset($_GET['search_params'])) {
			$search_params = \Imbibe\Json\JsonHelper::deserializeObject($_GET['search_params'], '\Drupal\food\Core\Cart\SearchParams');
			if(!empty($search_params->user_address)) {
				$user_address = $search_params->user_address;
			}
		}
		
        $form['address_search'] = array(
			'#type' => 'textfield',
			'#attributes' => array(
				'placeholder' => $this->t('Street address, city state'),
			),
			'#default_value' => $user_address,
        );

        $form['rest_search'] = array(
			'#type' => 'textfield',
			'#attributes' => array(
				'placeholder' => $this->t('Restaurant/ Takeway name'),
			),
        );
		
		//$form['#theme'] = 'food_restaurant_search_form';
		//$form['#cache']['max-age'] = 0;

        return ($form);
    }

    public function validateForm(array &$form, FormStateInterface $form_state) {        
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
    }

}
