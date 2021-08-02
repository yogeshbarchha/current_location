<?php

namespace Drupal\food\Core\Platform;

class OrderSettings {
	
	/**
     * @var string
     */
	public $order_number_prefix;
	
	/**
     * @var boolean
     */
	public $disable_platform_deals;
    
    /**
     * @var \Drupal\food\Core\Platform\PlatformDeal[]
     */
	public $deals;
	
	/**
     * @var boolean
     */
	public $disable_condiments;
	
	/**
     * @var boolean
     */
	public $disable_tip;
	
	/**
     * @var boolean
     */
	public $disable_order_scheduling;

}
