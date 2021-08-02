<?php

namespace Drupal\food\Form\Cart;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Imbibe\Util\PhpHelper;
use Symfony\Component\HttpFoundation\RedirectResponse;

class OrderPlacementForm extends FormBase {

  public function getFormId() {
    return 'food_user_cart_order_placement_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $platform_settings = \Drupal\food\Core\PlatformController::getPlatformSettings();
    $cart = \Drupal\food\Core\CartController::getCurrentCart();
    $restaurant = \Drupal\food\Core\RestaurantController::getRestaurantById($cart->restaurant_id);

    $validationResult = $this->validateCart($restaurant, $cart);
    if ($validationResult == FALSE) {
      $url = Url::fromRoute('entity.food_restaurant.canonical',
        ['food_restaurant' => $restaurant->restaurant_id]);
      return new RedirectResponse($url->toString());
    }
    $form['back_button'] = array(
      '#type' => 'link',
      '#title' => t('Back'),
      '#url' => Url::fromRoute('food.cart.deliveryoptions'),
    );

    $form['user_details'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('User Details'),
    );
    $form['user_details']['user_name'] = array(
      '#type' => 'item',
      '#title' => $this->t('Name'),
      '#markup' => $cart->order_details->user_name,
    );
    $form['user_details']['user_phone'] = array(
      '#type' => 'item',
      '#title' => $this->t('Phone'),
      '#markup' => $cart->order_details->user_phone,
    );


    $form['order_options'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Options'),
    );
    if (PhpHelper::getNestedValue($platform_settings,
        ['order_settings', 'disable_tip'], FALSE) == FALSE) {
      $tip_pcts = \Drupal\food\Core\RestaurantController::getRestaurantTipPercentages($cart->restaurant_id);
      $options = [];
      foreach ($tip_pcts as $tip_pct) {
        $options[$tip_pct['tip_pct']] = $tip_pct['text'];
      }
      $form['order_options']['tip_pct'] = array(
        '#type' => 'select',
        '#title' => $this->t('Tip Percentage'),
        '#options' => $options,
      );
      $form['order_options']['tip_manual_amount'] = array(
        '#type' => 'number',
        '#title' => $this->t('Tip Amount'),
        '#attributes' => array(
          'class' => array(
            'manual-tip-amount-field',
          ),
        ),
        '#min' => 0,
        '#step' => .001,
        '#default_value' => 0,
      );
      $form['order_options']['tip_amount'] = array(
        '#type' => 'numberfield',
        '#min' => 0,
        '#step' => .001,
        '#attributes' => array(
          'disabled' => 'disabled',
        ),
        '#default_value' => 0,
      );
    }

    $form['order_options']['instructions'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Instructions'),
    );
    if (PhpHelper::getNestedValue($platform_settings,
        ['order_settings', 'disable_condiments'], FALSE) == FALSE) {
      $condiments = \Drupal\food\Core\RestaurantController::getRestaurantCondiments($cart->restaurant_id);
      $options = [];
      foreach ($condiments as $condiment) {
        $options[$condiment['text']] = $condiment['text'];
      }
      $form['order_options']['condiments'] = array(
        '#type' => 'checkboxes',
        '#title' => $this->t('Condiments'),
        '#options' => $options,
      );
      $form['order_options']['num_people'] = array(
        '#type' => 'number',
        '#title' => $this->t('Please specify the number of people you are ordering for'),
        '#min' => 1,
        '#default_value' => 1,
      );
    }

    if (PhpHelper::getNestedValue($platform_settings,
        ['order_settings', 'disable_order_scheduling'], FALSE) == FALSE) {
      $date = date('Y-m-d');
      $end_date = date('Y-m-d', strtotime("+7 days"));
      $order_time = time();
      if (\Drupal\food\Core\RestaurantController::isRestaurantOpen($restaurant,
        $order_time)) {
        $scheduleDayOptions = [0 => $this->t('As soon as possible')];
      }
      $i = 0;
      $temp_str = '';
      while (strtotime($date) <= strtotime($end_date)) {
        $i++;

        if ($i == 1) {
          $temp_str = 'Today';
        }
        else if ($i == 2) {
          $temp_str = 'Tomorrow';
        }
        else {
          $temp_str = date('l', strtotime($date));
        }
        $scheduleDayOptions[$date] = $temp_str;

        $date = date("Y-m-d", strtotime("+1 day", strtotime($date)));
      }
      $form['order_options']['schedule_date'] = array(
        '#type' => 'select',
        '#prefix' => '<h1 class="schedule-for-heading">Schedule For</h1>',
        '#title' => $this->t('Schedule Date'),
        '#options' => $scheduleDayOptions,
        '#default_value' => $cart->order_details->schedule_date,
        '#limit_validation_errors' => array(),
        '#ajax' => array(
          'callback' => '::changeSchedule_DateAjax',
          'wrapper' => 'schedule_date_field_wrapper',
          'method' => 'replace',
        ),
      );
      $rest_key = \Drupal\food\Core\RestaurantController::isRestaurantOpen($restaurant, $order_time);
      $form['order_options']['schedule_time'] = array(
        '#type' => 'select',
        '#title' => $this->t('Schedule Time'),
        '#options' => $this->_ajax_example_get_second_dropdown_options($rest_key, $restaurant, key($scheduleDayOptions)),
        '#default_value' => key($this->_ajax_example_get_second_dropdown_options($rest_key, $restaurant, key($scheduleDayOptions))),
        '#states' => array(
          'invisible' => array(
            'select[name="schedule_date"]' => array('value' => 0),
          ),
        ),
        '#limit_validation_errors' => array(),
        '#validated' => TRUE,
        '#prefix' => '<div id="schedule_date_field_wrapper">',
        '#suffix' => '</div>',
      );
    }

    $form['payment_details'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Payment Details'),
    );

    $payment_mode = json_decode($restaurant->payment_accept_mode);

    if (!empty($payment_mode->cash_on_delivery) && empty($payment_mode->credit)) {
      $form['payment_details']['payment_mode'] = array(
        '#type' => 'radios',
        '#title' => $this->t('Payment Type'),
        '#options' => [
          \Drupal\food\Core\Restaurant\PaymentMode::CashOnDelivery => $this->t('Cash'),
        ],
        '#default_value' => \Drupal\food\Core\Restaurant\PaymentMode::CashOnDelivery,
        '#attributes' => array(
          'class' => array(
            'order_payment_mode-cash',
          ),
        ),
      );
    }
    elseif (empty($payment_mode->cash_on_delivery) && !empty($payment_mode->credit)) {
      $form['payment_details']['payment_mode'] = array(
        '#type' => 'radios',
        '#title' => $this->t('Payment Type'),
        '#options' => [
          \Drupal\food\Core\Restaurant\PaymentMode::Card => $this->t('Credit Card'),
        ],
        '#default_value' => \Drupal\food\Core\Restaurant\PaymentMode::Card,
        '#attributes' => array(
          'class' => array(
            'order_payment_mode',
          ),
        ),
      );

      $form['fraud_instructions'] = array(
        '#type' => 'markup',
        '#markup' => $this->t('Please note: Your credit card will be processed by ' . $platform_settings->derived_settings->site_name . '.<br />We report and prosecute all Credit Card Fraud! Your IP Address: <b>@ip</b>',
          ['@ip' => $_SERVER['REMOTE_ADDR']]),
      );
    }

    else {
      $form['payment_details']['payment_mode'] = array(
        '#type' => 'radios',
        '#title' => $this->t('Payment Type'),
        '#options' => [
          \Drupal\food\Core\Restaurant\PaymentMode::Card => $this->t('Credit Card'),
          \Drupal\food\Core\Restaurant\PaymentMode::CashOnDelivery => $this->t('Cash'),
        ],
        '#default_value' => \Drupal\food\Core\Restaurant\PaymentMode::Card,
        '#attributes' => array(
          'class' => array(
            'order_payment_mode',
          ),
        ),
      );
      $form['payment_details']['card'] = array(
        '#type' => 'container',
        '#states' => array(
          'invisible' => array(
            'input[name="payment_mode"]' => array('value' => \Drupal\food\Core\Restaurant\PaymentMode::CashOnDelivery),
          ),
        ),
      );

    }
    $form['payment_details']['card']['card_auth_token'] = array(
      '#type' => 'hidden',
    );


    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Submit Order'),
      '#button_type' => 'primary',
    );

    $form['fraud_instructions'] = array(
      '#type' => 'markup',
      '#markup' => $this->t('Please note: Your credit card will be processed by ' . $platform_settings->derived_settings->site_name . '.<br />We report and prosecute all Credit Card Fraud! Your IP Address: <b>@ip</b>',
        ['@ip' => $_SERVER['REMOTE_ADDR']]),
    );

    $form['#food_form_validation_callback'] = [];
    return ($form);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $cart = $this->collectCart($form, $form_state);
    $restaurant = \Drupal\food\Core\RestaurantController::getRestaurantById($cart->restaurant_id);

    $order_time = time();
    if (isset($cart->order_details->schedule_date)) {
      $order_time = \DateTime::createFromFormat("Y-m-d H:i",
        $cart->order_details->schedule_date . ' ' . $cart->order_details->schedule_time)
        ->getTimestamp();
    }

    if (!\Drupal\food\Core\RestaurantController::isRestaurantOpen($restaurant,
      $order_time)) {
      $form_state->setErrorByName('schedule_date',
        $this->t('Restaurant is closed during the specified time.'));
    }
    if ($cart->order_details->delivery_mode == \Drupal\food\Core\Restaurant\DeliveryMode::Delivery) {
      if (!\Drupal\food\Core\RestaurantController::isRestaurantDeliveryOpen($restaurant,
        $order_time)) {
        $form_state->setErrorByName('schedule_date',
          $this->t('Restaurant does not deliver during the specified time.'));
      }
    }

    if (is_array($form['#food_form_validation_callback'])) {
      foreach ($form['#food_form_validation_callback'] as $callback) {
        call_user_func_array($callback, array(&$cart, &$form, $form_state));
      }
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cart = $this->collectCart($form, $form_state);

    \Drupal::moduleHandler()
      ->invokeAll('food_order_preplace', array(&$cart, &$form, $form_state));

    \Drupal\food\Core\CartController::updateCart($cart);
    $order = \Drupal\food\Core\CartController::createOrder($cart);
    \Drupal\food\Core\CartController::unlinkCurrentCart();

    $url = Url::fromRoute('food.cart.order.confirmation',
      array('order_id' => $order->order_id));
    $form_state->setRedirectUrl($url);
  }

  private function collectCart(array &$form, FormStateInterface $form_state) {
    $platform_settings = \Drupal\food\Core\PlatformController::getPlatformSettings();
    $cart = \Drupal\food\Core\CartController::getCurrentCart();
    $items_total_amount = PhpHelper::getNestedValue($cart,
      ['order_details', 'breakup', 'items_total_amount'], 0);

    $tip_manual_amount = $form_state->getValue('tip_manual_amount');
    if (!empty($tip_manual_amount) && $tip_manual_amount > 0) {
      $calculate_manual_tip = $tip_manual_amount / $items_total_amount * 100;
      $cart->order_details->breakup->tip_pct = (float) $calculate_manual_tip;
    }
    $cart->order_details->payment_mode = $form_state->getValue('payment_mode');

    if (PhpHelper::getNestedValue($platform_settings,
        ['order_settings', 'disable_condiments'], FALSE) == FALSE) {
      $condiments_submission = array_filter($form_state->getValue('condiments'));
      $condiments = [];
      foreach ($condiments_submission as $key => $condiment_submission) {
        //if($key == $condiment_submission) {
        $condiments[] = $condiment_submission;
        //}
      }
      $cart->order_details->condiments = $condiments;
      $cart->order_details->num_people = $form_state->getValue('num_people');
    }
    else {
      $cart->order_details->condiments = NULL;
      $cart->order_details->num_people = NULL;
    }

    $cart->order_details->instructions = $form_state->getValue('instructions');

    if (PhpHelper::getNestedValue($platform_settings,
        ['order_settings', 'disable_order_scheduling'],
        FALSE) == FALSE && $form_state->getValue('schedule_date') != 0) {

      $cart->order_details->schedule_date = $form_state->getValue('schedule_date');
      $cart->order_details->schedule_time = $form_state->getValue('schedule_time');
    }

    return ($cart);
  }

  private function validateCart($restaurant, $cart) {
    $delivery_mode = PhpHelper::getNestedValue($cart,
      ['order_details', 'delivery_mode']);
    if (empty($delivery_mode)) {
      drupal_set_message($this->t('Please select a delivery mode.'), 'warning');
      return (FALSE);
    }

    $minimum_order_amount = $delivery_mode == \Drupal\food\Core\Restaurant\DeliveryMode::Delivery ? PhpHelper::getNestedValue($restaurant,
      ['order_types', 'delivery_settings', 'minimum_order_amount'], 0) : 0;
    $items_total_amount = PhpHelper::getNestedValue($cart,
      ['order_details', 'breakup', 'items_total_amount'], 0);
    if ($items_total_amount < $minimum_order_amount) {
      drupal_set_message($this->t('Delivery Minimum: ' . \Drupal\food\Core\PlatformController::getPlatformSettings()->derived_settings->currency_symbol . $minimum_order_amount . ' (before tax). No minimum on Pickup orders.'),
        'warning');
      return (FALSE);
    }

    return (TRUE);
  }

  public function changeSchedule_DateAjax(array $form, FormStateInterface $form_state) {
    $cart = \Drupal\food\Core\CartController::getCurrentCart();
    $restaurant = \Drupal\food\Core\RestaurantController::getRestaurantById($cart->restaurant_id);
    $scheduleTimeOptions = [];
    if(!empty($restaurant)){
      $weekDay = date('l', strtotime($form_state->getValue('schedule_date')));
      $timings = $restaurant->timings->open_timings[strtolower($weekDay)];
      $timeStart = $timings->start_time;
      $timeStart = strtotime($timings->start_time, time());
      while ($timeStart < strtotime($timings->end_time, time())) {
        $timeStr = date("H:i", $timeStart);
        $scheduleTimeOptions[$timeStr] = $timeStr;
        $timeStart = strtotime('+15 minutes', $timeStart);
      }
      $form['order_options']['schedule_time']['#options'] = $scheduleTimeOptions;
    }
    return $form['order_options']['schedule_time'];
  }

  function _ajax_example_get_second_dropdown_options($key, $restaurant, $day) {
    $scheduleTimeOptions = [];
    if ($key) {
      $timeStart = strtotime('01:00', time());
      for ($i = 0; $i < 96; $i++) {
        $timeStr = date("H:i", $timeStart);
        $scheduleTimeOptions[$timeStr] = $timeStr;
        $timeStart = strtotime('+15 minutes', $timeStart);
      }
    }else {
      $date = ($day != 0) ? strtotime($day) : time();
      $weekDay = date('l', $date);
      $timings = $restaurant->timings->open_timings[strtolower($weekDay)];
      $timeStart = $timings->start_time;
      $timeStart = strtotime($timings->start_time, time());
      while ($timeStart < strtotime($timings->end_time, time())) {
        $timeStr = date("H:i", $timeStart);
        $scheduleTimeOptions[$timeStr] = $timeStr;
        $timeStart = strtotime('+15 minutes', $timeStart);
      }
    }
    return $scheduleTimeOptions;
  }

}
