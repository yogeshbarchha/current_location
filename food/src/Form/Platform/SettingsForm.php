<?php

namespace Drupal\food\Form\Platform;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Imbibe\Util\PhpHelper;

class SettingsForm extends FormBase {

    public function getFormId() {
        return 'food_platform_settings_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state, $menu_id = NULL) {
        $entity = \Drupal\food\Core\PlatformController::getPlatformSettings();

        $supportedCurrencies = \Drupal\food\Core\PlatformController::getSupportedCurrencies();
        $options = [];
        foreach ($supportedCurrencies as $key => $supportedCurrency) {
            $options[$key] = $supportedCurrency->code;
        }
        $form['general_settings'] = array(
            '#type' => 'fieldset',
            '#title' => $this->t('General settings'),
        );
        $form['general_settings']['currency_code'] = array(
            '#type' => 'select',
            '#title' => $this->t('Currency Code'),
            '#required' => TRUE,
            '#options' => $options,
            '#default_value' => PhpHelper::getNestedValue($entity, ['currency_code']),
        );
        $form['general_settings']['use_miles'] = array(
            '#type' => 'checkbox',
            '#title' => $this->t('Use Miles'),
            '#default_value' => PhpHelper::getNestedValue($entity, ['use_miles']),
        );
        $form['general_settings']['country_calling_code'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Country Calling Code'),
            '#default_value' => PhpHelper::getNestedValue($entity, ['country_calling_code']),
        );
        $form['general_settings']['google_api_key'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Google Api Key'),
            '#required' => TRUE,
            '#default_value' => PhpHelper::getNestedValue($entity, ['google_api_key']),
        );
        $form['general_settings']['partner_support_phone_number'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Partner Support Phone#'),
            '#default_value' => PhpHelper::getNestedValue($entity, ['partner_support_phone_number']),
        );
        $form['general_settings']['user_support_phone_number'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('User Support Phone#'),
            '#default_value' => PhpHelper::getNestedValue($entity, ['user_support_phone_number']),
        );
        $form['general_settings']['email'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Email'),
            '#default_value' => PhpHelper::getNestedValue($entity, ['email']),
        );
        $form['general_settings']['address'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Address'),
            '#default_value' => PhpHelper::getNestedValue($entity, ['address']),
        );

        $form['deal'] = array(
            '#type' => 'fieldset',
            '#title' => $this->t('Deals'),
        );
        $form['deal']['disable_platform_deals'] = array(
			'#type' => 'checkbox',
			'#title' => $this->t('Disable Platform Deals'),
			'#default_value' => PhpHelper::getNestedValue($entity, ['order_settings', 'disable_platform_deals'], FALSE)
		);
        $form['deal']['deals_table'] = array(
            '#type' => 'table',
            '#header' => array(
                $this->t(''),
                $this->t('Discount %'),
            )
        );
        $deals = PhpHelper::getNestedValue($entity, ['order_settings', 'deals']);
        for ($i = 0; $i < 10; $i++) {
            $deal = isset($deals[$i]) ? $deals[$i] : NULL;
            $form['deal']['deals_table'][$i]['toggle'] = array(
                '#type' => 'checkbox',
                '#attributes' => array(
                    'class' => array(
                        'deal_row_toggle'
                    ),
                    'style' => 'margin-left: 0px;',
                ),
                '#default_value' => ($deal != NULL) ? 1 : ''
            );
            $form['deal']['deals_table'][$i]['deal_discount_pct'] = array(
                '#type' => 'number',
                '#step' => '.001',
                '#default_value' => ($deal != NULL) ? $deal->discount_pct : '',
                '#attributes' => ($deal != NULL) ?
                    array('class' => ['deal_row_pct']) :
                    array('class' => ['deal_row_pct'], 'disabled' => 'disabled'),
            );
        }

        $form['order_settings'] = array(
            '#type' => 'fieldset',
            '#title' => $this->t('Order Settings'),
        );
        $form['order_settings']['order_number_prefix'] = array(
			'#type' => 'textfield',
			'#title' => $this->t('Order# prefix'),
			'#default_value' => PhpHelper::getNestedValue($entity, ['order_settings', 'order_number_prefix'], FALSE)
		);
        $form['order_settings']['disable_condiments'] = array(
			'#type' => 'checkbox',
			'#title' => $this->t('Disable Condiments'),
			'#default_value' => PhpHelper::getNestedValue($entity, ['order_settings', 'disable_condiments'], FALSE)
		);
        $form['order_settings']['disable_tip'] = array(
			'#type' => 'checkbox',
			'#title' => $this->t('Disable Tip'),
			'#default_value' => PhpHelper::getNestedValue($entity, ['order_settings', 'disable_tip'], FALSE)
		);
        $form['order_settings']['disable_order_scheduling'] = array(
			'#type' => 'checkbox',
			'#title' => $this->t('Disable Order Scheduling'),
			'#default_value' => PhpHelper::getNestedValue($entity, ['order_settings', 'disable_order_scheduling'], FALSE)
		);

        $form['platform_google_settings'] = array(
            '#type' => 'fieldset',
            '#title' => $this->t('Platform Google Settings'),
        );
        $form['platform_google_settings']['delivery_lookup_settings_json'] = array(
			'#type' => 'textarea',
			'#title' => $this->t('Delivery Lookup Settings Json'),
			'#default_value' => PhpHelper::getNestedValue($entity, ['platform_google_settings', 'delivery_lookup_settings_json'], '')
		);

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Submit'),
            '#button_type' => 'primary',
        );

        $form['#platform_settings_callback'] = [];

        $form['#attached']['library'][] = 'food/form.platform.settingform';

        return ($form);
    }

    public function validateForm(array &$form, FormStateInterface $form_state) {
        
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        $entity = \Drupal\food\Core\PlatformController::getPlatformSettings();

        $entity = new \Drupal\food\Core\Platform\PlatformSettings();
        $entity->currency_code = $form_state->getValue('currency_code');
        $entity->use_miles = $form_state->getValue('use_miles');
        $entity->country_calling_code = $form_state->getValue('country_calling_code');
        $entity->google_api_key = $form_state->getValue('google_api_key');
        $entity->partner_support_phone_number = $form_state->getValue('partner_support_phone_number');
        $entity->user_support_phone_number = $form_state->getValue('user_support_phone_number');
        $entity->email = $form_state->getValue('email');
        $entity->address = $form_state->getValue('address');
		
		$entity->platform_google_settings = new \Drupal\food\Core\Platform\PlatformGoogleSettings();
		$entity->platform_google_settings->delivery_lookup_settings_json = $form_state->getValue('delivery_lookup_settings_json');
		
		$entity->order_settings = new \Drupal\food\Core\Platform\OrderSettings();
		$entity->order_settings->disable_platform_deals = $form_state->getValue('disable_platform_deals');

        $deals = [];
        $dealsValue = $form_state->getValue('deals_table');
        foreach ($dealsValue as $index => $dealValue) {
            if ($dealValue['toggle'] != 0) {
                $deal = new \Drupal\food\Core\Platform\PlatformDeal();
                $deal->discount_pct = $dealValue['deal_discount_pct'];
                $deals[] = $deal;
            }
        }
        $entity->order_settings->deals = $deals;
		$entity->order_settings->order_number_prefix = $form_state->getValue('order_number_prefix');
		$entity->order_settings->disable_condiments = $form_state->getValue('disable_condiments');
		$entity->order_settings->disable_tip = $form_state->getValue('disable_tip');
		$entity->order_settings->disable_order_scheduling = $form_state->getValue('disable_order_scheduling');

        if (is_array($form['#platform_settings_callback'])) {
            foreach ($form['#platform_settings_callback'] as $callback) {
                call_user_func_array($callback, array(&$entity, &$form, $form_state));
            }
        }

        \Drupal\food\Core\PlatformController::updatePlatformSettings($entity);
        drupal_set_message($this->t('Platform settings updated successfully.'));
    }

}
