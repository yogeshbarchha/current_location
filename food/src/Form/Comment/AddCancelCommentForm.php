<?php

namespace Drupal\food\Form\Comment;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Imbibe\Util\PhpHelper;
use Drupal\Core\Url;
use Drupal\Core\Ajax\AlertCommand;
use Drupal\Core\Ajax\RedirectCommand;

class AddCancelCommentForm extends FormBase {

  public function getFormId() {
    return 'partner_cancel_order_comment_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $restaurant_id = \Drupal::routeMatch()->getParameter('restaurant_id');
    $order_id = \Drupal::routeMatch()->getParameter('order_id');

    $form['reason'] = array(
      '#type' => 'select',
      '#title' => t('Reason'),
      '#required' => TRUE,
      '#options' => $this->cancelReasons(),
      '#empty_value' => 'none',
    );
    
    $form['comment'] = array('#title' => t('Comment'),
      '#type' => 'textarea',
      '#description' => t('Please add comment in order to cancel the order'),
      '#states' => array(
        'visible' => array(
         ':input[name="reason"]' => array('value' => 'custom'),
        ),
      ),
    );

    $form['restaurant_id'] = array('#type' => 'hidden', '#value' => $restaurant_id);
    $form['order_id'] = array('#type' => 'hidden', '#value' => $order_id);

    $form['submit'] = array('#type' => 'submit',
      '#attributes' => array(
        'class' => array(
          'add_cart_button',
          //'use-ajax-submit',
        ),
      ),
     '#value' => t('Add'));
    $form['cancel'] = array('#type' => 'submit',
     '#attributes' => array(
        'class' => array(
          'add_cart_button',
          //'use-ajax-submit',
        ),
      ),
     '#value' => t('Cancel'));

   // $form['#attached']['library'][] = 'food/form.partner.addcancelcommentform';

    return ($form);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
     $restaurant = \Drupal\food\Core\RestaurantController::getRestaurantById($form_state->getValue('restaurant_id'));
     $order = \Drupal\food\Core\OrderController::getOrderByOrderId($form_state->getValue('order_id'));
     $op = $form_state->getTriggeringElement()['#value']->__toString();      
    if($op == 'Add'){
       if($form_state->getValue('reason') == 'custom' && empty($form_state->getValue('comment'))){
        $form_state->setErrorByName('comment', $this->t('Please Enter comment'));
       }
       
       if(empty($restaurant)){
        $form_state->setErrorByName('restaurant_id', $this->t('Restaurant Not Found'));
       }

       if(empty($order)){
        $form_state->setErrorByName('order_id', $this->t('Order Not Found'));
       } 
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
       $form_state->getValue('restaurant_id');
       $form_state->getValue('order_id');
       $op = $form_state->getTriggeringElement()['#value']->__toString();
      
    if($op == 'Add'){
       $order = \Drupal\food\Core\OrderController::getOrderByOrderId($form_state->getValue('order_id'));
      
       if($order->status == \Drupal\food\Core\Order\OrderStatus::Submitted || $order->status == \Drupal\food\Core\Order\OrderStatus::Confirmed) {
          if($form_state->getValue('reason') == 'custom'){
            $order->order_details->cancel_comment = $form_state->getValue('comment');
          }else{
            $reasons = $this->cancelReasons();
            $order->order_details->cancel_comment = $reasons[$form_state->getValue('reason')];
          }
          \Drupal\food\Core\OrderController::updateOrder($order);      
          \Drupal\food\Core\OrderController::cancelOrder($order);
          drupal_set_message(t('Order Cancel Successfully'));
          //$url = Url::fromRoute('food.partner.order.cancel', ['order_id' => $form_state->getValue('order_id'), 'restaurant_id' => $form_state->getValue('restaurant_id')]);
          $url = Url::fromRoute('food.partner.report.dashboard');          
          $form_state->setRedirectUrl($url);
       }
    }
     
    if($op == 'Cancel'){
      $url = Url::fromRoute('food.partner.report.dashboard');
      $form_state->setRedirectUrl($url);
    }
  }

  public function cancelReasons(){
   
   $reason = array(1 => 'No Staff',2 => 'No Delivery Boy',3 => 'Out of Stock','custom' => 'Custom');
   
   return $reason;
  }

}
