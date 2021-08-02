<?php

namespace Drupal\food\Plugin\Block;

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


/**
 * Provides a block with a simple text.
 *
 * @Block(
 *   id = "food_shortcut_menu_block",
 *   admin_label = @Translation("Food Shortcut Menu Block"),
 * )
 */
class ShortcutMenu extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
    $output = '';
    $currentUser = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());

    $isAdmin = $currentUser->hasRole(RoleController::Administrator_Role_Name);
    $isPartner = $currentUser->hasRole(RoleController::Partner_Role_Name);


    $output .= '<li class="ShortcutMenu"><a class="show-form" data-target="status_update">Update Status</a></li>
                <li class="ShortcutMenu"><a class="show-form" data-target="open_close_update">Update Opening/Closing</a></li>
                <li class="ShortcutMenu"><a class="show-form" data-target="pick_del_update">Update Pickup/Delivery</a></li>
                <li class="ShortcutMenu"><a class="show-form" data-target="menu_item_status_update">Update Menu Item</a></li>';
    if($isAdmin){
      $new_restaurants_output = \Drupal\food\Form\Shortcut\RestaurantNotification::_get_new_restaurants();
      $updated_restaurants_output = \Drupal\food\Form\Shortcut\RestaurantNotification::_get_updated_restaurants();
       $new_count ='';
       $update_count='';
    
      if ($new_restaurants_output['count'] == 0) {
        $new_restaurants_output['count'] = '';
         $new_count='';
      }else {
         $new_count = 'new-count';
      }

      if ($updated_restaurants_output['count'] == 0) {
        $updated_restaurants_output['count'] = '';
        $update_count='';
      }else {
      $update_count = 'update-count';
      }

      $output .= '<li class="ShortcutMenu"><a class="show-form" data-target="new_restaurants">New Restaurants <sup class="notification-count '.$new_count.'">' . $new_restaurants_output['count'] . '</sup></a></li>
                <li class="ShortcutMenu"><a class="show-form" data-target="updated_restaurants">Updated Restaurants <sup class="notification-count '.$update_count.'">' . $updated_restaurants_output['count'] . '<sup></a></li>';
    }

    if($isPartner){
      $adjustment_output = \Drupal\food\Form\Shortcut\RestaurantNotification::_get_adjustment();
        $adjustment_count = "";

      if ($adjustment_output['count'] == 0) {
        $adjustment_output['count'] = '';
         $adjustment_count = "";
      }else {
        $adjustment_count = 'adjustment-count'; 
     }

      $output .= '<li class="ShortcutMenu"><a class="show-form" data-target="order_adjustment">Adjustment <sup class="notification-count '.$adjustment_count.'">' . $adjustment_output['count'] . '</sup></a></li>';
    }

    if($isPartner || $isAdmin){
      $cancel_order_output = \Drupal\food\Form\Shortcut\RestaurantNotification::_get_customer_cancel_orders();
      $adjustment_count_ad = "";
      if ($cancel_order_output['count'] == 0) {
        $cancel_order_output['count'] = '';
        $adjustment_count_ad = "";
      }else{
        $adjustment_count_ad = 'adjustment-count'; 
    }
      
      $output .='<li class="ShortcutMenu"><a class="show-form" data-target="customer_cancel_order">Customer Cancel Order<sup class="notification-count '.$adjustment_count_ad.'">' . $cancel_order_output['count'] . '</sup></a></li>';
    }


    if($isPartner || $isAdmin){
      $vendor_subuser_cancel_order_output = \Drupal\food\Form\Shortcut\RestaurantNotification::_get_vendor_subuser_cancel_orders();
      $vender_subuser_cancel_order_count  = "";
      if($vendor_subuser_cancel_order_output['count'] == 0){
        $vendor_subuser_cancel_order_output['count'] = '';
        $vender_subuser_cancel_order_count = '';
      }else{
        $vender_subuser_cancel_order_count = 'adjustment-count';
      }
      $output .='<li class="ShortcutMenu"><a class="show-form" data-target="vendor_subuser_cancel_order">Vendor/Subuser Cancel Order<sup class="notification-count '.$vender_subuser_cancel_order_count.'">' . $vendor_subuser_cancel_order_output['count'] . '</sup></a></li>';
    }

    $allrestaurants = \Drupal\food\Core\RestaurantController::getCurrentUserRestaurants([
            'pageSize' => 0,
            'conditionCallback' => function($query) {
                return($query);
            }
            ]);
    $restaurants = \Drupal\food\Core\RestaurantController::getCurrentUserRestaurants([
            'pageSize' => 0,
            'conditionCallback' => function($query) {
                $query = $query->condition('fr.status', 1);
                return($query);
            }
            ]);
    $current_user_restaurant = array();
    $all_user_restaurant = array();

    if(!empty($restaurants)){
        foreach ($restaurants as $key => $value) {
            $current_user_restaurant[$value->restaurant_id] = $value->name;
        }
    }
    
     if(!empty($allrestaurants)){
        foreach ($allrestaurants as $key => $value) {
            $all_user_restaurant[$value->restaurant_id] = $value->name;
        }
    }

    $status_form = \Drupal::formBuilder()->getForm('Drupal\food\Form\Shortcut\StatusUpdateForm',$all_user_restaurant);
    $open_close_form = \Drupal::formBuilder()->getForm('Drupal\food\Form\Shortcut\OpenCloseForm',$current_user_restaurant);
    $pickup_delivery_form = \Drupal::formBuilder()->getForm('Drupal\food\Form\Shortcut\PickupDeliveryForm',$current_user_restaurant);
    $menu_item_status_form = \Drupal::formBuilder()->getForm('Drupal\food\Form\Shortcut\MenuItemStatusForm',$current_user_restaurant);

    $output .= '<div class="collapse ShortcutMenuContent" data-target="status_update"><div class="show-form"><a><i class="fa fa-times-circle-o" aria-hidden="true"></i></a></div>'.render($status_form).'</div>
                <div class="collapse ShortcutMenuContent" data-target="open_close_update"><div class="show-form"><a><i class="fa fa-times-circle-o" aria-hidden="true"></i></a></div>'.render($open_close_form).'</div>
                <div class="collapse ShortcutMenuContent" data-target="pick_del_update"><div class="show-form"><a><i class="fa fa-times-circle-o" aria-hidden="true"></i></a></div>'.render($pickup_delivery_form).'</div>
                <div class="collapse ShortcutMenuContent" data-target="menu_item_status_update"><div class="show-form"><a><i class="fa fa-times-circle-o" aria-hidden="true"></i></a></div>'.render($menu_item_status_form).'</div>';

    if($isAdmin){
      $output .= '<div class="collapse ShortcutMenuContent" data-target="new_restaurants"><div class="show-form"><a><i class="fa fa-times-circle-o" aria-hidden="true"></i></a></div>'.render($new_restaurants_output['html']).'</div>
                <div class="collapse ShortcutMenuContent" data-target="updated_restaurants"><div class="show-form"><a><i class="fa fa-times-circle-o" aria-hidden="true"></i></a></div>'.render($updated_restaurants_output['html']).'</div>';
    }

    if($isPartner){
      $output .=  '<div class="collapse ShortcutMenuContent" data-target="order_adjustment"><div class="show-form"><a><i class="fa fa-times-circle-o" aria-hidden="true"></i></a></div>'.render($adjustment_output['html']).'</div>';
    }

    if($isPartner || $isAdmin){
      $output .='<div class="collapse ShortcutMenuContent" data-target="customer_cancel_order"><div class="show-form"><a><i class="fa fa-times-circle-o" aria-hidden="true"></i></a></div>'.render($cancel_order_output['html']).'</div>';
      $output .='<div class="collapse ShortcutMenuContent" data-target="vendor_subuser_cancel_order"><div class="show-form"><a><i class="fa fa-times-circle-o" aria-hidden="true"></i></a></div>'.render($vendor_subuser_cancel_order_output['html']).'</div>';
    }

    return array(
      '#children' => $output,
      '#attached' => array(
        'library' => 'food/food.shortcutmenublock',
        'drupalSettings' => array (
          'food' => array(
            'newShortcutNotificationRefreshUrl' => Url::fromRoute('food.notifications.newshortcutnotificationrefresh')->toString(),
            'updatedShortcutNotificationRefreshUrl' => Url::fromRoute('food.notifications.updatedshortcutnotificationrefresh')->toString(),
            'adjustmentNotificationRefreshUrl' => Url::fromRoute('food.notifications.adjustmentupdatednotificationrefresh')->toString(),
            'customerCancelOrderNotificationRefreshUrl' => Url::fromRoute('food.notifications.customercancelordernotificationrefresh')->toString(),
            'vendorSubuserCancelOrderNotificationRefreshUrl' => Url::fromRoute('food.notifications.vendorsubusercancelordernotificationrefresh')->toString(),
            'admin' => $isAdmin,
            'partner' => $isPartner,
          ),
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
 protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'food shortcut menu access');
  }
  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['food_shortcut_menu_block_settings'] = $form_state->getValue('food_shortcut_menu_block_settings');
  }

}
