<?php

namespace Drupal\food\Form\Shortcut;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\food\Core\RestaurantController;
use Drupal\food\Form\Partner\RestaurantList;
use Drupal\food\Core\RoleController;
use Drupal\user\Entity\User;
use Drupal\Core\Url;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Link;

class RestaurantNotification {
    
    /**
   * {@inheritdoc}
   */
  public function refreshNewNotification() {
    $html = $this->_get_new_restaurants()['html'];
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('.new-restaurant-list', render($html)));
    return ($response);
  }

  /**
   * {@inheritdoc}
   */
  public function refreshUpdatedNotification1() {
    $html = $this->_get_updated_restaurants()['html'];
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('.updated-restaurant-list', render($html)));
    return ($response);
  }
  
  /**
   * {@inheritdoc}
   */
  public function adjustmentUpdatedNotification() {
    $html = $this->_get_adjustment()['html'];
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('.adjustment-list', render($html)));
    return ($response);
  }

  /**
   * {@inheritdoc}
   */
  public function customerCancelOrderNotification() {
    $html = $this->_get_customer_cancel_orders()['html'];
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('.order-cancel-list', render($html)));
    return ($response);
  }

  /**
   * {@inheritdoc}
   */
  public function vendorSubuserCancelOrderNotification() {
    $html = $this->_get_vendor_subuser_cancel_orders()['html'];
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('.vendor-subuser-order-cancel-list', render($html)));
    return ($response);
  }

  /**
   * {@inheritdoc}
   */
  public static function _get_new_restaurants() {
    $currentUser = User::load(\Drupal::currentUser()->id());
    $restaurants = RestaurantController::getCurrentUserRestaurants([
            'pageSize' => 0,
            'conditionCallback' => function($query) {
                return($query);
            }
            ]);
    $isAdministrator = $currentUser->hasRole(RoleController::Administrator_Role_Name);
    $output = [];
    $output['html'] = '';
    $output['count'] = 0;
    if ($isAdministrator && !empty($restaurants)) {
      $output['html'] .= "<div class='restaurant-notifications new-restaurant-list'><ul>";
      foreach ($restaurants as $key => $row) {
        if (RestaurantList::restaurant_mark($row->restaurant_id, $row->changed) == 1) {
          $RestaurantUrl = Url::fromRoute('entity.food_restaurant.canonical',
            array('food_restaurant' => $row->restaurant_id),
            array('attributes' => array('target' => '_blank'),'query' => array('view' => 'notify')));
          $RestaurantLink = Link::fromTextAndUrl(t($row->name), $RestaurantUrl)->toString();
          $output['html'] .= "<li>".$RestaurantLink."<span class='text-warning'> New</span></li>";
          $output['count']++;
        }
      }
      $output['html'] .= "</ul></div>";
    }
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public static function _get_updated_restaurants() {
    $currentUser = User::load(\Drupal::currentUser()->id());
    $restaurants = RestaurantController::getCurrentUserRestaurants([
            'pageSize' => 0,
            'conditionCallback' => function($query) {
                return($query);
            }
            ]);
    $isAdministrator = $currentUser->hasRole(RoleController::Administrator_Role_Name);
    $output = [];
    $output['html'] = '';
    $output['count'] = 0;
    if ($isAdministrator && !empty($restaurants)) {
      $output['html'] .= "<div class='restaurant-notifications updated-restaurant-list'><ul>";
      foreach ($restaurants as $key => $row) {
        if (RestaurantList::restaurant_mark($row->restaurant_id, $row->changed) == 2) {
          $RestaurantUrl = Url::fromRoute('entity.food_restaurant.canonical',
            array('food_restaurant' => $row->restaurant_id),
            array('attributes' => array('target' => '_blank'),'query' => array('view' => 'notify')));
          $RestaurantLink = Link::fromTextAndUrl(t($row->name), $RestaurantUrl)->toString();
          $output['html'] .= "<li>".$RestaurantLink."<span class='text-warning'> Updated</span></li>";
          $output['count']++;
        }
      }
      $output['html'] .= "</ul></div>";
    }
    return $output;
  }
  
  /**
   * {@inheritdoc}
   */
  public static function _get_adjustment() {
    $currentUser = User::load(\Drupal::currentUser()->id());
    $isPartner = $currentUser->hasRole(RoleController::Partner_Role_Name);
    $output = [];
    $output['html'] = '';
    $output['count'] = 0;
    if ($isPartner && db_field_exists('food_order_charge','notification')) {
      $output['html'] .= "<div class='restaurant-notifications adjustment-list'><ul>";
      $adjustments = \Drupal\food\Core\OrderController::getOrderCharges(\Drupal\food\Core\Order\OrderChargeType::Adjustment, [
                'pageSize' => 0,
                'conditionCallback' => function($query) use (&$request) {
                    
                    $query = $query
                            ->condition('notification', 1);

                    return($query);
                }
      ]);
      if(!empty($adjustments)){
        foreach ($adjustments as $key => $row) {
            $OrderUrl = Url::fromRoute('food.cart.order.confirmation', array('order_id' => $row->order_id),array('attributes' => array('target' => '_blank')));
            $OrderLink = Link::fromTextAndUrl(t('Order-'.$row->order_id), $OrderUrl)->toString();    
            $output['html'] .= "<li>".$OrderLink."<span class='text-warning'> New</span></li>";
            $output['count']++;
        }        
      }
      $output['html'] .= "</ul></div>";
    }
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public static function _get_customer_cancel_orders() {
    $currentUser = User::load(\Drupal::currentUser()->id());
    $isAdmin = $currentUser->hasRole(RoleController::Administrator_Role_Name);
    $isPartner = $currentUser->hasRole(RoleController::Partner_Role_Name);
    $output = [];
    $output['html'] = '';
    $output['count'] = 0;
    if (($isPartner || $isAdmin) && db_field_exists('food_order','notification')) {
      $output['html'] .= "<div class='restaurant-notifications order-cancel-list'><ul>";
      $orders = \Drupal\food\Core\OrderController::getCurrentPartnerOrders([
            'pageSize' => 0,
            'conditionCallback' => function($query) use (&$request) {                
                $query = $query
                    ->condition('fo.status', \Drupal\food\Core\Order\OrderStatus::Cancelled);                    
                    
                      $query = $query->isNotNull('notification');

                return($query);
            }
      ]);
      if(!empty($orders)){
        foreach ($orders as $key => $row) {
            $notification = json_decode($row->notification);
            if(($isAdmin && isset($notification->customer_cancel_order->admin) && $notification->customer_cancel_order->admin) || ($isPartner && isset($notification->customer_cancel_order->owner) && $notification->customer_cancel_order->owner)) {
              $OrderUrl = Url::fromRoute('food.cart.order.confirmation', array('order_id' => $row->order_id),array('attributes' => array('target' => '_blank')));
              $OrderLink = Link::fromTextAndUrl(t('Order-'.$row->order_id), $OrderUrl)->toString();    
              $output['html'] .= "<li>".$OrderLink."<span class='text-warning'> New</span></li>";
              $output['count']++;              
            }
        }        
      }
      $output['html'] .= "</ul></div>";
    }
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public static function _get_vendor_subuser_cancel_orders() {
    $currentUser = User::load(\Drupal::currentUser()->id());
    $isAdmin = $currentUser->hasRole(RoleController::Administrator_Role_Name);
    $isPartner = $currentUser->hasRole(RoleController::Partner_Role_Name);
    $output = [];
    $output['html'] = '';
    $output['count'] = 0;
    if (($isAdmin || $isPartner) && db_field_exists('food_order','notification')) {
      $output['html'] .= "<div class='restaurant-notifications vendor-subuser-order-cancel-list'><ul>";
      $orders = \Drupal\food\Core\OrderController::getCurrentPartnerOrders([
            'pageSize' => 0,
            'conditionCallback' => function($query) use (&$request) {                
                $query = $query
                    ->condition('fo.status', \Drupal\food\Core\Order\OrderStatus::Cancelled);                    
                    
                      $query = $query->isNotNull('notification');

                return($query);
            }
      ]);
      if(!empty($orders)){
        foreach ($orders as $key => $row) {
          $notification = json_decode($row->notification);
          if(($isAdmin && isset($notification->vendor_subuser_cancel_order->admin) && $notification->vendor_subuser_cancel_order->admin) || ($isPartner && isset($notification->vendor_subuser_cancel_order->owner) && $notification->vendor_subuser_cancel_order->owner)) {
            $OrderUrl = Url::fromRoute('food.cart.order.confirmation', array('order_id' => $row->order_id),array('attributes' => array('target' => '_blank')));
            $OrderLink = Link::fromTextAndUrl(t('Order-'.$row->order_id), $OrderUrl)->toString();    
            $output['html'] .= "<li>".$OrderLink."<span class='text-warning'> New</span></li>";
            $output['count']++;              
          }
        }        
      }
      $output['html'] .= "</ul></div>";
    }
    return $output;
  }
}