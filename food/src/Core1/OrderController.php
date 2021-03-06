<?php

namespace Drupal\food\Core;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Link;
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
    $isPartner = $currentUser->hasRole(RoleController::Partner_Role_Name);
    $isSubuser = $currentUser->hasRole(RoleController::Subuser_Role_Name);

    $query = db_select('food_order', 'fo')
    ->fields('fo');

    if ($isPartner) {
      $innerQuery = db_select('food_restaurant', 'fr')
        ->condition('fr.owner_user_id', $currentUser->id())
        ->fields('fr', ['restaurant_id']);
      $query = $query->condition('fo.restaurant_id', $innerQuery, 'IN');
    }
    elseif ($isSubuser) {
      $restaurants = \Drupal\food\Core\SubuserController::getSubuserRestaurantsIds();
      $restaurant_owner_id = \Drupal\food\Core\SubuserController::getRestaurantOwner($restaurants[0]);
      if(!empty($restaurants)){
       $innerQuery = db_select('food_restaurant', 'fr')
        ->condition('fr.owner_user_id', $restaurant_owner_id)
        ->condition('fr.restaurant_id', $restaurants, 'IN')
        ->fields('fr', ['restaurant_id']);
      $query = $query->condition('fo.restaurant_id', $innerQuery, 'IN');   
      }else{
          $query = $query->condition('fo.restaurant_id', 0);
      }
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

  public static function getCurrentPartnerDeposit($restaurant_id = NULL,
    $deposit_uid = NULL) {
    $currentUser = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
    $isPlatform = $currentUser->hasRole(RoleController::Platform_Role_Name);
    $isPartner = $currentUser->hasRole(RoleController::Partner_Role_Name);
    $isSubuser = $currentUser->hasRole(RoleController::Subuser_Role_Name);

    if (empty($restaurant_id)) {
      return;
    }

    $db = \Drupal::database();
    $query = $db->select('food_deposit_account_history', 'fd');
    $query->fields('fd');
    if ($restaurant_id != NULL && !is_array($restaurant_id)) {
      $query->condition('restaurant_id', $restaurant_id);
    }
    elseif ($restaurant_id != NULL && is_array($restaurant_id)) {
      $query->condition('restaurant_id', $restaurant_id, 'IN');
    }
    if ($deposit_uid != NULL) {
      $query->condition('depositor_uid', $deposit_uid);
    }
    $rows = $query->execute()->fetchAll();

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
       $query->fields('fo');
       $query->addField('ufd', 'status', 'ustatus');
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

    $row->derived_fields->partnerOrderCancelCommentUrl = Url::fromRoute('food.partner.order.cancel.comment.add',
      ['restaurant_id' => $row->restaurant_id, 'order_id' => $row->order_id]);
    $row->derived_fields->partnerOrderCancelCommentUrl->setOptions([
      'attributes' => [
        'style' => 'background:#e11b1b; color:#fff; padding:5px; border-radius:5px;',
        'class' => ['use-ajax', 'food-partner-order-cancel-comment-link'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode([
          'width' => 700,
        ]),
      ],
    ]);

    $row->derived_fields->partnerOrderCancelCommentUrl = Link::fromTextAndUrl('Cancel order',
      $row->derived_fields->partnerOrderCancelCommentUrl);

    $row->derived_fields->partnerOrderCancelCommentUrl = $row->derived_fields->partnerOrderCancelCommentUrl->toString();
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
    $isPartner = $currentUser->hasRole(RoleController::Partner_Role_Name);
    $isSubuser = $currentUser->hasRole(RoleController::Subuser_Role_Name);

    $query = db_select('food_order_charge', 'fo')
      ->fields('fo')
      ->condition('charge_type', $orderChargeType);

    if ($isPartner) {
      $innerQuery = db_select('food_restaurant', 'fr')
        ->condition('owner_user_id', $currentUser->id())
        ->fields('fr', ['restaurant_id']);
      $query = $query->condition('restaurant_id', $innerQuery, 'IN');

    }
    elseif ($isSubuser) {
      $restaurants = \Drupal\food\Core\SubuserController::getSubuserRestaurantsIds();
      $restaurant_owner_id = \Drupal\food\Core\SubuserController::getRestaurantOwner($restaurants[0]);
      $innerQuery = db_select('food_restaurant', 'fr')
        ->condition('owner_user_id', $restaurant_owner_id)
        ->condition('restaurant_id', $restaurants, 'IN')
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

    $message = '<html><body>';
    $message .= '
		<div style="font-family:Arial; width: 80%; margin-left: 80px;">
        <div style=" height:60px; margin: 0 auto; padding: 5px 13px; background: #5cb439;">
				<div style="float:left">	<img src="https://www.foodondeal.com/images/logo.png" border="0" width="20%" style="vertical-align: middle; display: inline-flex; height: auto; width: 35%;" class="CToWUd" /></div>
		  <div style="float:right; color:#fff">  <h2 style="padding: 5px 7px; color: #fff; float: right; margin-top: 10px; border-radius: 4px; font-family:HelveticaNeue,"Helvetica Neue",Helvetica,Arial,"Lucida Grande",sans-serif;">Dear ' . $order->user_name . ',</h2></div>
			</div>';

    /*if (!$isFax) {
        $message.= '
  <div style="margin-bottom:10px;padding:17px 0;font-size:14pt;text-align:center">
    <a href="' . $orderConfirmationUrl . '" style="text-decoration:none;color:#000" target="_blank"><b>Click Here to confirm this order</b></a>
  </div>';
    }*/

    $message .= '
			<div style="margin-bottom:10px;border-bottom:1px dashed black"></div>
			<div style="width:100%;background:#ffffff">
				<table width="100%" border="0" cellpadding="0" cellspacing="0" style="padding-bottom:10px;border-bottom:1px dashed black">
					<tbody>
						<tr>
							<td align="left">&nbsp;</td>
							<td align="left" style="font-size:12pt">
								<div><b>' . $restaurant->name . '</b></div>
								<div>
									<b>';
    $message .= $restaurant->derived_fields->formatted_address;
    $message .= '
									</b>
								</div>
								<div style="padding:3px 0">ORDER <a href="' . $orderUserUrl . '">' . $order_number_prefix . '# <b>' . $order->order_id . '</b></a></div>
								<div>Placed on: ' . $order->derived_fields->created_time_formatted . '</div>
							</td>
						</tr>
					</tbody>
				</table>

				<table width="100%" border="0" cellpadding="0" cellspacing="0" style="padding:10px 12px;border:1px solid black">
					<tbody>
						<tr>
							<td align="left" style="font-size:18pt">';
    if (!empty($order->order_details->schedule_date)) {
      $message .= '
								<b>PLEASE COMPLETE THIS ORDER BY<br />' . $order->order_details->schedule_date . ' ' . $order->order_details->schedule_time . '</b>';
    }

    $message .= '
							</td>
							<td align="right" style="font-size:30pt;text-transform:uppercase"><b>' . $order_mode . '</b></td>
						</tr>
					</tbody>
				</table>
				
				<table width="100%" border="0" cellpadding="0" cellspacing="0" style="font-size:12pt;border-bottom:2px solid black">
					<tbody>
						<tr>
							<td width="33%" style="padding:10px 0">
								<div>
									<div>' . $order->user_name . '</div>
									<div style="font-size:14pt"><b><a href="tel:' . $order->user_phone . '" value="" target="_blank">' . $order->user_phone . '</a></b></div>';

    if ($order->order_details->delivery_mode == \Drupal\food\Core\Restaurant\DeliveryMode::Delivery) {
      $message .= '<div>' . $order->user_apartment_number . ' ' . $order->user_address . '</div>';
    }

    $message .= '
									</div>
								</div>
							</td>
						</tr>
					</tbody>
				</table>
				
				<table width="100%" border="0" cellpadding="0" cellspacing="0" style="border-bottom:2px solid black">
					<tbody>
						<tr style="font-size:11pt">
							<th align="left" width="50" style="padding:7px 0 0"><b>Qty</b></th>
							<th align="left" style="padding:7px 0 0"><b>Item</b></th>
							<th align="right" width="90" style="padding:7px 0 0"><b>Price</b></th>
						</tr>';

    if (is_array($order->order_details->items)) {
      foreach ($order->order_details->items as $curIndex => $item) {
        $message .= '
						<tr>
							<td style="padding:7px 0;font-size:14pt;border-bottom:1px dashed black;vertical-align:top"><b>' . $item->quantity . 'x</b></td>
							<td style="padding:7px 0;border-bottom:1px dashed black">
								<table border="0" cellpadding="0" cellspacing="0">
									<tbody>
										<tr>
											<td colspan="2" style="padding-bottom:3px;font-size:14pt">' . $item->item_name . '</td>
										</tr>';

        foreach ($item->options as $keys => $option) {
          if (!empty($option)) {
            $message .= '
										<tr style="font-size:11pt; ">
											<td colspan="2" style="  text-align:left;  color: black;"><b>' . $option->category_name . ' : ' . $option->option_name . '</b>:';

            $message .= '<span>' . $option->option_name . '</span>';

            $message .= '			</td>
										</tr>';
          }
        }

        if (!empty($item->instructions)) {
          $message .= '
										<tr>
											<td style="font-size:11pt;vertical-align:top">
												<b>Instructions: <i style="font-size:13pt">' . $item->instructions . '</i></b>
											</td>
										</tr>';
        }

        $message .= '		</tbody>
								</table>
							</td>
							<td align="right" style="padding:7px 0;font-size:13pt;border-bottom:1px dashed black;vertical-align:top">' . $currencySymbol . ' ' . ($item->item_total_amount) . '</td>
						</tr>';
      }
    }

    if (!empty($order->order_details->instructions)) {
      $message .= '
						<tr>
							<td colspan="3" style="font-size:11pt;vertical-align:top">
								<b>Instructions:</b> <i style="font-size:11pt">' . $order->order_details->instructions . '</i>
							</td>
						</tr>';
    }

    if (!empty($order->order_details->num_people)) {
      $people = $order->order_details->num_people;
    }
    else {
      $people = 1;
    }

    if (!empty($order->order_details->condiments)) {
      $message .= '
						<tr>
							<td colspan="2" style="padding:7px 0;font-size:13pt;"><b>Condiments & Utensils</b> ' . implode(', ',
          $order->order_details->condiments) . ' <br/>
								Number of people ' . $people . '
							</td>
						</tr>';
    }

    $message .= '
					</tbody>   
				</table>

				<div style="padding-top:7px;font-size:12pt;text-align:center"><b>END OF ORDER</b></div>
				<table width="100%" border="0" cellpadding="0" cellspacing="0" style="padding:0 0 10px;font-size:12pt">
					<tbody>
						<tr>
							<td width="50%" style="vertical-align:top">';

    if ($order->order_details->payment_mode == \Drupal\food\Core\Restaurant\PaymentMode::CashOnDelivery) {
      $message .= '
								<div style="width:150px; padding:8px; border:1px solid black">WILL PAY BY
									<div style="font-size:18pt"><b>CASH</b></div>
								</div>';
    }
    else {
      $message .= '
								<div style="width:150px; padding:8px; border:1px solid black">ALREADY PAID DO NOT
									<div style="font-size:18pt"><b>CHARGE</b></div>
								</div>';
    }

    $message .= '
							</td>
							<td>
								<table width="100%" border="0" cellpadding="0" cellspacing="0">
									<tbody>
										<tr>
											<td align="right" style="font-size:10pt;vertical-align:bottom">SUBTOTAL</td>
											<td align="right" width="125">' . $currencySymbol . ' ' . $order->order_details->breakup->items_total_amount . '</td>
										</tr>';

    if ($order->order_details->breakup->total_discount_amount > 0) {
      $message .= ' 	
										<tr>
											<td align="right" style="font-size:10pt;vertical-align:bottom">
												Discount
											</td>
											<td align="right">' . $currencySymbol . ' ' . $order->order_details->breakup->total_discount_amount . '</td>
										</tr>';
    }

    if ($order->order_details->delivery_mode == \Drupal\food\Core\Restaurant\DeliveryMode::Delivery) {
      $message .= ' 	
										<tr>
											<td align="right" style="font-size:10pt;vertical-align:bottom">Delivery Charge</td>
											<td align="right">' . $currencySymbol . ' ' . $order->order_details->breakup->delivery_charges_amount . '</td>
										</tr>';
    }

    if ($order->order_details->breakup->tip_amount > 0) {
      $message .= ' 	
										<tr>
											<td align="right" style="font-size:10pt;vertical-align:bottom">TIP</td>
											<td align="right">' . $currencySymbol . ' ' . $order->order_details->breakup->tip_amount . '</td>
										</tr>';
    }

    $message .= '		
										<tr>
											<td align="right" style="font-size:10pt;vertical-align:bottom">TAX</td>
											<td align="right">' . $currencySymbol . ' ' . $order->order_details->breakup->tax_amount . '</td>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>
						<tr>
							<td style="padding-top:30px;font-size:12pt">';

    if ($order->order_details->payment_mode == \Drupal\food\Core\Restaurant\PaymentMode::CashOnDelivery) {
    }
    else {
      $message .= '
								<div style="padding:6px 0 2px;border-top:1px solid black">I agree to pay the total amount.</div>';
    }

    $message .= '
							</td>
							<td style="padding:4px 0;vertical-align:top">
								<table width="100%" border="0" cellpadding="0" cellspacing="0">
									<tbody>
										<tr>
											<td align="right" style="font-size:10pt">TOTAL</td>
											<td align="right" width="125" style="font-size:14pt"><b>' . $currencySymbol . ' ' . $order->order_details->breakup->net_amount . '</b></td>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>
					</tbody>
				</table>
				<table width="100%" border="0" cellpadding="0" cellspacing="0" style="border-top:1px dashed black;border-bottom:1px dashed black">
					<tbody>
						<tr>
							<td style="padding:10px 15px 10px 0;font-size:12pt;border-right:1px dashed black">
								<div style="font-size:14pt">To cancel this order</div>
								<div style="padding:5px 0 5px">Call at: <b style="font-size:16pt"><a href="tel:' . $restaurant->phone_number . '" value="' . $restaurant->phone_number . '" target="_blank">' . $restaurant->phone_number . '</a></b></div>
							</td>
						</tr>
					</tbody>
				</table>
			
			
			<div style="margin-top:10px"></div>';

    if (!$isFax) {
      /*$message.= '
  <div style="margin-bottom:10px;padding:17px 0;font-size:14pt;text-align:center">
    <a href="' . $orderConfirmationUrl . '" style="text-decoration:none;color:#000" target="_blank"><b>Click Here to confirm this order</b></a>
  </div>';*/

      $cut_from_here_line = "";
    }
    else {
      $cut_from_here_line = '<div><hr style="border:1px dashed black">Cut from here</div>';
    }

    $message .= '' . $cut_from_here_line . '';
    $message .= '
				<table width="80%" border="0" cellpadding="0" cellspacing="0" style="font-size:12pt;border-bottom:2px solid black">
					<tbody>
						<tr>
							<td width="33%" style="padding:10px 0">
								<div>
									<div>' . $order->user_name . '</div>
									<div style="font-size:14pt"><b><a href="tel:' . $order->user_phone . '" value="" target="_blank">' . $order->user_phone . '</a></b></div>';

    if ($order->order_details->delivery_mode == \Drupal\food\Core\Restaurant\DeliveryMode::Delivery) {
      $message .= '
									<div>' . $order->user_apartment_number . ' ' . $order->user_address . '</div>';
    }

    $message .= '
								</div>
							</td>
							<td>
								<div>
									<div>Order amount : ' . $currencySymbol . ' ' . $order->order_details->breakup->net_amount . '</div>';

    if ($order->order_details->breakup->tip_amount > 0) {
      $message .= '
									<div>Tip amount : ' . $currencySymbol . ' ' . $order->order_details->breakup->tip_amount . '</div>';
    }

    $message .= '
								</div>
							</td>
						</tr>
					</tbody>
				</table> 
					<div style="width: 100%; margin: 0 auto; padding: 5px 13px; background: #000000;     color: #fff;">Find us on:
		<a href="https://play.google.com/store/apps/details?id=com.foodondeal.app.android&hl=en">	<img src="https://www.foodondeal.com/images/android.png" border="0" width="20%" style="vertical-align: middle; display: inline-flex; max-width: 125px;" class="CToWUd" /></a>
		<a href="https://itunes.apple.com/us/app/food-on-deal/id1205075361?mt=8">	<img src="https://www.foodondeal.com/images/iphone.png" border="0" width="20%" style="vertical-align: middle; display: inline-flex; max-width: 125px;" class="CToWUd" /></a>
			</div>
			</div>
		</div>';
    $message .= "</body></html>";

    return ($message);
  }

  public static function buildOrderConfirmationBody($order) {
    $platform_settings = \Drupal\food\Core\PlatformController::getPlatformSettings();
    $order_number_prefix = PhpHelper::getNestedValue($platform_settings,
      ['order_settings', 'order_number_prefix']);

    $restaurant = \Drupal\food\Core\RestaurantController::getRestaurantById($order->restaurant_id);
    //\Drupal\food\Core\UserController::validateCurrentUserOrAdmin($order->user_id);
    $siteUrl = Url::fromRoute('<front>')->setAbsolute()->toString();

    $message = '
<html>
	<body>
	<div style="background-color: #e1e4e4; width: 100%">
		<div style="width: 50%; margin: 0 auto; padding: 5px 13px; background: #5cb439; height: 60px;">
		<div style="float:left">	<img src="https://www.foodondeal.com/images/logo.png" border="0" width="20%" style="vertical-align: middle; display: inline-flex; height: auto; width: 35%;" class="CToWUd" /></div>
		  <div style="float:right; color:#fff">  <h2 style="padding: 5px 7px; color: #fff; float: right; margin-top: 10px; border-radius: 4px; font-family:HelveticaNeue,"Helvetica Neue",Helvetica,Arial,"Lucida Grande",sans-serif;">Dear ' . $order->user_name . ',</h2></div>
		</div>
			<div style="text-align: center">
		
			<p>Your Order No. ' . $order_number_prefix . '# ' . $order->order_id . ' is being cooked and will be delivered to you in scheduled time. </p>
			<p>Please share your experience as it will help us to give you even better service next time!</p>
			<p style="margin-top:20px"><a href="' . $siteUrl . '" style="background-color: #5cb439; border: 6px solid #5bb238; border-radius: 4px; color:#fff" >Review and Rating</a></p>
			<p style="font:12px/normal HelveticaNeue,"Helvetica Neue",Helvetica,Arial,"Lucida Grande",sans-serif; color:#000; display:-webkit-inline-box">If you have any questions or concerns, call our</p>
		<p style="font:12px/normal HelveticaNeue,"Helvetica Neue",Helvetica,Arial,"Lucida Grande",sans-serif; color:#000; display:-webkit-inline-box">	Customer Service team at (888) 518-1475</p>
		<p style="font:12px/normal HelveticaNeue,"Helvetica Neue",Helvetica,Arial,"Lucida Grande",sans-serif; color:#000; display:-webkit-inline-box">	or talk with restaurant at ' . $restaurant->phone_number . '.</p>
	<p>	<a href="' . $siteUrl . '">	<img src="https://www.foodondeal.com/themes/food_theme2/images/app-order.png" width="450px" /></a></p>
			Thanks
			<p style="margin-top:20px"><a href="' . $siteUrl . '" style="background-color: #5cb439; border: 6px solid #5bb238; border-radius: 4px; color:#fff" >' . $platform_settings->derived_settings->site_name . ' team</a><br></p>
			</div>
		<div style="width: 50%; margin: 0 auto; padding: 5px 13px; background: #000000;     color: #fff;">Find us on:
		<a href="https://play.google.com/store/apps/details?id=com.foodondeal.app.android&hl=en">	<img src="https://www.foodondeal.com/images/android.png" border="0" width="20%" style="vertical-align: middle; display: inline-flex; max-width: 125px;" class="CToWUd" /></a>
		<a href="https://itunes.apple.com/us/app/food-on-deal/id1205075361?mt=8">	<img src="https://www.foodondeal.com/images/iphone.png" border="0" width="20%" style="vertical-align: middle; display: inline-flex; max-width: 125px;" class="CToWUd" /></a>
			</div>
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

    $message = '
<html>
	<body>
		<div style="background-color: #e1e4e4; width: 100%">
		<div style="width: 50%; margin: 0 auto; padding: 5px 13px; background: #5cb439; height: 60px;">
		<div style="float:left">	<img src="https://www.foodondeal.com/images/logo.png" border="0" width="20%" style="vertical-align: middle; display: inline-flex; height: auto; width: 35%;" class="CToWUd" /></div>
		  <div style="float:right; color:#fff">  <h2 style="padding: 5px 7px; color: #fff; float: right; margin-top: 10px; border-radius: 4px; font-family:HelveticaNeue,"Helvetica Neue",Helvetica,Arial,"Lucida Grande",sans-serif;">Dear ' . $order->user_name . ',</h2></div>
		</div>
			
				<div style="text-align: center">
				
			
			<p>We apologize that <strong style="color:#375623 !important; font:14px HelveticaNeue,"Helvetica Neue",Helvetica,Arial,"Lucida Grande",sans-serif;">' . $restaurant->name . '</strong></p>
			<p>is unable to process your order <strong style="color:#375623 !important; font:14px HelveticaNeue,"Helvetica Neue",Helvetica,Arial,"Lucida Grande",sans-serif;">' . $order_number_prefix . '# ' . $order->order_id . ' right now.</p>
			
				<p style="margin-top:20px">You may 
				<a href="' . $siteUrl . '">choose another Restaurant</a> </p>
		    <p>OR</p>
			<p><a href="' . $siteUrl . '" >click here</a> to get a call from our service team or.</p>
		    <p>	dial (888) 518-1475 now for more option</p>
			<p style="font:12px/normal HelveticaNeue,"Helvetica Neue",Helvetica,Arial,"Lucida Grande",sans-serif; color:#000; display:-webkit-inline-box">Thank you</p>
			<p style="margin-top:20px"><a href="' . $siteUrl . '" style="background-color: #5cb439; border: 6px solid #5bb238; border-radius: 4px; color:#fff" >' . $platform_settings->derived_settings->site_name . ' team</a><br></p>
			</div>
			<div style="width: 50%; margin: 0 auto; padding: 5px 13px; background: #000000;     color: #fff;">Find us on:
		<a href="https://play.google.com/store/apps/details?id=com.foodondeal.app.android&hl=en">	<img src="https://www.foodondeal.com/images/android.png" border="0" width="20%" style="vertical-align: middle; display: inline-flex; max-width: 125px;" class="CToWUd" /></a>
		<a href="https://itunes.apple.com/us/app/food-on-deal/id1205075361?mt=8">	<img src="https://www.foodondeal.com/images/iphone.png" border="0" width="20%" style="vertical-align: middle; display: inline-flex; max-width: 125px;" class="CToWUd" /></a>
			</div>
		</div>
	</body>
</html>';

    return ($message);
  }

  /*
   * Prepare the mail body for adjustment added by Admin user.
  */
  public static function buildAdjustmentNotificationBody($adjustment) {
    $message = '<html>
						<body>
							<div style="font-family:Arial">
								<table width="100%" border="0" cellpadding="0" cellspacing="0" style="padding-bottom:10px;border-bottom:1px dashed black">
									<tbody>
										<tr>
											<td align="left">Transaction date</td>
											<td align="left">' . $adjustment['transaction'] . '</td>
										</tr>
										<tr>
											<td align="left">Amount</td>
											<td align="left">' . $adjustment['amount'] . '</td>
										</tr>
										<tr>
											<td align="left">Description</td>
											<td align="left">' . $adjustment['description'] . '</td>
										</tr>
									</tbody>
								</table>
							</div>
						</body>
					</html>';

    return ($message);
  }

}
