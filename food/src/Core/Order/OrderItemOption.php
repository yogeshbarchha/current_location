<?php

namespace Drupal\food\Core\Order;

class OrderItemOption {
	
	/**
     * @var int
     */
	public $category_id;
	
	/**
     * @var int
     */
	public $id;

	/**
     * @var string
     */
	public $category_name;

	/**
     * @var string
     */
	public $option_name;
	
	/**
     * @var double
     */
	public $price;	
	
}
