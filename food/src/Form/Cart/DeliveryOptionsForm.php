<?php

namespace Drupal\food\Form\Cart;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing;
use Drupal\Core\Url;
use Imbibe\Util\PhpHelper;

class DeliveryOptionsForm extends FormBase {

    public function getFormId() {
        return 'food_user_cart_delivery_options_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
		$cart = \Drupal\food\Core\CartController::getCurrentCart();
		$user = \Drupal\user\Entity\User::load($cart->user_id);
		$platform_settings = \Drupal\food\Core\PlatformController::getPlatformSettings();
		$user_last_order = \Drupal\food\Core\OrderController::findOrders([
			'pageSize' => 1,
			'conditionCallback' => function($query) use (&$user) {             
	             		 $query->condition('user_id', $user->id());
		                 $query->orderBy('order_id', 'DESC');
           			 return($query);
          		}
		]);
			if($cart->order_details->delivery_mode == \Drupal\food\Core\Restaurant\DeliveryMode::Delivery) {

		$form['type'] = array(
	            '#type' => 'radios',
	            '#title' => t('Type'),
	            '#options' => array(
	                0 => t('Home'),
	                1 => t('Office'),
	            ),
	            '#default_value' => 0,
       		);
          }
		
		$form['user_name'] = array(
			'#type' => 'textfield',
			'#title' => $this->t('Name'),
			'#required' => TRUE,
			'#default_value' => empty($cart->order_details->user_name) ? $user->getDisplayName() : $cart->order_details->user_name,
		);
		$form['user_phone'] = array(
			'#type' => 'tel',
			'#title' => $this->t('Phone'),
			'#required' => TRUE,
			'#default_value' => isset($user_last_order[0]->user_phone) ? $user_last_order[0]->user_phone : $cart->order_details->user_phone,
			'#attributes' => [
				'maxlength' => 10,
			],
		);
		$form_button_val ="Checkout" ;
		if($cart->order_details->delivery_mode == \Drupal\food\Core\Restaurant\DeliveryMode::Delivery) {
			$form_button_val = "Save & Checkout";
			$form['user_address'] = array(
				'#type' => 'textfield',
				'#title' => $this->t('Street name, Number & City'),
				'#required' => TRUE,
				'#default_value' => empty($cart->order_details->user_address) ? $cart->search_params->user_address : $cart->order_details->user_address,
			);
			$form['user_apartment_number'] = array(
				'#type' => 'textfield',
				'#title' => $this->t('House No./Apt/Suite/Building'),
				'#default_value' => $cart->order_details->user_apartment_number,
			);
			$form['user_address_latitude'] = array(
				'#type' => 'hidden',
				'#default_value' => empty($cart->order_details->user_address_latitude) ? $cart->search_params->latitude : $cart->order_details->user_address_latitude,
			);
			$form['user_address_longitude'] = array(
				'#type' => 'hidden',
				'#default_value' => empty($cart->order_details->user_address_longitude) ? $cart->search_params->longitude : $cart->order_details->user_address_longitude,
			);
			$form['user_address_id'] = array(
				'#type' => 'hidden',
			);
				

			$form['address_wrapper'] = array(
	            '#type' => 'fieldset',
	            '#title' => t('More Information'),
	            '#prefix' => '<div class="more-address-wrapper">',
	            '#suffix' => '</div>',
       		);

       		$form['address_wrapper']['address_save'] = array(
       			'#type' => 'hidden',
       			'#value' => 0,
       		);

		

        	$form['address_wrapper']['postal_code'] = array(
	            '#type' => 'textfield',
	            '#title' => t('Postal Code'),
        	);

        	$form['address_wrapper']['actions']['save_address'] = array(
				'#type' => 'submit',
				'#value' => $this->t('Save Address'),
				'#attributes' => [
				'class' => ['btn btn-success'],
				],
        	);
		}

        $form['#attached']['drupalSettings']['food'] = array(
			'delivery_lookup_settings_json' => PhpHelper::getNestedValue($platform_settings, ['platform_google_settings', 'delivery_lookup_settings_json'], ''),
        );

        $form['actions']['#type'] = 'actions';
        $form['actions']['modify_cart'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Modify cart'),
            '#attributes' => [
				'class' => ['btn-modify-cart'],
			],
        );
        $form['actions']['submit'] = array(
            '#type' => 'submit',
            '#value' => $this->t($form_button_val),
            '#button_type' => 'primary'
        );

        return ($form);
    }

    public function validateForm(array &$form, FormStateInterface $form_state) {
    	$triggering_element = $form_state->getTriggeringElement();



    	if($triggering_element['#value'] == 'Checkout'){
         if (!is_numeric($form_state->getValue('user_phone'))) {
					$form_state->setErrorByName('user_phone', $this->t('Phone number must be numeric'));
                }
			$cart = \Drupal\food\Core\CartController::getCurrentCart();
			if ($cart->order_details->delivery_mode == \Drupal\food\Core\Restaurant\DeliveryMode::Delivery) {
				$isDeliverable = \Drupal\food\Core\RestaurantController::isDeliverableByRestaurantId($cart->restaurant_id, $form_state->getValue('user_address_latitude'), $form_state->getValue('user_address_longitude'));
				
				if($isDeliverable == FALSE) {
					$form_state->setErrorByName('user_address', $this->t('The specified address is not deliverable by this restaurant!'));
				}
                          
			}
		}
		if($triggering_element['#value'] == 'Save & Checkout'){
         
          $cart = \Drupal\food\Core\CartController::getCurrentCart();
			if ($cart->order_details->delivery_mode == \Drupal\food\Core\Restaurant\DeliveryMode::Delivery) {
				$isDeliverable = \Drupal\food\Core\RestaurantController::isDeliverableByRestaurantId($cart->restaurant_id, $form_state->getValue('user_address_latitude'), $form_state->getValue('user_address_longitude'));
				if($isDeliverable == FALSE) {
					$form_state->setErrorByName('user_address', $this->t('The specified address is not deliverable by this restaurant!'));
				}elseif (!is_numeric($form_state->getValue('user_phone'))) {
					$form_state->setErrorByName('user_phone', $this->t('Phone number must be numeric'));
                }elseif($isDeliverable == FALSE) {
					$form_state->setErrorByName('user_address', $this->t('The specified address is not deliverable by this restaurant!'));
				}else {

                     self::saveAddress($form_state);

				}
			}
		}
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
    	$triggering_element = $form_state->getTriggeringElement();

    	if(($triggering_element['#value'] == 'Save & Checkout')||($triggering_element['#value'] == 'Checkout')){

			$cart = \Drupal\food\Core\CartController::getCurrentCart();
			
			$cart->order_details->user_name = $form_state->getValue('user_name');
			$cart->order_details->user_phone = $form_state->getValue('user_phone');

			$cart->order_details->user_address_id = $form_state->getValue('user_address_id');
			$cart->order_details->user_address = $form_state->getValue('user_address');
			$cart->order_details->user_apartment_number = $form_state->getValue('user_apartment_number');
			$cart->order_details->user_address_latitude = $form_state->getValue('user_address_latitude');
			$cart->order_details->user_address_longitude = $form_state->getValue('user_address_longitude');
			if(empty($cart->order_details->user_address_id)) {
				$cart->order_details->user_address_id = NULL;
			}
			
			\Drupal\food\Core\CartController::updateCart($cart);

	        $url = Url::fromRoute('food.cart.placeorder');
	        $form_state->setRedirectUrl($url);    		
    	}
    }

    public function saveAddress($form_state){	
	$cart = \Drupal\food\Core\CartController::getCurrentCart();
    	$user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());

    	$result = db_select('food_user_address', 'fua')
	       ->fields('fua')
	       ->condition('address_line1',	trim($form_state->getValue('user_address')))
	       ->execute()
	       ->fetchAll();

	    if(empty($result)){
	  
	    	$entity = array(
	            'type' => $form_state->getValue('type'),
	            'contact_name' => $form_state->getValue('user_name'),
	            'phone_number' => $form_state->getValue('user_phone'),
	            'email' => $form_state->getValue('email'),
	            'address_line1' => $form_state->getValue('user_address'),
	            'address_line2' => $form_state->getValue('user_apartment_number'),
	            //'city' => $form_state->getValue('city'),
	            //'state' => $form_state->getValue('state'),
	            'postal_code' => $form_state->getValue('postal_code'),
	            //'country' => $form_state->getValue('country'),
	            'latitude' => !empty($form_state->getValue('user_address_latitude')) ? $form_state->getValue('user_address_latitude') : 0,
	            'longitude' => !empty($form_state->getValue('user_address_longitude')) ? $form_state->getValue('user_address_longitude') : 0,
	            'owner_user_id' => $user->id(),
				'created_time' => \Imbibe\Util\TimeUtil::now(),
	        );
	       $last_address_id = db_insert('food_user_address')
                 ->fields($entity)
                 ->execute();
               $cart->order_details->user_address_id = $last_address_id;
               drupal_set_message(t('Address Added Successfully.'),'status');
	    }else{
		if(isset($result[0]->address_id)){
	    		$cart->order_details->user_address_id = $result[0]->address_id;
	    	}
            }
	    \Drupal\food\Core\CartController::updateCart($cart);
    }

}
