<?php

namespace Drupal\food\Form\Partner;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing;
use Drupal\Core\Url;
use Drupal\food\Core\RestaurantController;
use Drupal\food\Core\RoleController;

class AdjustmentForm extends FormBase {

    public function getFormId() {
        return 'food_partner_adjustment_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {

        $form['transaction_date'] = array(
            '#type' => 'date',
            '#title' => $this->t('Date'),
            '#attributes' => array(
                'class' => array(
                    'transaction_date'
                )
            ),
            '#required' => TRUE,
        );

        $form['amount'] = array(
            '#type' => 'textfield',
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
            'charge_type' => \Drupal\food\Core\Order\OrderChargeType::Adjustment,
            'transaction_date' => $form_state->getValue('transaction_date'),
            'amount' => $form_state->getValue('amount'),
            'description' => $form_state->getValue('description'),
            'user_id' => $user_id,
            'created_time' => \Imbibe\Util\TimeUtil::now(),
        );
        
         if(db_field_exists('food_order_charge','notification')){
            $entity['notification'] = 1;
        }
        
        db_insert('food_order_charge')
            ->fields($entity)
            ->execute();

        // Send mail to restaurant owner, if adjustment is added by Admin user.
        $currentUser = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
        $isAdmin = $currentUser->hasRole(RoleController::Administrator_Role_Name);
        if ($isAdmin) {
            $restaurant = RestaurantController::getRestaurantById($restaurant_id);
            $restaurant_owner = \Drupal\user\Entity\User::load($restaurant->owner_user_id);
            $adjustment = [
                'transaction' => $form_state->getValue('transaction_date'),
                'amount' => $form_state->getValue('amount'),
                'description' => $form_state->getValue('description'),
                'order_id' => $order_id,
                'restaurant_name' => $restaurant->name,
            ];
            // Send Adjustment notification email.
            \Drupal::service('plugin.manager.mail')
            ->mail('food', 'adjustment_notification_email', $restaurant_owner->getEmail(),
            $currentUser->getPreferredLangcode(), ['adjustment' => $adjustment]);
            drupal_set_message(t('Adjustment notification mail is sent to Restaurant owner.'));
        }

        drupal_set_message(t('Adjustment added successfully.'));

        $url = Url::fromRoute('food.partner.report.liveorders');
        $form_state->setRedirectUrl($url);
    }

}
