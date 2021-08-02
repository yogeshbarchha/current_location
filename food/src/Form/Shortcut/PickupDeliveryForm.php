<?php

namespace Drupal\food\Form\Shortcut;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing;
use Drupal\Core\Url;
use Drupal\food\Core\RestaurantController;
use Drupal\food\Core\RoleController;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;


class PickupDeliveryForm extends FormBase {

    public function getFormId() {
        return 'food_pickup_delivery_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state, $restaurants = NULL) {
        
    if(empty($restaurants)){
        $form['empty'] = array(
            '#type' => 'item',
            '#markup' => '<p>No Restaurants Found</p>',
        );

        return $form;
    }

    if (empty($form_state->getValue('restaurant'))) {
        $default_restaurant = key($restaurants);
    }else{
        $default_restaurant = $form_state->getValue('restaurant');            
    }

    $entity = \Drupal\food\Core\RestaurantController::getRestaurantById($default_restaurant);

    $order_types = $entity != NULL ? $entity->order_types : NULL;
    $selectedOrderTypes = array();
    if ($order_types != NULL && $order_types->delivery_settings != NULL && $order_types->delivery_settings->enabled) {
      $selectedOrderTypes[\Drupal\food\Core\Restaurant\DeliveryMode::Delivery] = \Drupal\food\Core\Restaurant\DeliveryMode::Delivery;
    }
    if ($order_types != NULL && $order_types->pickup_settings != NULL && $order_types->pickup_settings->enabled) {
      $selectedOrderTypes[\Drupal\food\Core\Restaurant\DeliveryMode::Pickup] = \Drupal\food\Core\Restaurant\DeliveryMode::Pickup;
    }

    $form['status_message'] = array(
          '#type' => 'markup',
          '#markup' => '<div id="pickupdelivery-message-wrapper"></div>',
    );

    $form['restaurant'] = array(
        '#type' => 'select',
        '#title' => $this->t('Restaurant'),
        '#options' => $restaurants,
        '#required' => TRUE,
        '#default_value' => $default_restaurant,
        '#ajax' => array(
            'callback' => '::changePickupDeliveryAjax',
            'wrapper' => 'pickup_delivery_field_wrapper',
            'method' => 'replace',
         ),
    );
        
    $form['accepted_order_type'] = array(
        '#type' => 'fieldset',
        '#title' => $this->t('Order Type'),
        '#prefix' => '<div id="pickup_delivery_field_wrapper">',
        '#suffix' => '</div>',
    );

    
    $form['accepted_order_type']['order_types'] = array(
      '#type' => 'checkboxes',
      '#options' => array(
        \Drupal\food\Core\Restaurant\DeliveryMode::Delivery => $this->t('Delivery'),
        \Drupal\food\Core\Restaurant\DeliveryMode::Pickup => $this->t('Pickup'),
      ),
      '#attributes' => array(
        'class' => array(
          'order_types',
        ),
      ),
      '#value' => (array)$selectedOrderTypes,
    );

    $form['accepted_order_type']['delivery'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array(
          'order_type_delivery',
        ),
      ),
      '#states' => array(
        'invisible' => array(
          'input[name="order_types[delivery]"]' => array('checked' => FALSE),
        ),
      ),
    );

    $form['accepted_order_type']['delivery']['delivery_time'] = array(
      '#type' => 'number',
      '#title' => $this->t('Delivery Time'),
      '#value' => $entity != NULL && $entity->order_types != NULL && $entity->order_types->delivery_settings != NULL ? $entity->order_types->delivery_settings->estimated_delivery_time_minutes : '',
      '#default_value' => $entity != NULL && $entity->order_types != NULL && $entity->order_types->delivery_settings != NULL ? $entity->order_types->delivery_settings->estimated_delivery_time_minutes : '',
    );
    $form['accepted_order_type']['delivery']['minimum_order'] = array(
      '#type' => 'number',
      '#title' => $this->t('Minimum Order'),
      '#value' => $entity != NULL && $entity->order_types != NULL && $entity->order_types->delivery_settings != NULL ? $entity->order_types->delivery_settings->minimum_order_amount : '',
      '#default_value' => $entity != NULL && $entity->order_types != NULL && $entity->order_types->delivery_settings != NULL ? $entity->order_types->delivery_settings->minimum_order_amount : '',
      '#step' => .001,
    );
    $form['accepted_order_type']['delivery']['delivery_charge'] = array(
      '#type' => 'number',
      '#title' => $this->t('Delivery Charge'),
      '#value' => $entity != NULL && $entity->order_types != NULL && $entity->order_types->delivery_settings != NULL ? $entity->order_types->delivery_settings->delivery_charges_amount : '',
      '#default_value' => $entity != NULL && $entity->order_types != NULL && $entity->order_types->delivery_settings != NULL ? $entity->order_types->delivery_settings->delivery_charges_amount : '',
      '#step' => .001,
    );
    $form['accepted_order_type']['pickup'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array(
          'order_type_pickup',
        ),
      ),
      '#states' => array(
        'invisible' => array(
          'input[name="order_types[pickup]"]' => array('checked' => FALSE),
        ),
      ),
    );
    $form['accepted_order_type']['pickup']['pickup_time'] = array(
      '#type' => 'number',
      '#title' => $this->t('Pickup Time'),
      '#value' => $entity != NULL && $entity->order_types != NULL && $entity->order_types->pickup_settings != NULL ? $entity->order_types->pickup_settings->estimated_pickup_time_minutes : '',
      '#default_value' => $entity != NULL && $entity->order_types != NULL && $entity->order_types->pickup_settings != NULL ? $entity->order_types->pickup_settings->estimated_pickup_time_minutes : '',
    );

    $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Update'),
            '#button_type' => 'primary',
            '#ajax' => array(
              'callback' => '::promptCallback',
              'wrapper' => 'pickupdelivery-message-wrapper',
            ),
        );

    $form['#attached']['library'][] = 'food/form.shortcut.pickupdeliveryform';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

  public function changePickupDeliveryAjax(array $form, FormStateInterface $form_state) {
    $form['accepted_order_type']['order_types'][\Drupal\food\Core\Restaurant\DeliveryMode::Delivery]['#checked'] = FALSE;
    $form['accepted_order_type']['order_types'][\Drupal\food\Core\Restaurant\DeliveryMode::Pickup]['#checked'] = FALSE;
    
    if(!empty($form_state->getValue('restaurant')) && is_numeric($form_state->getValue('restaurant'))){
        $entity = \Drupal\food\Core\RestaurantController::getRestaurantById($form_state->getValue('restaurant'));

        $order_types = $entity != NULL ? $entity->order_types : NULL;
        $selectedOrderTypes = array();
        if ($order_types != NULL && $order_types->delivery_settings != NULL && $order_types->delivery_settings->enabled) {
            $selectedOrderTypes[\Drupal\food\Core\Restaurant\DeliveryMode::Delivery] = \Drupal\food\Core\Restaurant\DeliveryMode::Delivery;
        }
        if ($order_types != NULL && $order_types->pickup_settings != NULL && $order_types->pickup_settings->enabled) {
            $selectedOrderTypes[\Drupal\food\Core\Restaurant\DeliveryMode::Pickup] = \Drupal\food\Core\Restaurant\DeliveryMode::Pickup;
        }

        if(!empty($selectedOrderTypes)){
            $form['accepted_order_type']['order_types'][\Drupal\food\Core\Restaurant\DeliveryMode::Delivery]['#checked'] = isset($selectedOrderTypes[\Drupal\food\Core\Restaurant\DeliveryMode::Delivery]) ? TRUE : FALSE;
            $form['accepted_order_type']['order_types'][\Drupal\food\Core\Restaurant\DeliveryMode::Pickup]['#checked'] = isset($selectedOrderTypes[\Drupal\food\Core\Restaurant\DeliveryMode::Pickup]) ? TRUE : FALSE;
        }
    }

        //   echo $form_state->getValue('delivery_time');

    return $form['accepted_order_type'];
  }

  public function promptCallback(array &$form, FormStateInterface $form_state) {
      $values = $form_state->getUserInput();
   	$ajax_response = new AjaxResponse();
   	$error = FALSE;
    $text  = '';

    if(!empty($values['restaurant']) && is_numeric($values['restaurant'])){
    	if(!$values['order_types'][\Drupal\food\Core\Restaurant\DeliveryMode::Delivery] && !$values['order_types'][\Drupal\food\Core\Restaurant\DeliveryMode::Pickup]){
    		$error = TRUE;
    		$text .= '<div class="alert alert-danger alert-dismissable">
               <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
               Please Select order type.
               </div>';	

    	}

    }else{
    	$error = TRUE;
        $text .= '<div class="alert alert-danger alert-dismissable">
               <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
               Restaurant Not Found.
               </div>';
    }

    if(!$error){

		$entity = \Drupal::entityTypeManager()->getStorage('food_restaurant')->load($values['restaurant']);

		$order_types = new \Drupal\food\Core\Restaurant\OrderTypeSettings();
		$order_types->delivery_settings = new \Drupal\food\Core\Restaurant\DeliverySettings();
		$order_types->delivery_settings->enabled = $values['order_types'][\Drupal\food\Core\Restaurant\DeliveryMode::Delivery] == \Drupal\food\Core\Restaurant\DeliveryMode::Delivery;
		$order_types->delivery_settings->estimated_delivery_time_minutes = $values['delivery_time'];
		$order_types->delivery_settings->minimum_order_amount = $values['minimum_order'];
		$order_types->delivery_settings->delivery_charges_amount = $values['delivery_charge'];
		$order_types->pickup_settings = new \Drupal\food\Core\Restaurant\PickupSettings();
		$order_types->pickup_settings->enabled = $values['order_types'][\Drupal\food\Core\Restaurant\DeliveryMode::Pickup] == \Drupal\food\Core\Restaurant\DeliveryMode::Pickup;
		$order_types->pickup_settings->estimated_pickup_time_minutes = $values['pickup_time'];

		$entity->set('order_types', json_encode($order_types));
		$entity->setChangedTime(REQUEST_TIME);
		$entity->save();
      	$text .='<div class="alert alert-success alert-dismissable">
        	       <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
            	   Pickup and Delivery time updated successfully.
               	</div>';
    }

    $ajax_response->addCommand(new HtmlCommand('#pickupdelivery-message-wrapper', $text)); 
	return $ajax_response;
  }  

}
