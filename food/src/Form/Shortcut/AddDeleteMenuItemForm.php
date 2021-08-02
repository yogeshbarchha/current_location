<?php

namespace Drupal\food\Form\Shortcut;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing;
use Drupal\Core\Url;
use Drupal\food\Core\RestaurantController;
use Drupal\food\Core\RoleController;

class AddDeleteMenuItemForm extends FormBase {

    public function getFormId() {
        return 'food_add_delete_menu_item_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state, $restaurants = NULL) {

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
      '#default_value' => (array) $selectedOrderTypes,
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
      '#default_value' => $entity != NULL && $entity->order_types != NULL && $entity->order_types->delivery_settings != NULL ? $entity->order_types->delivery_settings->estimated_delivery_time_minutes : '',
    );
    $form['accepted_order_type']['delivery']['minimum_order'] = array(
      '#type' => 'number',
      '#title' => $this->t('Minimum Order'),
      '#default_value' => $entity != NULL && $entity->order_types != NULL && $entity->order_types->delivery_settings != NULL ? $entity->order_types->delivery_settings->minimum_order_amount : '',
      '#step' => .001,
    );
    $form['accepted_order_type']['delivery']['delivery_charge'] = array(
      '#type' => 'number',
      '#title' => $this->t('Delivery Charge'),
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
      '#default_value' => $entity != NULL && $entity->order_types != NULL && $entity->order_types->pickup_settings != NULL ? $entity->order_types->pickup_settings->estimated_pickup_time_minutes : '',
    );

    $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Update'),
            '#button_type' => 'primary',
        );

    $form['#attached']['library'][] = 'food/form.shortcut.pickupdeliveryform';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    print "<pre>";print_r($form_state->getValues());die();
  }

  public function changePickupDeliveryAjax(array $form, FormStateInterface $form_state) {
    $form['accepted_order_type'] = array(
        '#type' => 'fieldset',
        '#title' => $this->t('Order Type'),
        '#prefix' => '<div id="pickup_delivery_field_wrapper">',
        '#suffix' => '</div>',
    );
    $entity = \Drupal\food\Core\RestaurantController::getRestaurantById($form_state->getValue('restaurant'));

     $order_types = $entity != NULL ? $entity->order_types : NULL;
    $selectedOrderTypes = array();
    if ($order_types != NULL && $order_types->delivery_settings != NULL && $order_types->delivery_settings->enabled) {
      $selectedOrderTypes[\Drupal\food\Core\Restaurant\DeliveryMode::Delivery] = \Drupal\food\Core\Restaurant\DeliveryMode::Delivery;
    }
    if ($order_types != NULL && $order_types->pickup_settings != NULL && $order_types->pickup_settings->enabled) {
      $selectedOrderTypes[\Drupal\food\Core\Restaurant\DeliveryMode::Pickup] = \Drupal\food\Core\Restaurant\DeliveryMode::Pickup;
    }
    
    $form['accepted_order_type']['order_types'] = array(
      '#type' => 'checkboxes',
      '#options' => array(
        \Drupal\food\Core\Restaurant\DeliveryMode::Delivery => t('Delivery'),
        \Drupal\food\Core\Restaurant\DeliveryMode::Pickup => t('Pickup'),
      ),
      '#attributes' => array(
        'class' => array(
          'order_types',
        ),
      ),
      '#value' => (array) $selectedOrderTypes,
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
    );
    $form['accepted_order_type']['delivery']['minimum_order'] = array(
      '#type' => 'number',
      '#title' => $this->t('Minimum Order'),
      '#value' => $entity != NULL && $entity->order_types != NULL && $entity->order_types->delivery_settings != NULL ? $entity->order_types->delivery_settings->minimum_order_amount : '',
      '#step' => .001,
    );
    $form['accepted_order_type']['delivery']['delivery_charge'] = array(
      '#type' => 'number',
      '#title' => $this->t('Delivery Charge'),
      '#value' => $entity != NULL && $entity->order_types != NULL && $entity->order_types->delivery_settings != NULL ? $entity->order_types->delivery_settings->delivery_charges_amount : '',
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
    );

    return $form['accepted_order_type'];
  }

  public function getRestaurantData($restaurant_id) {

    $entity = \Drupal\food\Core\RestaurantController::getRestaurantById($restaurant_id);

     $order_types = $entity != NULL ? $entity->order_types : NULL;
    $selectedOrderTypes = array();
    if ($order_types != NULL && $order_types->delivery_settings != NULL && $order_types->delivery_settings->enabled) {
      $selectedOrderTypes[\Drupal\food\Core\Restaurant\DeliveryMode::Delivery] = \Drupal\food\Core\Restaurant\DeliveryMode::Delivery;
    }
    if ($order_types != NULL && $order_types->pickup_settings != NULL && $order_types->pickup_settings->enabled) {
      $selectedOrderTypes[\Drupal\food\Core\Restaurant\DeliveryMode::Pickup] = \Drupal\food\Core\Restaurant\DeliveryMode::Pickup;
    }

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
      '#default_value' => (array) $selectedOrderTypes,
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
      '#default_value' => $entity != NULL && $entity->order_types != NULL && $entity->order_types->delivery_settings != NULL ? $entity->order_types->delivery_settings->estimated_delivery_time_minutes : '',
    );
    $form['accepted_order_type']['delivery']['minimum_order'] = array(
      '#type' => 'number',
      '#title' => $this->t('Minimum Order'),
      '#default_value' => $entity != NULL && $entity->order_types != NULL && $entity->order_types->delivery_settings != NULL ? $entity->order_types->delivery_settings->minimum_order_amount : '',
      '#step' => .001,
    );
    $form['accepted_order_type']['delivery']['delivery_charge'] = array(
      '#type' => 'number',
      '#title' => $this->t('Delivery Charge'),
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
      '#default_value' => $entity != NULL && $entity->order_types != NULL && $entity->order_types->pickup_settings != NULL ? $entity->order_types->pickup_settings->estimated_pickup_time_minutes : '',
    );

    return $form;

  }  

  

}
