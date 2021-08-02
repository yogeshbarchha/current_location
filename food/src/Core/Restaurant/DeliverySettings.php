<?php

namespace Drupal\food\Core\Restaurant;

class DeliverySettings {
	
	/**
     * @var boolean
     */
	public $enabled;
	
	/**
     * @var int
     */
	public $estimated_delivery_time_minutes;
	
	/**
     * @var double
     */
	public $minimum_order_amount;
	
	/**
     * @var double
     */
	public $delivery_charges_amount;
	
}
