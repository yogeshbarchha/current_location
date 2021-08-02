<?php

namespace Drupal\food\Core\Restaurant;

class PlatformSettings {
	
	/**
     * @var double
     */
	public $platform_commission_pct;
	
	/**
     * @var double
     */
	public $card_fee_pct;
	
	/**
     * @var boolean
     */
	public $disable_platform_deals;
    
    /**
     * @var \Drupal\food\Core\Platform\PlatformDeal[]
     */
	public $deals;
	
}
