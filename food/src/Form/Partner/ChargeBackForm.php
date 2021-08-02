<?php

namespace Drupal\food\Form\Partner;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing;
use Drupal\Core\Url;

class ChargeBackForm extends FormBase {

    public function getFormId() {
        return 'food_partner_chargeback_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {

        $form['transaction_date'] = array(
            '#type' => 'date',
            '#title' => $this->t('Date'),
            '#attributes' => array(
                'class' => array(
                    'transaction_date'
                ),
            ),
            '#required' => TRUE,
        );

        $form['amount'] = array(
            '#type' => 'number',
            '#title' => $this->t('Amount'),
            '#step' => '.01',
			'#required' => TRUE,
			'#attributes' => array(
				'min' => '0'
			),
        );

        $form['description'] = array(
            '#type' => 'textarea',
            '#title' => $this->t('Description'),
            '#required' => TRUE,
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
        
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        $restaurant_id = \Drupal\food\Util::getRestaurantIdFromUrl();
        $order_id = \Drupal::routeMatch()->getParameter('order_id');
        $user_id = \Drupal::routeMatch()->getParameter('user_id');

        $entity = array(
            'restaurant_id' => $restaurant_id,
            'order_id' => $order_id,
            'charge_type' => \Drupal\food\Core\Order\OrderChargeType::Chargeback,
            'transaction_date' => $form_state->getValue('transaction_date'),
            'amount' => $form_state->getValue('amount'),
            'description' => $form_state->getValue('description'),
            'user_id' => $user_id,
            'created_time' => \Imbibe\Util\TimeUtil::now(),
        );

        db_insert('food_order_charge')
            ->fields($entity)
            ->execute();

        drupal_set_message(t('Charge back added successfully.'));

        $url = Url::fromRoute('food.partner.report.liveorders');
        $form_state->setRedirectUrl($url);
    }

}
