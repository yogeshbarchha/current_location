<?php

namespace Drupal\food\Core\Cart;

class SearchParams {
	
	/**
     * @var string
     */
	public $user_address;
	
	/**
     * @var double
     */
	public $latitude;
	
	/**
     * @var double
     */
	public $longitude;
	
	/**
     * @var int
     */
	public $delivery_mode;
	
	/**
     * @var int[]
     */
	public $cuisine_ids;
	
	/**
     * @var int[]
     */
	public $service_area_ids;
	
	/**
     * @var int[]
     */
	public $dish_ids;
	
	/**
     * @var int
     */
	public $restaurant_open_status;
	
	/**
     * @var int
     */
	public $distance;
		
}
