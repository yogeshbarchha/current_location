<?php

namespace Drupal\food\Form\Partner;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Imbibe\Util\PhpHelper;

class RestaurantForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $currentUser = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
    $isPlatform = $currentUser->hasRole(\Drupal\food\Core\RoleController::Platform_Role_Name);
    $isadmin = $currentUser->hasRole(\Drupal\food\Core\RoleController::Administrator_Role_Name);
    $ispartner = $currentUser->hasRole(\Drupal\food\Core\RoleController::Partner_Role_Name);
    $op = $form_state->getBuildInfo()['callback_object']->getOperation();
    $status_field_access = FALSE;
    $first_restaurant_status = FALSE;
    $current_restaurant_status = FALSE;


    $entity = $this->getEntity1(FALSE);
    if ($entity != NULL) {
      $entity = (object) $entity;
    }

    $owner = $entity->owner_user_id != NULL ? user_load($entity->owner_user_id) : '';

    if (!$ispartner) {

      $form['user_info'] = array(
        '#type' => 'fieldset',
        '#title' => $this->t('User Settings'),
      );

      $form['user_info']['user_reference'] = array(
        '#type' => 'entity_autocomplete',
        '#target_type' => 'user',
        '#default_value' => $owner,
        // The #default_value can be either an entity object or an array of entity objects.
      );

    }

    if($op == 'edit'){
      if($this->checkRestaurantStatus($entity->restaurant_id, $entity->owner_user_id)) {
        $status_field_access = TRUE;
      }         
    }

   if($status_field_access || $isadmin){

      $form['restaurant_switch_info'] = array(
        '#type' => 'fieldset',
        '#title' => $this->t('Primary Settings'),
      );
      $form['restaurant_switch_info']['restaurant_active'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Activate/Deactivate'),
        '#attributes' => array(
          'class' => array(
            'restaurant_active',
          ),
        ),
        '#default_value' => $entity != NULL ? $entity->status : \Drupal\food\Core\EntityStatus::Disabled,
      );
      $form['restaurant_switch_info']['featured_restaurant'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Featured'),
        '#attributes' => array(
          'class' => array(
            'restaurant_active',
          ),
        ),
        '#default_value' => $entity != NULL ? $entity->featured_restaurant : \Drupal\food\Core\EntityStatus::Enabled,
      );

    }


    $form['basic_info'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Basic Information'),
    );
    $form['basic_info']['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#required' => TRUE,
      '#default_value' => $entity != NULL ? $entity->name : '',
    );
    $form['basic_info']['tag_line'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Tagline'),
      '#default_value' => $entity != NULL ? $entity->tag_line : '',
    );
    $form['basic_info']['speciality'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Speciality'),
      '#default_value' => $entity != NULL ? $entity->speciality : '',
    );
    $form['basic_info']['tax_pct'] = array(
      '#type' => 'number',
      '#title' => $this->t('Tax Rate in %'),
      '#step' => '.001',
      '#required' => TRUE,
      '#default_value' => $entity != NULL ? $entity->tax_pct : '',
    );
    if ($entity != NULL && is_numeric($entity->image_fid)) {
      $picture = \Drupal\file\Entity\File::load($entity->image_fid);
      $form['basic_info']['current_picture'] = array(
        '#type' => 'html_tag',
        '#title' => t('Current Profile Picture'),
        '#tag' => 'img',
        '#attributes' => array(
          'src' => $picture->url(),
          'style' => 'max-height: 150px;',
        ),
      );
    }
    $form['basic_info']['picture'] = array(
      '#type' => 'managed_file',
      '#title' => $this->t('Picture'),
      '#upload_validators' => array(
        'file_validate_extensions' => array('png', 'jpg', 'jpeg', 'gif'),
        'file_validate_size' => 4194304, //4 MB
      ),
      '#theme' => 'image_widget',
      '#preview_image_style' => 'medium',
      '#upload_location' => $this->getUploadLocation(),
    );


    $order_types = $entity != NULL ? $entity->order_types : NULL;
    $selectedOrderTypes = array();
    if ($order_types != NULL && $order_types->delivery_settings != NULL && $order_types->delivery_settings->enabled) {
      $selectedOrderTypes[\Drupal\food\Core\Restaurant\DeliveryMode::Delivery] = \Drupal\food\Core\Restaurant\DeliveryMode::Delivery;
    }
    if ($order_types != NULL && $order_types->pickup_settings != NULL && $order_types->pickup_settings->enabled) {
      $selectedOrderTypes[\Drupal\food\Core\Restaurant\DeliveryMode::Pickup] = \Drupal\food\Core\Restaurant\DeliveryMode::Pickup;
    }
    $form['accepted_order_type'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Order Type'),
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

    $payment_types = $entity != NULL ? json_decode($entity->payment_accept_mode) : NULL;
    $selectedPaymentTypes = array(
      'cash_on_delivery' => 'Cash On Delivery',
      'credit' => 'Credit Card',
    );

    $form['payment_type_info'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Payment Option Method'),
    );

    $form['payment_type_info']['payment_accept_mode'] = array(
      '#type' => 'checkboxes',
      '#options' => $selectedPaymentTypes,
      '#attributes' => array(
        'class' => array(
          'order_types',
        ),
      ),
      '#default_value' => $payment_types,
    );

    $form['order_contact_info'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Order Contact Details'),
    );
    $form['order_contact_info']['order_contact_email'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Email'),
      '#required' => TRUE,
      '#default_value' => $entity != NULL && $entity->order_contact_details != NULL ? $entity->order_contact_details->email : '',
    );


    $settlement_payment_settings = $entity != NULL ? $entity->settlement_payment_settings : NULL;
    $beneficiary_name = '';
    if ($settlement_payment_settings != NULL && $settlement_payment_settings->payment_mode == \Drupal\food\Core\Restaurant\PaymentMode::WireTransfer) {
      $selectedPaymentSettings = \Drupal\food\Core\Restaurant\PaymentMode::WireTransfer;
      if ($settlement_payment_settings != NULL && $settlement_payment_settings->wire_transfer_payment_settings->beneficiary_name != NULL) {
        $beneficiary_name = $settlement_payment_settings->wire_transfer_payment_settings->beneficiary_name;
      }
    }
    else {
      $selectedPaymentSettings = \Drupal\food\Core\Restaurant\PaymentMode::Cheque;
      if ($settlement_payment_settings != NULL && $settlement_payment_settings->cheque_payment_settings->beneficiary_name != NULL) {
        $beneficiary_name = $settlement_payment_settings->cheque_payment_settings->beneficiary_name;
      }
    }
    $form['settlement_payment'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Payment Type'),
    );
    $form['settlement_payment']['settlement_payment_mode'] = array(
      '#type' => 'radios',
      '#options' => array(
        \Drupal\food\Core\Restaurant\PaymentMode::WireTransfer => $this->t('Wire Transfer'),
        \Drupal\food\Core\Restaurant\PaymentMode::Cheque => $this->t('Cheque'),
      ),
      '#attributes' => array(
        'class' => array(
          'settlement_payment_mode',
        ),
      ),
      '#default_value' => $selectedPaymentSettings,
    );
    $form['settlement_payment']['beneficiary_name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Beneficiary Name'),
      '#default_value' => $beneficiary_name,
    );
    $form['settlement_payment']['wire_transfer'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array(
          'settlement_payment_wire_transfer',
        ),
      ),
      '#states' => array(
        'invisible' => array(
          'input[name="settlement_payment_mode"]' => array('value' => \Drupal\food\Core\Restaurant\PaymentMode::Cheque),
        ),
      ),
    );
    $form['settlement_payment']['wire_transfer']['swift_code'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Swift Code'),
      '#default_value' => isset($settlement_payment_settings->wire_transfer_payment_settings->swift_code) ? $settlement_payment_settings->wire_transfer_payment_settings->swift_code : '',
    );
    $form['settlement_payment']['wire_transfer']['routing_code'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Routing Code'),
      '#default_value' => isset($settlement_payment_settings->wire_transfer_payment_settings->routing_code) ? $settlement_payment_settings->wire_transfer_payment_settings->routing_code : '',
    );
    $form['settlement_payment']['wire_transfer']['account_number'] = array(
      '#type' => 'number',
      '#title' => $this->t('Account Number'),
      '#default_value' => isset($settlement_payment_settings->wire_transfer_payment_settings->account_number) ? $settlement_payment_settings->wire_transfer_payment_settings->account_number : '',
    );
    $form['settlement_payment']['cheque'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array(
          'settlement_payment_cheque',
        ),
      ),
      '#states' => array(
        'invisible' => array(
          'input[name="settlement_payment_mode"]' => array('value' => \Drupal\food\Core\Restaurant\PaymentMode::WireTransfer),
        ),
      ),
    );
    $form['settlement_payment']['cheque']['settlement_payment_address_line1'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Address Line1'),
      '#default_value' => isset($settlement_payment_settings->cheque_payment_settings->address_line1) ? $settlement_payment_settings->cheque_payment_settings->address_line1 : '',
    );
    $form['settlement_payment']['cheque']['settlement_payment_address_line2'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Address Line2'),
      '#default_value' => isset($settlement_payment_settings->cheque_payment_settings->address_line2) ? $settlement_payment_settings->cheque_payment_settings->address_line2 : '',
    );
    $form['settlement_payment']['cheque']['settlement_payment_city'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('City'),
      '#default_value' => isset($settlement_payment_settings->cheque_payment_settings->city) ? $settlement_payment_settings->cheque_payment_settings->city : '',
    );
    $form['settlement_payment']['cheque']['settlement_payment_state'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('State'),
      '#default_value' => isset($settlement_payment_settings->cheque_payment_settings->state) ? $settlement_payment_settings->cheque_payment_settings->state : '',
    );
    $form['settlement_payment']['cheque']['settlement_payment_postal_code'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Postal Code'),
      '#default_value' => isset($settlement_payment_settings->cheque_payment_settings->postal_code) ? $settlement_payment_settings->cheque_payment_settings->postal_code : '',
    );
    $form['settlement_payment']['cheque']['settlement_payment_country'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Country'),
      '#default_value' => isset($settlement_payment_settings->cheque_payment_settings->country) ? $settlement_payment_settings->cheque_payment_settings->country : '',
    );

    if ($isPlatform) {
      $platform_settings = $entity != NULL ? $entity->platform_settings : NULL;
      $form['platform_setting'] = array(
        '#type' => 'fieldset',
        '#title' => $this->t('Platform Setting'),
      );
      $form['platform_setting']['platform_commission_pct'] = array(
        '#type' => 'number',
        '#title' => $this->t('Platform Commission Pct'),
        '#step' => '.001',
        '#max' => 15,
        '#required' => TRUE,
        '#default_value' => isset($platform_settings->platform_commission_pct) ? $platform_settings->platform_commission_pct : '',
      );
      $form['platform_setting']['card_fee_pct'] = array(
        '#type' => 'number',
        '#title' => $this->t('Card Fee Pct'),
        '#step' => '.001',
        '#max' => 15,
        '#required' => TRUE,
        '#default_value' => isset($platform_settings->card_fee_pct) ? $platform_settings->card_fee_pct : '',
      );

      $platform_deals = PhpHelper::getNestedValue($entity,
        ['platform_settings', 'deals']);
      $form['platform_setting']['platform_deals'] = array(
        '#type' => 'fieldset',
        '#title' => $this->t('Deals'),
      );
      $form['platform_setting']['platform_deals']['disable_platform_deals'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Disable Platform Deals'),
        '#default_value' => PhpHelper::getNestedValue($entity,
          ['platform_settings', 'disable_platform_deals'], FALSE),
      );
      $form['platform_setting']['platform_deals']['platform_deals_table'] = array(
        '#type' => 'table',
        '#header' => array(
          $this->t(''),
          $this->t('Discount %'),
        ),
      );
      for ($i = 0; $i < 10; $i++) {
        $platform_deal = isset($platform_deals[$i]) ? $platform_deals[$i] : NULL;
        $form['platform_setting']['platform_deals']['platform_deals_table'][$i]['toggle'] = array(
          '#type' => 'checkbox',
          '#attributes' => array(
            'class' => array(
              'platform_deal_row_toggle',
            ),
            'style' => 'margin-left: 0px;',
          ),
          '#default_value' => ($platform_deal != NULL) ? 1 : '',
        );
        $form['platform_setting']['platform_deals']['platform_deals_table'][$i]['platform_deal_discount_pct'] = array(
          '#type' => 'number',
          '#step' => '.001',
          '#default_value' => ($platform_deal != NULL) ? $platform_deal->discount_pct : '',
          '#attributes' => ($platform_deal != NULL) ? array('class' => ['platform_deal_row_pct']) : array(
            'class' => ['platform_deal_row_pct'],
            'disabled' => 'disabled',
          ),
        );
      }
    }

    $form['contact_info'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Contact Information'),
    );
    $form['contact_info']['email'] = array(
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#required' => TRUE,
      '#default_value' => $entity != NULL ? $entity->email : '',
    );
    $form['contact_info']['phone_number'] = array(
      '#type' => 'tel',
      '#title' => $this->t('Phone Number'),
      '#required' => TRUE,
      '#default_value' => $entity != NULL ? $entity->phone_number : '',
      '#attributes' => [
        'maxlength' => 10,
      ],
    );
    $form['contact_info']['fax_number'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Fax Number'),
      '#default_value' => $entity != NULL ? $entity->fax_number : '',
    );


    $form['about_us'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('About Us'),
    );
    $form['about_us']['about_summary'] = array(
      '#type' => 'text_format',
      '#default_value' => $entity != NULL ? $entity->about_summary : '',
    );

    if ($ispartner) {
      $form['about_us']['about_summary']['#allowed_formats'] = array('basic_html');
    }

    $form['address_info'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Address Information'),
    );
    $form['address_info']['address_line1'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Address Line1'),
      '#required' => TRUE,
      '#attributes' => array(
        'class' => array(
          'address_line1',
        ),
      ),
      '#default_value' => $entity != NULL ? $entity->address_line1 : '',
    );
    $form['address_info']['address_line2'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Address Line2'),
      '#default_value' => $entity != NULL ? $entity->address_line2 : '',
    );
    $form['address_info']['city'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('City'),
      '#required' => TRUE,
      '#attributes' => array(
        'class' => array(
          'city',
        ),
      ),
      '#default_value' => $entity != NULL ? $entity->city : '',
    );
    $form['address_info']['state'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('State'),
      '#required' => TRUE,
      '#attributes' => array(
        'class' => array(
          'state',
        ),
      ),
      '#default_value' => $entity != NULL ? $entity->state : '',
    );
    $form['address_info']['postal_code'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Postal Code'),
      '#required' => TRUE,
      '#attributes' => array(
        'class' => array(
          'postal_code',
        ),
      ),
      '#default_value' => $entity != NULL ? $entity->postal_code : '',
    );
    $form['address_info']['country'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Country'),
      '#required' => TRUE,
      '#attributes' => array(
        'class' => array(
          'country',
        ),
      ),
      '#default_value' => $entity != NULL ? $entity->country : '',
    );

    if (!$ispartner) {

      $form['address_info']['url_slug'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Url Slug'),
        '#default_value' => $entity != NULL ? $entity->url_slug : '',
      );

    }


    $weekDays = [
      'monday',
      'tuesday',
      'wednesday',
      'thursday',
      'friday',
      'saturday',
      'sunday',
    ];
    $form['timings_info'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Restaurant Timings'),
    );
    $form['timings_info']['timings_table'] = array(
      '#type' => 'table',
      '#header' => array(
        $this->t(''),
        $this->t('Open Time'),
        $this->t('Close Time'),
        $this->t(''),
        $this->t('Delivery Start Time'),
        $this->t('Delivery End Time'),
        $this->t(''),
      ),
    );

    $timings = $entity != NULL ? $entity->timings : NULL;
    $open_timings = $timings != NULL ? $timings->open_timings : NULL;
    $delivery_timings = $timings != NULL ? $timings->delivery_timings : NULL;

    foreach ($weekDays as $weekDayIndex => $weekDay) {
      $form['timings_info']['timings_table'][$weekDayIndex]['day'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t(ucwords($weekDay)),
        '#attributes' => array(
          'class' => array(
            'weekday_name',
          ),
        ),
        '#default_value' => ($open_timings != NULL && isset($open_timings[$weekDay])) ? 1 : '',
      );

      $form['timings_info']['timings_table'][$weekDayIndex]['open_start_time'] = array(
        '#type' => 'textfield',
        '#attributes' => ($open_timings != NULL && isset($open_timings[$weekDay])) ? array(
          'readonly' => 'readonly',
          'class' => array('open_start_time', 'timepicker'),
        ) : array(
          'readonly' => 'readonly',
          'disabled' => 'disabled',
          'class' => array('open_start_time', 'timepicker'),
        ),
        '#default_value' => ($open_timings != NULL && isset($open_timings[$weekDay])) ? $open_timings[$weekDay]->start_time : '',
      );
      $form['timings_info']['timings_table'][$weekDayIndex]['open_end_time'] = array(
        '#type' => 'textfield',
        '#attributes' => ($open_timings != NULL && isset($open_timings[$weekDay])) ? array(
          'readonly' => 'readonly',
          'class' => array('open_end_time', 'timepicker'),
        ) : array(
          'readonly' => 'readonly',
          'disabled' => 'disabled',
          'class' => array('open_end_time', 'timepicker'),
        ),
        '#default_value' => ($open_timings != NULL && isset($open_timings[$weekDay])) ? $open_timings[$weekDay]->end_time : '',
      );
      $form['timings_info']['timings_table'][$weekDayIndex]['open_time_apply_btn'] = array(
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => 'Apply to All',
        '#attributes' => ($open_timings != NULL && isset($open_timings[$weekDay])) ? array(
          'class' => array(
            'open_time_apply_btn',
            'btn btn-info',
          ),
        ) : array(
          'class' => array('open_time_apply_btn', 'btn btn-info'),
          'style' => array('display:none'),
        ),
      );

      $form['timings_info']['timings_table'][$weekDayIndex]['del_start_time'] = array(
        '#type' => 'textfield',
        '#attributes' => ($delivery_timings != NULL && isset($delivery_timings[$weekDay])) ? array(
          'readonly' => 'readonly',
          'class' => array('del_start_time', 'timepicker'),
        ) : array(
          'readonly' => 'readonly',
          'disabled' => 'disabled',
          'class' => array('del_start_time', 'timepicker'),
        ),
        '#default_value' => ($delivery_timings != NULL && isset($delivery_timings[$weekDay])) ? $delivery_timings[$weekDay]->start_time : '',
      );
      $form['timings_info']['timings_table'][$weekDayIndex]['del_end_time'] = array(
        '#type' => 'textfield',
        '#attributes' => ($delivery_timings != NULL && isset($delivery_timings[$weekDay])) ? array(
          'readonly' => 'readonly',
          'class' => array('del_end_time', 'timepicker'),
        ) : array(
          'readonly' => 'readonly',
          'disabled' => 'disabled',
          'class' => array('del_end_time', 'timepicker'),
        ),
        '#default_value' => ($delivery_timings != NULL && isset($delivery_timings[$weekDay])) ? $delivery_timings[$weekDay]->end_time : '',
      );
      $form['timings_info']['timings_table'][$weekDayIndex]['del_time_apply_btn'] = array(
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => 'Apply to All',
        '#attributes' => ($delivery_timings != NULL && isset($delivery_timings[$weekDay])) ? array(
          'class' => array(
            'del_time_apply_btn',
            'btn btn-info',
          ),
        ) : array(
          'class' => array('del_time_apply_btn', 'btn btn-info'),
          'style' => array('display:none'),
        ),
      );
    }


    $deals = PhpHelper::getNestedValue($entity, ['deals']);
    $form['deals'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Deals'),
    );
    $form['deals']['deals_table'] = array(
      '#type' => 'table',
      '#header' => array(
        $this->t(''),
        $this->t('Order Amount'),
        $this->t('Discount %'),
      ),
    );
    for ($i = 0; $i < 10; $i++) {
      $deal = isset($deals[$i]) ? $deals[$i] : NULL;
      $form['deals']['deals_table'][$i]['toggle'] = array(
        '#type' => 'checkbox',
        '#attributes' => array(
          'class' => array(
            'deal_row_toggle',
          ),
          'style' => 'margin-left: 0px;',
        ),
        '#default_value' => ($deal != NULL) ? 1 : '',
      );
      $form['deals']['deals_table'][$i]['min_order_amount'] = array(
        '#type' => 'number',
        '#step' => '.001',
        '#default_value' => ($deal != NULL) ? $deal->min_order_amount : '',
        '#attributes' => ($deal != NULL) ? array('class' => ['deal_row_amount']) : array(
          'class' => ['deal_row_amount'],
          'disabled' => 'disabled',
        ),
      );
      $form['deals']['deals_table'][$i]['discount_pct'] = array(
        '#type' => 'number',
        '#step' => '.001',
        '#default_value' => ($deal != NULL) ? $deal->discount_pct : '',
        '#attributes' => ($deal != NULL) ? array('class' => ['deal_row_pct']) : array(
          'class' => ['deal_row_pct'],
          'disabled' => 'disabled',
        ),
      );
    }


    $form['restaurant_map'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Map'),
      '#attributes' => array(
        'class' => array(
          'scrollMap',
        ),
      ),
    );
    $form['restaurant_map']['latitude_val'] = array(
      '#type' => 'hidden',
      '#attributes' => array(
        'class' => array(
          'latitude_val',
        ),
      ),
      '#default_value' => $entity != NULL ? $entity->latitude : 40.67660124,
    );
    $form['restaurant_map']['longitude_val'] = array(
      '#type' => 'hidden',
      '#attributes' => array(
        'class' => array(
          'longitude_val',
        ),
      ),
      '#default_value' => $entity != NULL ? $entity->longitude : -73.86895552,
    );
    $form['restaurant_map']['delivery_radius_val'] = array(
      '#type' => 'hidden',
      '#default_value' => $entity != NULL ? $entity->delivery_radius : '',
    );
    $form['restaurant_map']['delivery_polygon_val'] = array(
      '#type' => 'hidden',
      '#default_value' => $entity != NULL && $entity->delivery_polygon != NULL ? json_encode($entity->delivery_polygon) : '',
    );
    $form['restaurant_map']['delivery_area_type'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Draw Delivery Area - Click on the map to define your delivery area.'),
      '#default_value' => $entity != NULL ? $entity->delivery_area_type : \Drupal\food\Core\Location\DeliveryAreaType::Circle,
      '#options' => array(
        \Drupal\food\Core\Location\DeliveryAreaType::Circle => $this->t('Circle'),
        \Drupal\food\Core\Location\DeliveryAreaType::Polygon => $this->t('Polygon'),
      ),
    );
    $form['restaurant_map']['delivery_radius'] = array(
      '#type' => 'number',
      '#title' => $this->t('Radius (In Kilometres)'),
      '#step' => '.001',
      '#default_value' => $entity != NULL ? $entity->delivery_radius : 1,
    );
    $form['restaurant_map']['delivery_map'] = array(
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => '',
      '#attributes' => array(
        'id' => 'google_map',
        'class' => array(
          'google_map',
        ),
      ),
    );


    $form['#attached']['library'][] = 'food/form.partner.restaurantform';
    $form['#attached']['drupalSettings']['food']['restaurant'] = array();

    return ($form);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $op = $form_state->getBuildInfo()['callback_object']->getOperation();

    $currentUser = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
    $isPlatform = $currentUser->hasRole(\Drupal\food\Core\RoleController::Platform_Role_Name);
    $isadmin = $currentUser->hasRole(\Drupal\food\Core\RoleController::Administrator_Role_Name);
    $ispartner = $currentUser->hasRole(\Drupal\food\Core\RoleController::Partner_Role_Name);
    $op = $form_state->getBuildInfo()['callback_object']->getOperation();

    $entity = $this->getEntity1();
    $entity = \Drupal\food\Core\RestaurantController::prepareForUpdation('food_restaurant',
      $entity);

    $order_types = new \Drupal\food\Core\Restaurant\OrderTypeSettings();
    $order_types->delivery_settings = new \Drupal\food\Core\Restaurant\DeliverySettings();
    $order_types->delivery_settings->enabled = $form_state->getValue('order_types')[\Drupal\food\Core\Restaurant\DeliveryMode::Delivery] == \Drupal\food\Core\Restaurant\DeliveryMode::Delivery;
    $order_types->delivery_settings->estimated_delivery_time_minutes = $form_state->getValue('delivery_time');
    $order_types->delivery_settings->minimum_order_amount = $form_state->getValue('minimum_order');
    $order_types->delivery_settings->delivery_charges_amount = $form_state->getValue('delivery_charge');
    $order_types->pickup_settings = new \Drupal\food\Core\Restaurant\PickupSettings();
    $order_types->pickup_settings->enabled = $form_state->getValue('order_types')[\Drupal\food\Core\Restaurant\DeliveryMode::Pickup] == \Drupal\food\Core\Restaurant\DeliveryMode::Pickup;
    $order_types->pickup_settings->estimated_pickup_time_minutes = $form_state->getValue('pickup_time');

    $settlement_payment_settings = new \Drupal\food\Core\Restaurant\PaymentSettings();
    $settlement_payment_settings->payment_mode = $form_state->getValue('settlement_payment_mode');
    $settlement_payment_settings->wire_transfer_payment_settings = new \Drupal\food\Core\Restaurant\WireTransferPaymentSettings();
    $settlement_payment_settings->wire_transfer_payment_settings->beneficiary_name = $form_state->getValue('beneficiary_name');
    $settlement_payment_settings->wire_transfer_payment_settings->swift_code = $form_state->getValue('swift_code');
    $settlement_payment_settings->wire_transfer_payment_settings->routing_code = $form_state->getValue('routing_code');
    $settlement_payment_settings->wire_transfer_payment_settings->account_number = $form_state->getValue('account_number');
    $settlement_payment_settings->cheque_payment_settings = new \Drupal\food\Core\Restaurant\ChequePaymentSettings();
    $settlement_payment_settings->cheque_payment_settings->beneficiary_name = $form_state->getValue('beneficiary_name');
    $settlement_payment_settings->cheque_payment_settings->address_line1 = $form_state->getValue('settlement_payment_address_line1');
    $settlement_payment_settings->cheque_payment_settings->address_line2 = $form_state->getValue('settlement_payment_address_line2');
    $settlement_payment_settings->cheque_payment_settings->city = $form_state->getValue('settlement_payment_city');
    $settlement_payment_settings->cheque_payment_settings->state = $form_state->getValue('settlement_payment_state');
    $settlement_payment_settings->cheque_payment_settings->postal_code = $form_state->getValue('settlement_payment_postal_code');
    $settlement_payment_settings->cheque_payment_settings->country = $form_state->getValue('settlement_payment_country');

    $orderContactDetail = new \Drupal\food\Core\Restaurant\OrderContactDetails();
    $orderContactDetail->email = ($form_state->getValue('order_contact_email'));

    $deals = [];
    $dealsValue = $form_state->getValue('deals_table');
    foreach ($dealsValue as $index => $dealValue) {
      if ($dealValue['toggle'] != 0) {
        $deal = new \Drupal\food\Core\Restaurant\RestaurantDeal();
        $deal->min_order_amount = $dealValue['min_order_amount'];
        $deal->discount_pct = $dealValue['discount_pct'];
        $deals[] = $deal;
      }
    }

    $timings = new \Drupal\food\Core\Restaurant\RestaurantTimings();
    $timings->open_timings = new \Imbibe\Collections\Dictionary();
    $timings->delivery_timings = new \Imbibe\Collections\Dictionary();
    $weekDays = [
      'monday',
      'tuesday',
      'wednesday',
      'thursday',
      'friday',
      'saturday',
      'sunday',
    ];
    $timingsValue = $form_state->getValue('timings_table');


    foreach ($timingsValue as $index => $timingValue) {
      if ($timingValue['day'] != 0) {
        $timeRange = new \Drupal\food\Core\DateTime\TimeRange();
        $timeRange->start_time = $timingValue['open_start_time'];
        $timeRange->end_time = $timingValue['open_end_time'];
        $timings->open_timings[$weekDays[$index]] = $timeRange;

        $timeRange = new \Drupal\food\Core\DateTime\TimeRange();
        $timeRange->start_time = $timingValue['del_start_time'];
        $timeRange->end_time = $timingValue['del_end_time'];
        $timings->delivery_timings[$weekDays[$index]] = $timeRange;
      }
    }

    $roles = \Drupal::currentUser()->getRoles();
    if (in_array(\Drupal\food\Core\RoleController::Administrator_Role_Name,
        $roles) && !empty($form_state->getValue('user_reference'))) {
      $entity = array_merge($entity,
        array('owner_user_id' => $form_state->getValue('user_reference')));
    }

    $entity = array_merge($entity, array(
      'name' => $form_state->getValue('name'),

      'status' => isset($values['restaurant_active']) ? $values['restaurant_active'] : 0,

      'phone_number' => $form_state->getValue('phone_number'),
      'fax_number' => $form_state->getValue('fax_number'),
      'email' => $form_state->getValue('email'),

      'address_line1' => $form_state->getValue('address_line1'),
      'address_line2' => $form_state->getValue('address_line2'),
      'city' => $form_state->getValue('city'),
      'state' => $form_state->getValue('state'),
      'postal_code' => $form_state->getValue('postal_code'),
      'country' => $form_state->getValue('country'),

      'latitude' => $form_state->getValue('latitude_val'),
      'longitude' => $form_state->getValue('longitude_val'),

      'about_summary' => $form_state->getValue('about_summary')['value'],

      'tag_line' => $form_state->getValue('tag_line'),
      'speciality' => $form_state->getValue('speciality'),

      'order_contact_details' => json_encode($orderContactDetail),
      'order_types' => json_encode($order_types),

      'timings' => json_encode($timings),

      'delivery_area_type' => $form_state->getValue('delivery_area_type'),
      'delivery_radius' => round($form_state->getValue('delivery_radius_val'),
        3, PHP_ROUND_HALF_UP),
      'delivery_polygon' => $form_state->getValue('delivery_polygon_val'),

      'settlement_payment_settings' => json_encode($settlement_payment_settings),

      'deals' => json_encode($deals),

      'tax_pct' => $form_state->getValue('tax_pct'),

      'url_slug' => $form_state->getValue('url_slug'),

      'featured_restaurant' => $form_state->getValue('featured_restaurant'),

      'payment_accept_mode' => json_encode($form_state->getValue('payment_accept_mode')),
    ));

    $picture = $form_state->getValue('picture');
    if (!empty($picture)) {
      $file = \Drupal\file\Entity\File::load($picture[0]);
      $file->setPermanent();
      $file->save();

      $file_usage = \Drupal::service('file.usage');
      $file_usage->add($file, 'food', 'food_restaurant', $file->id());

      $entity['image_fid'] = $file->id();
    }

    if ($isPlatform) {
      $platform_settings = new \Drupal\food\Core\Restaurant\PlatformSettings();
      $platform_settings->platform_commission_pct = $form_state->getValue('platform_commission_pct');
      $platform_settings->card_fee_pct = $form_state->getValue('card_fee_pct');

      $deals = [];
      $dealsValue = $form_state->getValue('platform_deals_table');
      foreach ($dealsValue as $index => $dealValue) {
        if ($dealValue['toggle'] != 0) {
          $deal = new \Drupal\food\Core\Platform\PlatformDeal();
          $deal->discount_pct = $dealValue['platform_deal_discount_pct'];
          $deals[] = $deal;
        }
      }
      $platform_settings->disable_platform_deals = $form_state->getValue('disable_platform_deals');
      $platform_settings->deals = $deals;

      $entity['platform_settings'] = json_encode($platform_settings);
    }

    $baseEntity = $this->getEntity();
    foreach ($entity as $key => $value) {
      $baseEntity->$key = $value;
    }
    $this->updateChangedTime($baseEntity);
    $baseEntity->save();

    if (isset($entity['restaurant_id'])) {
      
      if($this->checkIfRestaurantExists($entity['restaurant_id'])){
        if($isadmin){
          if(isset($values['restaurant_active'])){
            $this->updateRestaurantStatus($entity['restaurant_id'], $values['restaurant_active']);
          }
        }
      }else{
        $this->addNewRestaurantStatus($entity['restaurant_id'], $entity['owner_user_id'], $values['restaurant_active']);          
      }

      drupal_set_message(t('Restaurant updated successfully...'));
    }else {
      
      $new_restaurant_id = db_query('SELECT MAX(restaurant_id) FROM {food_restaurant}')->fetchField();
        $this->addNewRestaurantStatus($new_restaurant_id, $entity['owner_user_id'], $values['restaurant_active']);

      drupal_set_message(t('Restaurant added successfully.'));
    }

    
    $url = Url::fromRoute('entity.food_restaurant.collection');
    $form_state->setRedirectUrl($url);
  }

  private function getEntity1($createDefault = TRUE) {
    $restaurant_id = \Drupal\food\Util::getRestaurantIdFromUrl();
    $entity = NULL;

    if ($restaurant_id != NULL) {
      $entity = \Drupal\food\Core\RestaurantController::getRestaurantById($restaurant_id);
      $entity = (array) $entity;
    }
    else {
      if ($createDefault) {
        $entity = array(
          'owner_user_id' => \Drupal::currentUser()->id(),
          'created_time' => \Imbibe\Util\TimeUtil::now(),
        );
      }
    }

    return ($entity);
  }

  private function getUploadLocation() {
    $dir = 'public://images/partner/restaurant';

    if (!file_prepare_directory($dir, FILE_MODIFY_PERMISSIONS)) {
      $service = \Drupal::service('file_system');
      $service->mkdir($dir, NULL, TRUE);
    }

    return ($dir);
  }

  public static function addNewRestaurantStatus($restaurant_id, $owner_id, $status){

    $query = \Drupal::database()->insert('restaurant_activation_status');
      $query->fields(
        array(
          'restaurant_id' => $restaurant_id,
          'owner_id' => $owner_id,
          'status' => isset($status) ? $status : 0,
          'processed_by' => isset($status) ? $status : 0,
        )
      )->execute();
  }

  public static function updateRestaurantStatus($restaurant_id, $status){

    $query = \Drupal::database()->update('restaurant_activation_status');
      $query->fields([
      'status' => $status,
      'processed_by' => $status ? 1 : 0,
      ]);
      $query->condition('restaurant_id',$restaurant_id);
      $query->execute();
  }

  public static function checkRestaurantStatus($restaurant_id, $owner_id){

    $query = \Drupal::database()->select('restaurant_activation_status', 'ras');
    $query->fields('ras',array('processed_by'));
    $query->condition('restaurant_id', $restaurant_id);
    $query->condition('owner_id', $owner_id);
    $restaurant = $query->execute()->fetchAssoc();

    if(isset($restaurant['processed_by']) && $restaurant['processed_by']) {
      return TRUE;
    }

    return FALSE;
  }


  public static function checkIfRestaurantExists($restaurant_id){

    $query = \Drupal::database()->select('restaurant_activation_status', 'ras');
    $query->fields('ras',array());
    $query->condition('restaurant_id', $restaurant_id);
    $restaurant = $query->execute()->fetchAssoc();

    if(!empty($restaurant) && $restaurant['restaurant_id']){
      return TRUE;
    }

    return FALSE;
  }

}
