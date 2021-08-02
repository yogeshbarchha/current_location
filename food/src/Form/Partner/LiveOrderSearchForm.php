<?php

namespace Drupal\food\Form\Partner;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing;
use Drupal\Core\Url;

class LiveOrderSearchForm extends FormBase {

    public function getFormId() {        
        return 'food_partner_live_order_search_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state, $restaurant_id = NULL) {
        $rows = \Drupal\food\Core\RestaurantController::getCurrentUserRestaurants();
        $values = array('' => $this->t('Select'));
        foreach ($rows as &$row) {
            $values[$row->restaurant_id] = $row->name;
        }
		
        $form['restaurant_id'] = array(
            '#type' => 'select',
            '#title' => $this->t('Select Restaurant'),
            '#options' => $values,
        );

        $form['order_id'] = array(
            '#type' => 'number',
            '#title' => $this->t('Order#'),
            '#options' => $values,
        );
        
        $form['user_name'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Name'),
        );
        
        $form['user_phone'] = array(
            '#type' => 'number',
            '#title' => $this->t('Phone Number'),
        );
        
        $form['user_address'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Address'),
        );
        
        $form['start_date'] = array(
            '#type' => 'date',
            '#title' => t('Start Date'),
        );
        
        $form['end_date'] = array(
            '#type' => 'date',
            '#title' => t('End Date'),
        );
        
        $form['order_amount'] = array(
            '#type' => 'number',
            '#title' => $this->t('Amount'),
        );
        
        $form['order_status'] = array(
            '#type' => 'checkboxes',
            '#options' => array(
                \Drupal\food\Core\Order\OrderStatus::Submitted => $this->t('Order pending'),
                \Drupal\food\Core\Order\OrderStatus::Confirmed => $this->t('Order confirmed'),
                \Drupal\food\Core\Order\OrderStatus::Cancelled => $this->t('Order cancelled'),
            ),
            '#attributes' => array(
                'class' => array(
                    'order_status'
                )
            ),
           '#default_value' => array(
                \Drupal\food\Core\Order\OrderStatus::Submitted,
                \Drupal\food\Core\Order\OrderStatus::Confirmed,
                \Drupal\food\Core\Order\OrderStatus::Cancelled,
            ),
        );
        
        $form['delivery_mode'] = array(
            '#type' => 'select',
            '#title' => $this->t('Delivery Mode'),
            '#options' => array(
				'' => t('Please select'),
				\Drupal\food\Core\Restaurant\DeliveryMode::Delivery => t('Delivery'),
				\Drupal\food\Core\Restaurant\DeliveryMode::Pickup => t('Pickup'),
			),
        );
        
        $form['payment_mode'] = array(
            '#type' => 'select',
            '#title' => $this->t('Payment Mode'),
            '#options' => array(
				'' => t('Please select'),
				\Drupal\food\Core\Restaurant\PaymentMode::CashOnDelivery => t('Cash On Delivery'),
				\Drupal\food\Core\Restaurant\PaymentMode::Card => t('Card'),
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
