<?php

namespace Drupal\food\Core\Order;

use Imbibe\Util\PhpHelper;

class OrderItem {

	const NOTHANKSVALUE = -100;
	
	/**
     * @var int
     */
	public $restaurant_menu_item_id;

	/**
     * @var string
     */
	public $item_name;

	/**
     * @var double
     */
	public $item_price;

	/**
     * @var double
     */
	public $base_price;

	/**
     * @var double
     */
	public $unit_price;

	/**
     * @var int
     */
	public $quantity;

	
	/**
     * @var double
     */
	public $item_total_amount;	
	

	/**
     * @var \Drupal\food\Core\Order\OrderItemSize
     */
	public $size;

	
	/**
     * @var \Drupal\food\Core\Order\OrderItemOption[]
     */
	public $options;

	
	/**
     * @var string
     */
	public $instructions;
	
	
	public function updateItemTotals() {
		$restaurant_menu_item = \Drupal\food\Core\MenuController::getRestaurantMenuItem($this->restaurant_menu_item_id);

		$this->item_name = $restaurant_menu_item->name;
		$this->item_price = $restaurant_menu_item->price;
		$this->base_price = $restaurant_menu_item->price;
		$this->unit_price = $restaurant_menu_item->price;
		$this->item_total_amount = $this->quantity * $this->unit_price;
		
		if($this->size != NULL && $this->size->id != NULL) {
			$size = $restaurant_menu_item->variations->sizes[$this->size->id];

			$this->size = new \Drupal\food\Core\Order\OrderItemSize();
			$this->size->name = $size->name;
			$this->size->price = $size->price;
			$this->base_price = $size->price;
			$this->unit_price = $size->price;
			$this->item_total_amount = $this->quantity * $this->unit_price;
		} else {
			$this->size = NULL;
		}
		
		$categories = PhpHelper::getNestedValue($restaurant_menu_item, ['variations', 'categories']);
		if($categories != NULL && $this->options != NULL) {
			foreach($this->options as $option) {
				$category = $categories[$option->category_id];				
				$categoryOption = $category->options[$option->id];
					
				$option->category_name = $category->name;
				$option->option_name = $categoryOption->name;
				
				$price = $categoryOption->price;
				if($categoryOption->is_price_pct) {
					$price = round($this->base_price * $price / 100, 2, PHP_ROUND_HALF_UP);
				}
				
				$option->price = $price;
				$this->unit_price += $price;
				$this->item_total_amount = $this->quantity * $this->unit_price;
			}
		}
	}
}
