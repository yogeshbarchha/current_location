<?php

namespace Drupal\food\Form\User;

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

class UserOrderCancelCommentForm extends FormBase {

  /**
   * Returns a page title.
   */
  public function getTitle() {
    $order_id = \Drupal::routeMatch()->getParameter('order_id');
    $order = \Drupal\food\Core\OrderController::getOrderByOrderId($order_id);
  	$title = 'Cancel Order';
    if($order->status != \Drupal\food\Core\Order\OrderStatus::Submitted) {
    	$title = 'Opps!! your order is already in process';
    }else{
    	$title = 'Cancel Order';
    }
    return $title;
  }

  public function getFormId() {
    return 'user_order_cancel_comment_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $restaurant_id = \Drupal::routeMatch()->getParameter('restaurant_id');
    $order_id = \Drupal::routeMatch()->getParameter('order_id');
    $order = \Drupal\food\Core\OrderController::getOrderByOrderId($order_id);
    
    if($order->status != \Drupal\food\Core\Order\OrderStatus::Submitted) {
        $form['message'] = array(
            '#type' => 'item',
            '#markup' => '<p>Opps!! your order is already in process and can not be cancel from here if you still want to make it cancel pls call 8885181475.</p>'
            );
    }else{

        $form['wrapper-messages'] = [
          '#type' => 'container',
          '#attributes' => [
            'id' => 'messages-wrapper',
          ],
        ];
    
        $form['wrapper'] = [
          '#type' => 'container',
          '#attributes' => [
            'id' => 'form-wrapper',
          ],
        ];
    
        $form['wrapper']['reason'] = array(
          '#type' => 'select',
          '#title' => t('Reason'),
          '#required' => TRUE,
          '#options' => $this->cancelReasons(),
          '#empty_value' => 'none',
        );
        
        $form['wrapper']['comment'] = array('#title' => t('Comment'),
          '#type' => 'textarea',
          '#description' => t('Please add comment in order to cancel the order'),
          '#states' => array(
            'visible' => array(
             ':input[name="reason"]' => array('value' => 'custom'),
            ),
          ),
        );
    
        $form['wrapper']['submit'] = array(
          '#type' => 'submit',
          '#value' => 'Add',
          '#ajax' => array(
            'callback' => [$this, 'submitCallback'],
            'wrapper' => 'form-wrapper',
            'effect' => 'fade',
          ),
        );
    }

    return ($form);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $restaurant_id = \Drupal::routeMatch()->getParameter('restaurant_id');
    $order_id = \Drupal::routeMatch()->getParameter('order_id');
    $triggering_element = $form_state->getTriggeringElement();      
      
    if($triggering_element['#value'] == 'Add'){
       if($form_state->getValue('reason') == 'custom' && empty($form_state->getValue('comment'))){
        $form_state->setErrorByName('comment', $this->t('Please Enter comment'));
       }
       
       if(empty($restaurant_id)){
        $form_state->setErrorByName('restaurant_id', $this->t('Restaurant Not Found'));
       }

       if(empty($order_id)){
        $form_state->setErrorByName('order_id', $this->t('Order Not Found'));
       } 
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
      $restaurant_id = \Drupal::routeMatch()->getParameter('restaurant_id');
      $order_id = \Drupal::routeMatch()->getParameter('order_id');
      $triggering_element = $form_state->getTriggeringElement();
      $response = new AjaxResponse();
      
      if($triggering_element['#value'] == 'Add'){
         $order = \Drupal\food\Core\OrderController::getOrderByOrderId($order_id);
        
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
         }
      }

    $form_state->setRebuild(TRUE);
  }

  public function cancelReasons(){
   
   $reason = array(1 => 'Ordered Wrong Item',2 => 'Inaccurate Product Description',3 => 'Bought By Mistake','custom' => 'Custom');
   
   return $reason;
  }


  public static function submitCallback(array &$form, FormStateInterface $form_state) {
    $restaurant_id = \Drupal::routeMatch()->getParameter('restaurant_id');
    $order_id = \Drupal::routeMatch()->getParameter('order_id');
    $response = new AjaxResponse();
    $messages = drupal_get_messages();

    if (!empty($messages)) {
      // Form did not validate, get messages and render them.
      $messages = [
        '#theme' => 'status_messages',
        '#message_list' => $messages,
        '#status_headings' => [
          'status' => t('Status message'),
          'error' => t('Error message'),
          'warning' => t('Warning message'),
        ],
      ];
      $response->addCommand(new HtmlCommand('#messages-wrapper', $messages));
      $messages = array();
    }else {
      // Remove messages.
      $response->addCommand(new HtmlCommand('#messages-wrapper', ''));
    }
    
    if(empty($form_state->getErrors())){
      $response->addCommand(new CloseModalDialogCommand());
      $response->addCommand(new RedirectCommand(Url::fromRoute('food.cart.order.cancel', ['restaurant_id' => $restaurant_id, 'order_id' => $order_id])->toString()));
    }

    // Update Form.
    $response->addCommand(new HtmlCommand('#form-wrapper',
      $form['wrapper']));


    return $response;
  }

}
