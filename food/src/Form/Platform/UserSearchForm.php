<?php

namespace Drupal\food\Form\Platform;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing;
use Drupal\Core\Url;

class UserSearchForm extends FormBase {

    public function getFormId() {        
        return 'food_platform_user_search_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state, $restaurant_id = NULL) {
        
          $rows = \Drupal\food\Core\RestaurantController::getCurrentUserRestaurants();
        $restaurantOptions = array('' => $this->t('Select'));
        foreach ($rows as &$row) {
            $restaurantOptions[$row->restaurant_id] = $row->name;
        }
        
      

        $form['order_id'] = array(
            '#type' => 'number',
            '#title' => $this->t('Order#'),
        );
          $form['restaurant_id'] = array(
            '#type' => 'select',
            '#title' => $this->t('Select Restaurant'),
            '#options' => $restaurantOptions,
        );
        $form['user_name'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Name'),
        );
        $form['user_email'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Email ID'),
        );
         $form['user_phone'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Phone Number'),
        );
        $form['user_address'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Address'),
        );
        
        $form['created_time'] = array(
            '#type' => 'date',
            '#title' => t('Created time'),
        );
        
        $form['order_amount'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Amount'),
        );
        
        $form['delivery_mode'] = array(
            '#type' => 'select',
            '#title' => $this->t('Order Mode'),
            '#options' => array(
                '' => t('Please select'),
                \Drupal\food\Core\Restaurant\DeliveryMode::Delivery => t('Delivery'),
                \Drupal\food\Core\Restaurant\DeliveryMode::Pickup => t('Pickup'),
            ),
        );
        
        $form['submit'] = array(
            '#type' => 'button',
            '#value' => t('Search'),
            '#button_type' => 'primary',
        );
        
        return ($form);
    }

    public function validateForm(array &$form, FormStateInterface $form_state) {
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
    }

}
