<?php

namespace Drupal\food\Form\Partner;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing;
use Drupal\Core\Url;
use Drupal\food\Core\RestaurantController;
use Drupal\food\Core\RoleController;

class AccountDepositForm extends FormBase {

    public function getFormId() {
        return 'food_account_deposit_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
        $restaurant_id = \Drupal\food\Util::getRestaurantIdFromUrl();

        $form['transaction_id'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Transaction Id'),
            '#required' => TRUE,
        );

        $form['amount'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Amount'),
			'#required' => TRUE,
        );

        $form['deposit_date'] = array(
            '#type' => 'date',
            '#title' => $this->t('Date'),
            '#default_value' => date('Y-m-d',time()),
            '#date_format' => 'd/m/Y',
            '#required' => TRUE,
        );

        $form['comment'] = array(
            '#type' => 'textarea',
            '#title' => $this->t('Comment'),
            '#required' => TRUE,
        );

        $form['restaurant_id'] = array('#type' => 'hidden', '#value' => $restaurant_id);
        $form['depositor_uid'] = array('#type' => 'hidden', '#value' => \Drupal::currentUser()->id());

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Deposit'),
            '#button_type' => 'primary',
        );
        return ($form);
    }

    public function validateForm(array &$form, FormStateInterface $form_state) {
        $currentUser = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
        $isAdmin = $currentUser->hasRole(\Drupal\food\Core\RoleController::Administrator_Role_Name);
        $mulval = 1;
        if(!$isAdmin) {
            $mulval = -1 ;
        }

        if(!empty($form_state->getValue('amount')) && !preg_match("/^[0-9][0-9.]{0,15}$/", $form_state->getValue('amount'))){
               $form_state->setErrorByName('amount', t('Please Enter valid Amount.'));
        }
        
        if(!empty($form_state->getValue('restaurant_id'))){
            $restaurant_id = $form_state->getValue('restaurant_id');
            $depositor_total = 0;
            $count = 1;
            $total = 0;
            $order_rows = \Drupal\food\Core\OrderController::getCurrentPartnerOrders([
                'pageSize' => 0,
                'conditionCallback' => function($query) use (&$request, &$form_state) {
                    $restaurant_id = $form_state->getValue('restaurant_id');
                    
                    $query->distinct('fo.order_id');
                    $query->leftJoin('food_order_charge','foc','fo.order_id = foc.order_id');
                    $or = db_or();
                    $or->condition('foc.charge_type', \Drupal\food\Core\Order\OrderChargeType::Adjustment);
                    $or->condition('fo.status', \Drupal\food\Core\Order\OrderStatus::Confirmed);
                    $query = $query->condition($or);
                    $query = $query
                        ->condition('fo.status', array(\Drupal\food\Core\Order\OrderStatus::Confirmed, \Drupal\food\Core\Order\OrderStatus::Cancelled), 'IN');


                        if (!empty($restaurant_id)) {
                            $query = $query
                                ->condition('fo.restaurant_id', $restaurant_id);
                        }                    
                
                    return($query);
                }
            ]);

            \Drupal\food\Core\OrderController::assignEntityRestaurants($order_rows);
            $deposits = \Drupal\food\Core\OrderController::getCurrentPartnerDeposit($form_state->getValue('restaurant_id'));
            if(!empty($deposits)){
                foreach ($deposits as $key => $value) {
                    $depositor_total += $value->amount;
                }
            }

            foreach ($order_rows as $index => &$row) {
                $row->payment_mode = $row->payment_mode == \Drupal\food\Core\Restaurant\PaymentMode::CashOnDelivery ? 'Cash on Delivery' : 'Card';
                $adjustment_total = 0;

            $adjustment_rows = \Drupal\food\Core\OrderController::getOrderCharges(\Drupal\food\Core\Order\OrderChargeType::Adjustment, [
                'pageSize' => 0,
                'conditionCallback' => function($query) use (&$row) {
                                        
                    $query = $query
                            ->condition('order_id', $row->order_id);

                    return($query);
                }
            ]);
            \Drupal\food\Core\OrderController::assignEntityRestaurants($adjustment_rows);

            if(!empty($adjustment_rows)){
                foreach ($adjustment_rows as $key => $value) {
                   $adjustment_total += round($value->amount, 2);
                }
            }
                
            if($row->status == \Drupal\food\Core\Order\OrderStatus::Cancelled && $adjustment_total){   
                $deb_credit = $adjustment_total;
            }elseif($row->status == \Drupal\food\Core\Order\OrderStatus::Confirmed && $row->payment_mode == 'Card'){
                $deb_credit =   round(((($row->order_details->breakup->platform_commission_amount+$row->order_details->breakup->payment_mode_processing_fee_amount)-$row->order_details->breakup->platform_discount_amount)-$row->order_details->breakup->net_amount),2);
                $deb_credit = $adjustment_total + $deb_credit;
            }else{
                if($row->status == \Drupal\food\Core\Order\OrderStatus::Confirmed){
                    $deb_credit =   round(($row->order_details->breakup->platform_commission_amount-$row->order_details->breakup->platform_discount_amount),2);
                    $deb_credit = $adjustment_total + $deb_credit;
                }
            } 
                
            $total = round($total + ($deb_credit*$mulval), 2);
            }            
        
            if($total >= 0){
               $total = bcsub($total, $depositor_total,2);
            }elseif($total <= 0){
                $total = bcadd($total, $depositor_total,2);
            }

            if($form_state->getValue('amount') > abs($total)){
               $form_state->setErrorByName('amount', t('Amount should be less than deposit amount.'));  
            }
        }        
    }


    public function submitForm(array &$form, FormStateInterface $form_state) {
        $restaurant_id = \Drupal\food\Util::getRestaurantIdFromUrl();
        $currentUser = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
        $isAdmin = $currentUser->hasRole(RoleController::Administrator_Role_Name);

        $entity = array(
            'restaurant_id' => $form_state->getValue('restaurant_id'),
            'transaction_id' => $form_state->getValue('transaction_id'),
            'amount' => $form_state->getValue('amount'),
            'comment' => $form_state->getValue('comment'),
            'depositor_uid' => $form_state->getValue('depositor_uid'),
            'deposit_date' => strtotime($form_state->getValue('deposit_date')),
        );

        
        db_insert(' food_deposit_account_history')
            ->fields($entity)
            ->execute();

        drupal_set_message(t('Deposit added successfully.'));

        $url = Url::fromRoute('food.partner.report.currentbalance');
        $form_state->setRedirectUrl($url);
    }

}
