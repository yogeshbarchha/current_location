<?php

namespace Drupal\food\Core\Order;

use Imbibe\Util\PhpHelper;


class Order {
	
	/**
     * @var int
     */
	public $restaurant_id;

	/**
     * @var int
     */
	public $delivery_mode;

	/**
     * @var int
     */
	public $payment_mode;

	
	/**
     * @var \Drupal\food\Core\Order\OrderBreakup
     */
	public $breakup;

	
	/**
     * @var \Drupal\food\Core\Order\OrderItem[]
     */
	public $items;


	/**
     * @var string
     */
	public $user_name;

	/**
     * @var string
     */
	public $user_phone;

	/**
     * @var int
     */
	public $user_address_id;

	/**
     * @var double
     */
	public $user_address_latitude;

	/**
     * @var double
     */
	public $user_address_longitude;

	/**
     * @var string
     */
	public $user_apartment_number;

	/**
     * @var string
     */
	public $user_address;

	/**
     * @var string
     */
	public $instructions;

	/**
     * @var string[]
     */
	public $condiments;

	/**
     * @var int
     */
	public $num_people;

	/**
     * @var string
     */
	public $schedule_date;

	/**
     * @var string
     */
	public $schedule_time;

	/**
     * @var string
     */
	public $cancel_comment;
	
	
	
	public function updateBreakup($config = array()) {
        $restaurant = \Drupal\food\Core\RestaurantController::getRestaurantById($this->restaurant_id);
        
		if(empty($this->breakup)) {
			$this->breakup = new \Drupal\food\Core\Order\OrderBreakup();
		}
		
		$breakup = $this->breakup;		
		$breakup->items_total_amount = 0;
		
		if(!empty($this->items)) {
			$updateItemTotals = PhpHelper::getNestedValue($config, ['updateItemTotals'], FALSE);
			foreach($this->items as $item) {
				if($updateItemTotals) {
					$item->updateItemTotals();
				}
				$breakup->items_total_amount += $item->item_total_amount;
			}
		}
		
        $breakup->tip_pct = PhpHelper::getNestedValue($breakup, ['tip_pct'], 0);
        $breakup->tip_amount = PhpHelper::getNestedValue($breakup, ['tip_amount'], 0);
        $breakup->restaurant_discount_pct = PhpHelper::getNestedValue($breakup, ['restaurant_discount_pct'], 0);
        $breakup->platform_discount_pct = PhpHelper::getNestedValue($breakup, ['platform_discount_pct'], 0);
                
        $breakup->effective_restaurant_discount_pct = $breakup->restaurant_discount_pct;
        $breakup->effective_platform_discount_pct = $breakup->platform_discount_pct;        
        if($breakup->effective_restaurant_discount_pct > $breakup->effective_platform_discount_pct) {
            $breakup->total_discount_pct = $breakup->effective_restaurant_discount_pct;
        } else {
            $breakup->total_discount_pct = $breakup->effective_platform_discount_pct;
        }        
        $breakup->effective_platform_discount_pct = $breakup->total_discount_pct - $breakup->effective_restaurant_discount_pct;
        if($breakup->effective_platform_discount_pct < 0) {
            $breakup->effective_platform_discount_pct = 0;
        }
        $breakup->platform_discount_amount = round($breakup->items_total_amount * $breakup->effective_platform_discount_pct / 100, 2, PHP_ROUND_HALF_UP);
        $breakup->restaurant_discount_amount = round($breakup->items_total_amount * $breakup->effective_restaurant_discount_pct / 100,2,PHP_ROUND_HALF_UP);
        $breakup->total_discount_amount = $breakup->platform_discount_amount + $breakup->restaurant_discount_amount;
        $breakup->net_amount = $breakup->items_total_amount - $breakup->total_discount_amount;
        
        $breakup->platform_commission_pct = PhpHelper::getNestedValue($restaurant, ['platform_settings', 'platform_commission_pct'], 0);
        $breakup->platform_commission_amount = round($breakup->net_amount * $breakup->platform_commission_pct / 100, 2, PHP_ROUND_HALF_UP);
        
        $breakup->tip_amount = round($breakup->net_amount * $breakup->tip_pct / 100, 2, PHP_ROUND_HALF_UP);
        $breakup->net_amount = $breakup->net_amount + $breakup->tip_amount;
        
        $breakup->delivery_charges_amount = 0;
		if($this->delivery_mode == \Drupal\food\Core\Restaurant\DeliveryMode::Delivery) {
			$breakup->delivery_charges_amount = PhpHelper::getNestedValue($restaurant, ['order_types', 'delivery_settings', 'delivery_charges_amount'], 0);
		}
        $breakup->net_amount = $breakup->net_amount + $breakup->delivery_charges_amount;
        
        $breakup->tax_pct = doubleval($restaurant->tax_pct);
        $breakup->tax_amount = round($breakup->net_amount * $breakup->tax_pct / 100, 2, PHP_ROUND_HALF_UP);
        $breakup->net_amount = $breakup->net_amount + $breakup->tax_amount;

		$breakup->payment_mode_processing_fee_amount = 0;
		if($this->payment_mode == \Drupal\food\Core\Restaurant\PaymentMode::Card) {
			$card_fee_pct = PhpHelper::getNestedValue($restaurant, ['platform_settings', 'card_fee_pct'], 0);
			$breakup->payment_mode_processing_fee_amount = round($breakup->net_amount * $card_fee_pct / 100, 2, PHP_ROUND_HALF_UP);
			if(!$breakup->payment_mode_processing_fee_amount) {
				$breakup->payment_mode_processing_fee_amount = 0;
			}
		}
        
        $breakup->restaurant_amount = $breakup->net_amount - $breakup->platform_commission_amount - $breakup->payment_mode_processing_fee_amount;
		$breakup->net_restaurant_amount = $breakup->restaurant_amount + $breakup->platform_discount_amount;
		$breakup->net_platform_amount = $breakup->platform_commission_amount + $breakup->payment_mode_processing_fee_amount - $breakup->platform_discount_amount;

		$breakup->net_amount_due_to_restaurant = ($this->payment_mode == \Drupal\food\Core\Restaurant\PaymentMode::CashOnDelivery) ? 0 : $breakup->net_restaurant_amount;
		$breakup->net_amount_due_to_platform = ($this->payment_mode == \Drupal\food\Core\Restaurant\PaymentMode::CashOnDelivery) ? $breakup->net_platform_amount : 0;
		$breakup->net_amount_due = ($this->payment_mode == \Drupal\food\Core\Restaurant\PaymentMode::CashOnDelivery) ? -1 * $breakup->net_platform_amount : $breakup->net_restaurant_amount;        
	}

}
