<?php

namespace Drupal\food\Form\User;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Imbibe\Util\PhpHelper;
use Drupal\Core\Url;

class PreviousOrderForm extends FormBase {

  public function getFormId() {
    return 'food_cart_add_previous_order_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $order_id = \Drupal::routeMatch()->getParameter('order_id');
    $order = \Drupal\food\Core\OrderController::getOrderByOrderId($order_id);
    
    if(!empty($order) && !empty($order->order_details->items)){

      $header = array(t('Item'), t('quantity'), t('size'), t('options'), t('instructions'));
      $rows = array();

      foreach ($order->order_details->items as $item) {
        $restaurant_menu_item = \Drupal\food\Core\MenuController::getRestaurantMenuItem($item->restaurant_menu_item_id);
        $rows[] = array($item->item_name, $item->quantity, $item->size, $category->name, $item->instructions);
      }

      $form['detail'] = array(
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#sticky' => TRUE,
      );

      $form['add_cart'] = array(
        '#type' => 'submit',
        '#value' => t('Add to Cart'),
        '#attributes' => array(
          'class' => array(
            'add_cart_button',
            'use-ajax-submit',
          ),
        ),
      );

      $form['#attached']['library'][] = 'core/jquery.form';

    }else{
      $form['empty'] = array('#type' => 'item','#markup' => 'Order Not Found');
    }

    return ($form);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $order_id = \Drupal::routeMatch()->getParameter('order_id');
    $order = \Drupal\food\Core\OrderController::getOrderByOrderId($order_id);

    if($triggering_element['#value'] == 'Add to Cart' && !empty($order)){

      \Drupal\food\Core\CartController::unlinkCurrentCart();

      foreach ($order->order_details->items as $item) {
        $restaurant_menu_item_id = $item->restaurant_menu_item_id;
        $restaurant_menu_item = \Drupal\food\Core\MenuController::getRestaurantMenuItem($restaurant_menu_item_id);
        $cart = \Drupal\food\Core\CartController::getCurrentCart();
        
        if (empty($cart->order_details)) {
          $cart->order_details = new \Drupal\food\Core\Order\Order();
        }
        
        $cart->order_details->restaurant_id = $cart->restaurant_id;
        
        if (empty($cart->order_details->items)) {
          $cart->order_details->items = array();
        }
        
        $currentItem = new \Drupal\food\Core\Order\OrderItem();
        $currentItem->restaurant_menu_item_id = $restaurant_menu_item_id;
        $currentItem->quantity = intval($item->quantity);
        
        if(!empty($item->size)){
          foreach ($item->size as $size) {
            $sizeIndex = $size->id;            
          }
        }else{
          $sizeIndex = NULL;
        }
        
        if ($sizeIndex != NULL) {
          $currentItem->size = new \Drupal\food\Core\Order\OrderItemSize();
          $currentItem->size->id = $sizeIndex;
        }
        
        $currentItem->options = [];

        if(!empty($item->options)){
          foreach ($item->options as $options_key => $options) {
            if (strpos($options_key, 'category') === 0) {
              if ($options->id == \Drupal\food\Core\Order\OrderItem::NOTHANKSVALUE) {
                continue;
              }
              $option = new \Drupal\food\Core\Order\OrderItemOption();
              $option->category_id = $options->category_id;
              $option->id = $options->id;
              $currentItem->options[] = $option;
            }
          }
        }

        $currentItem->updateItemTotals();
        $currentItem->instructions = $item->instructions;
        
        $existingItem = NULL;
        
        foreach ($cart->order_details->items as $tempItem) {
          $propertiesToIgnore = [
            'Drupal\food\Core\Order\OrderItemOption::index',
            'Drupal\food\Core\Order\OrderItem::quantity',
            'Drupal\food\Core\Order\OrderItem::item_total_amount',
          ];
          if (PhpHelper::compareDeep($currentItem, $tempItem,
            ['propertiesToIgnore' => $propertiesToIgnore])) {
            $existingItem = $tempItem;
            break;
          }
        }
        
        if ($existingItem != NULL) {
          $existingItem->quantity += $currentItem->quantity;
          $existingItem->item_total_amount += $currentItem->item_total_amount;
        }else {
          $cart->order_details->items[] = $currentItem;
        }
        
        \Drupal\food\Core\CartController::updateCart($cart);
      }

      $response = new AjaxResponse();
      $response->addCommand(new CloseModalDialogCommand());
      $response->addCommand(new RedirectCommand(Url::fromRoute('entity.food_restaurant.canonical', ['food_restaurant' => $order->restaurant_id])->toString()));
      $form_state->setResponse($response);
    }

  }

}
