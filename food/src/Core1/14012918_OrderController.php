<?php

namespace Drupal\food\Core;

use Drupal\Core\Url;
use Imbibe\Util\PhpHelper;

abstract class OrderController extends ControllerBase {

  public static function setLastPendingOrderId($order_id) {
    setcookie('food_partner_last_pending_order_id', $order_id,
      strtotime('+1 day'), "/");
  }

  public static function getLastPendingOrderId() {
    if (isset($_COOKIE['food_partner_last_pending_order_id'])) {
      return ($_COOKIE['food_partner_last_pending_order_id']);
    }
    else {
      return (NULL);
    }
  }

  public static function getOrderByCartId($cart_id) {
    $query = db_select('food_order', 'fo')
      ->condition('cart_id', $cart_id)
      ->fields('fo');

    $row = ControllerBase::executeRowQuery($query,
      array('\Drupal\food\Core\OrderController', 'hydrateOrder'));
    return ($row);
  }

  public static function getOrdersByUserId($user_id, $config = array()) {
    $query = db_select('food_order', 'fo')
      ->condition('user_id', $user_id)
      ->fields('fo');

    $config['hydrateCallback'] = array(
      '\Drupal\food\Core\OrderController',
      'hydrateOrder',
    );
    $config['defaultSortField'] = ['created_time', 'DESC'];
    $row = ControllerBase::executeListQuery($query, $config);
    return ($row);
  }

  public static function getOrderByOrderId($order_id) {
    $query = db_select('food_order', 'fo')
      ->condition('order_id', $order_id)
      ->fields('fo');

    $row = ControllerBase::executeRowQuery($query,
      array('\Drupal\food\Core\OrderController', 'hydrateOrder'));
    return ($row);
  }

  public static function getCurrentPartnerOrders($config = array()) {
    $currentUser = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
    $isPlatform = $currentUser->hasRole(RoleController::Platform_Role_Name);

    $query = db_select('food_order', 'fo')->fields('fo');

    if ($isPlatform != TRUE) {
      $innerQuery = db_select('food_restaurant', 'fr')
        ->condition('owner_user_id', $currentUser->id())
        ->fields('fr', ['restaurant_id']);
      $query = $query->condition('restaurant_id', $innerQuery, 'IN');
    }

    $conditionCallback = PhpHelper::getNestedValue($config,
      ['conditionCallback']);
    if ($conditionCallback != NULL) {
      $query = call_user_func_array($conditionCallback, [$query]);
    }

    $config['defaultSortField'] = ['order_id', 'DESC'];
    $config['hydrateCallback'] = array(
      '\Drupal\food\Core\OrderController',
      'hydrateOrder',
    );
    $rows = ControllerBase::executeListQuery($query, $config);
    return ($rows);
  }

  public static function getUsersAndHisOrders($config = array()) {
    $currentUser = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
    $isPlatform = $currentUser->hasRole(RoleController::Platform_Role_Name);

    $query = db_select('users_field_data', 'ufd')->fields('ufd');

    $conditionCallback = PhpHelper::getNestedValue($config,
      ['conditionCallback']);
    if ($conditionCallback != NULL) {
      $query->leftJoin('food_order', 'fo', 'ufd.uid = fo.user_id');
      $query->fields('ufd');
      $query = call_user_func_array($conditionCallback, [$query]);
    }

    $config['defaultSortField'] = ['created', 'DESC'];
    $rows = ControllerBase::executeListQuery($query, $config);
    return ($rows);
  }


  public static function findOrders($config = array()) {
    $query = db_select('food_order', 'fo')->fields('fo');

    $conditionCallback = PhpHelper::getNestedValue($config,
      ['conditionCallback']);
    if ($conditionCallback != NULL) {
      $query = call_user_func_array($conditionCallback, [$query]);
    }

    $config['defaultSortField'] = ['created_time', 'DESC'];
    $config['hydrateCallback'] = array(
      '\Drupal\food\Core\OrderController',
      'hydrateOrder',
    );
    $rows = ControllerBase::executeListQuery($query, $config);
    return ($rows);
  }

  public static function hydrateOrder($row) {
    $row->order_details = \Imbibe\Json\JsonHelper::deserializeObject($row->order_details,
      '\Drupal\food\Core\Order\Order');
    $row->meta = \Imbibe\Json\JsonHelper::deserializeObject($row->meta,
      '\Drupal\food\Core\Order\OrderMeta');

    if (empty($row->meta)) {
      $row->meta = new \Drupal\food\Core\Order\OrderMeta();
    }

    $row->derived_fields = new \StdClass();
    $row->derived_fields->created_time_formatted = date("F j, Y, g:i a",
      $row->created_time / 1000.0);
    if ($row->processed_time != NULL) {
      $row->derived_fields->processed_time_formatted = date("F j, Y, g:i a",
        $row->processed_time / 1000.0);
    }

    $row->derived_fields->partnerConfirmUrl = Url::fromRoute('food.partner.order.confirm',
      ['restaurant_id' => $row->restaurant_id, 'order_id' => $row->order_id]);
    $row->derived_fields->partnerConfirmUrl->setOptions([
      'attributes' => [
        'class' => ['use-ajax'],
      ],
    ]);
    $row->derived_fields->partnerConfirmUrl = $row->derived_fields->partnerConfirmUrl->toString();

    $row->derived_fields->partnerCancelUrl = Url::fromRoute('food.partner.order.cancel',
      ['restaurant_id' => $row->restaurant_id, 'order_id' => $row->order_id]);
    $row->derived_fields->partnerCancelUrl->setOptions([
      'attributes' => [
        'class' => ['use-ajax'],
      ],
    ]);
    $row->derived_fields->partnerCancelUrl = $row->derived_fields->partnerCancelUrl->toString();
  }

  public static function confirmOrder($order) {
    $order->status = \Drupal\food\Core\Order\OrderStatus::Confirmed;
    $order->processed_by = \Drupal::currentUser()->id();
    $order->processed_time = \Imbibe\Util\TimeUtil::now();

    self::updateOrder($order);

    \Drupal::moduleHandler()->invokeAll('food_order_confirm', array($order));
  }

  public static function cancelOrder($order) {
    $order->status = \Drupal\food\Core\Order\OrderStatus::Cancelled;
    $order->processed_by = \Drupal::currentUser()->id();
    $order->processed_time = \Imbibe\Util\TimeUtil::now();

    self::updateOrder($order);

    \Drupal::moduleHandler()->invokeAll('food_order_cancel', array($order));
  }

  public static function updateOrder($order) {
    if (isset($order->order_details->breakup->net_amount)) {
      $order->net_amount = $order->order_details->breakup->net_amount;
    }
    elseif (empty($order->net_amount)) {
      $order->net_amount = 0;
    }

    $order = self::prepareForUpdation('food_order', $order);

    db_update('food_order')
      ->fields($order)
      ->condition('order_id', $order['order_id'])
      ->execute();
  }

  public static function getRecentlyOrderedItems($config = array()) {
    $result = db_select('config', 'c')
      ->fields('c')
      ->condition('name', 'food.order.recently_ordered_item_ids')
      ->execute()
      ->fetchObject();

    if ($result) {
      $decodedData = unserialize($result->data);
      $items = \Imbibe\Json\JsonHelper::deserializeArray($decodedData,
        '\Drupal\food\Core\Order\RecentOrderItem');
    }
    else {
      $returnNull = PhpHelper::getNestedValue($config, ['returnNull'], FALSE);
      if ($returnNull) {
        return (NULL);
      }
      else {
        $items = [];
      }
    }

    $convertToMenuItems = PhpHelper::getNestedValue($config,
      ['convertToMenuItems'], TRUE);
    if ($convertToMenuItems == FALSE) {
      return ($items);
    }

    $restaurant_menu_item_ids = array_map(function ($item) {
      return ($item->restaurant_menu_item_id);
    }, $items);

    if (count($restaurant_menu_item_ids) > 0) {
      $menu_items = MenuController::searchRestaurantMenuItems(function ($query) use
      (
        $restaurant_menu_item_ids
      ) {
        $query = $query->condition('fm.restaurant_menu_item_id',
          $restaurant_menu_item_ids, 'IN');
        return ($query);
      });
      self::assignEntityRestaurants($menu_items);
    }
    else {
      $menu_items = [];
    }

    return ($menu_items);
  }

  public static function recordRecentlyOrderedItems($items) {
    $existingItems = self::getRecentlyOrderedItems([
      'returnNull' => TRUE,
      'convertToMenuItems' => FALSE,
    ]);
    if ($existingItems == NULL) {
      $existingItems = [];
      $update = FALSE;
    }
    else {
      $update = TRUE;
    }

    foreach ($items as $item) {
      $exists = FALSE;
      foreach ($existingItems as $existingItem) {
        if ($existingItem->restaurant_menu_item_id == $item->restaurant_menu_item_id) {
          $exists = TRUE;
          break;
        }
      }

      if (!$exists) {
        $recentItem = new \Drupal\food\Core\Order\RecentOrderItem();
        $recentItem->restaurant_menu_item_id = $item->restaurant_menu_item_id;

        array_splice($existingItems, 0, 0, [$recentItem]);
      }
    }

    if (count($existingItems) > 10) {
      array_splice($existingItems, 10);
    }

    $encodedData = json_encode($existingItems);
    $encodedData = serialize($encodedData);

    if ($update) {
      db_update('config')
        ->fields(array(
          'collection' => '',
          'data' => $encodedData,
        ))
        ->condition('name', 'food.order.recently_ordered_item_ids')
        ->execute();
    }
    else {
      db_insert('config')->fields(array(
        'collection' => '',
        'name' => 'food.order.recently_ordered_item_ids',
        'data' => $encodedData,
      ))->execute();
    }
  }

  public static function getOrderCharges($orderChargeType, $config = array()) {
    $currentUser = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
    $isPlatform = $currentUser->hasRole(RoleController::Platform_Role_Name);

    $query = db_select('food_order_charge', 'fo')
      ->fields('fo')
      ->condition('charge_type', $orderChargeType);

    if ($isPlatform != TRUE) {
      $innerQuery = db_select('food_restaurant', 'fr')
        ->condition('owner_user_id', $currentUser->id())
        ->fields('fr', ['restaurant_id']);
      $query = $query->condition('restaurant_id', $innerQuery, 'IN');

    }

    $conditionCallback = PhpHelper::getNestedValue($config,
      ['conditionCallback']);
    if ($conditionCallback != NULL) {
      $query = call_user_func_array($conditionCallback, [$query]);
    }

    $config['defaultSortField'] = ['created_time', 'DESC'];
    $config['hydrateCallback'] = array(
      '\Drupal\food\Core\OrderController',
      'hydrateOrder',
    );
    $rows = ControllerBase::executeListQuery($query, $config);
    return ($rows);
  }

  public static function buildOrderNotificationBody($order, $isFax) {
    $platform_settings = \Drupal\food\Core\PlatformController::getPlatformSettings();
    $restaurant = \Drupal\food\Core\RestaurantController::getRestaurantById($order->restaurant_id);
    //\Drupal\food\Core\UserController::validateCurrentUserOrAdmin($order->user_id);

    $orderUserUrl = empty($order->meta->user_short_url) ? Url::fromRoute('food.cart.order.confirmation',
      ['order_id' => $order->order_id])
      ->setAbsolute()
      ->toString() : $order->meta->user_short_url;
    $orderConfirmationUrl = Url::fromRoute('food.cart.order.confirm', [
      'restaurant_id' => $order->restaurant_id,
      'order_id' => $order->order_id,
    ])->setAbsolute()->toString();
    if ($order->order_details->delivery_mode == \Drupal\food\Core\Restaurant\DeliveryMode::Delivery) {
      $order_mode = 'DELIVERY';
    }
    else {
      $order_mode = 'PICKUP';
    }
    $currencySymbol = $platform_settings->derived_settings->currency_symbol;
    $order_number_prefix = PhpHelper::getNestedValue($platform_settings,
      ['order_settings', 'order_number_prefix']);
    global $baseUrl;
    $siteUrl = $baseUrl;
    $message = '<html><body>
    <div class="c-form" style="width: 681px; margin: 0 auto; padding: 28px 13px; border: 14px solid #02c0d2; text-align: center; background: #f5f5f5; border-radius: 5px;">
   <img src="' . Url::fromUri('internal:' . $platform_settings->derived_settings->logo_url,
        ['absolute' => TRUE])
        ->toString() . '" alt="' . $platform_settings->derived_settings->site_name . '" border="0" width="20%" style="vertical-align: middle; display: inline-flex; max-width: 185px;" class="CToWUd" />
  <h2 style="background: #ffa600; padding: 5px 7px; color: #3e3e3e; border-radius: 4px; font-family:HelveticaNeue,"Helvetica Neue",Helvetica,Arial,"Lucida Grande",sans-serif;">Dear ' . $order->user_name . ',</h2>   
<p>Thanks for placing the Order with  <strong style="color:#375623 !important; font:14px HelveticaNeue,"Helvetica Neue",Helvetica,Arial,"Lucida Grande",sans-serif;">' . $restaurant->name . '</strong><strong style="color:#375623 !important; font:14px HelveticaNeue,"Helvetica Neue",Helvetica,Arial,"Lucida Grande",sans-serif;">' . $order_number_prefix . '# ' . $order->order_id . '</p>';
    if (!$isFax) {
      $message .= '<div style="margin-bottom:10px;padding:17px;font-size:14pt;text-align:center">
    <a href = "' . $orderConfirmationUrl . '" style = "text-decoration:none;color:#000" target = "_blank" ><b> VIEW MY ORDER </b></a ></div > ';
    }

    $message .= '<p>As your order accepts by Restaurant we will inform</p>
            <p>you in no time. till then get relaxed.</p>
            <p>Our Team may contact if adjustment needed.</p>
            <p>Please feel free to contact us anytime at 8885181475</p>
   <p style="font:12px normal HelveticaNeue,Helvetica Neue",Helvetica,Arial,"Lucida Grande",sans-serif; color:#000; display:-webkit-inline-box">Have a great day!</p>
   Thank you
   <p style="margin-top:20px"><a href="' . $siteUrl . '" >' . $platform_settings->derived_settings->site_name . ' team</a><br></p>
   <p style="margin-top:20px"><a href="' . $siteUrl . '" >Make Another Order</a><br></p>
   <p style="margin-top:20px"><a href="' . $siteUrl . '" >Cancel my Food</a><br></p>
  </div>
 </body>
</html>';

    return ($message);
  }

  public static function buildOrderConfirmationBody($order) {
    $platform_settings = \Drupal\food\Core\PlatformController::getPlatformSettings();
    $order_number_prefix = PhpHelper::getNestedValue($platform_settings,
      ['order_settings', 'order_number_prefix']);

    $restaurant = \Drupal\food\Core\RestaurantController::getRestaurantById($order->restaurant_id);
    //\Drupal\food\Core\UserController::validateCurrentUserOrAdmin($order->user_id);
    $siteUrl = Url::fromRoute('<front>')->setAbsolute()->toString();

    $message = '<html>
 <body>
  <div class="c-form" style="width: 681px; margin: 0 auto; padding: 28px 13px; border: 14px solid #02c0d2; text-align: center; background: #f5f5f5; border-radius: 5px;">
   <img src="' . Url::fromUri('internal:' . $platform_settings->derived_settings->logo_url,
        ['absolute' => TRUE])
        ->toString() . '" alt="' . $platform_settings->derived_settings->site_name . '" border="0" width="20%" style="vertical-align: middle; display: inline-flex; max-width: 185px;" class="CToWUd" />
   <h2 style="background: #ffa600; padding: 5px 7px; color: #3e3e3e; border-radius: 4px; font-family:HelveticaNeue,"Helvetica Neue",Helvetica,Arial,"Lucida Grande",sans-serif;">Dear ' . $order->user_name . ',</h2>
   <p>Your Order No. ' . $order_number_prefix . '# ' . $order->order_id . ' is being cooked and will be delivered to you in scheduled time. </p>
   <p>Please share your experience as it will help you to give you better service next time!</p>
            <p style="margin-top:20px"><a href="' . $siteUrl . '" >Review and Rating</a><br></p>
   <p style="font:12px/normal HelveticaNeue,"Helvetica Neue",Helvetica,Arial,"Lucida Grande",sans-serif; color:#000; display:-webkit-inline-box">If you have any questions or concerns, call our </p>
           <p> Customer Service team at (888) 518-1475.</p>
         <p style="font:12px/normal HelveticaNeue,"Helvetica Neue",Helvetica,Arial,"Lucida Grande",sans-serif; color:#000; display:-webkit-inline-box">  or talk with restaurant at ' . $restaurant->phone_number . '.
   Thank you
   <p style="margin-top:20px"><a href="' . $siteUrl . '" >' . $platform_settings->derived_settings->site_name . ' team</a><br></p>
  </div>
 </body>
</html>';

    return ($message);
  }

  public static function buildOrderCancellationBody($order) {
    $platform_settings = \Drupal\food\Core\PlatformController::getPlatformSettings();
    $order_number_prefix = PhpHelper::getNestedValue($platform_settings,
      ['order_settings', 'order_number_prefix']);

    $restaurant = \Drupal\food\Core\RestaurantController::getRestaurantById($order->restaurant_id);
    //\Drupal\food\Core\UserController::validateCurrentUserOrAdmin($order->user_id);
    $siteUrl = Url::fromRoute('<front>')->setAbsolute()->toString();

    $message = '<html>
 <body>
    <div class="c-form" style="width: 681px; margin: 0 auto; padding: 28px 13px; border: 14px solid #02c0d2; text-align: center; background: #f5f5f5; border-radius: 5px;">
   <img src="' . Url::fromUri('internal:' . $platform_settings->derived_settings->logo_url,
        ['absolute' => TRUE])
        ->toString() . '" alt="' . $platform_settings->derived_settings->site_name . '" border="0" width="20%" style="vertical-align: middle; display: inline-flex; max-width: 185px;" class="CToWUd" />
     <h2 style="background: #ffa600; padding: 5px 7px; color: #3e3e3e; border-radius: 4px; font-family:HelveticaNeue,"Helvetica Neue",Helvetica,Arial,"Lucida Grande",sans-serif;">Dear ' . $order->user_name . ',</h2>
   <h2 style="font-size:26px; font-weight:bold; margin:0px; padding-bottom:15px; color:#000; font-family:HelveticaNeue,"Helvetica Neue",Helvetica,Arial,"Lucida Grande",sans-serif;">Attention! Order ' . $order_number_prefix . '# ' . $order->order_id . ' cancelled!</h2>
   <p>We apologize that  <strong style="color:#375623 !important; font:14px HelveticaNeue,"Helvetica Neue",Helvetica,Arial,"Lucida Grande",sans-serif;">' . $restaurant->name . '</strong> is unable to process your order  <strong style="color:#375623 !important; font:14px HelveticaNeue,"Helvetica Neue",Helvetica,Arial,"Lucida Grande",sans-serif;">' . $order_number_prefix . '# ' . $order->order_id . '</p>
   <p>right now.You may choose another Restaurant</p>
            <p>OR</p>
            <p>click here to get a call from our service team or </p>
            <p>dial (888) 518-1475 now for more option</p>
   <p style="font:12px/normal HelveticaNeue,"Helvetica Neue",Helvetica,Arial,"Lucida Grande",sans-serif; color:#000; display:-webkit-inline-box">Have a great day!</p>
   Thank you
   <p style="margin-top:20px"><a href="' . $siteUrl . '" >' . $platform_settings->derived_settings->site_name . ' team</a><br></p>
  </div>
 </body>
</html>';

    return ($message);
  }

}