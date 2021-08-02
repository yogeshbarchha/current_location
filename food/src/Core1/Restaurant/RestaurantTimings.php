<?php

namespace Drupal\food\Core\Restaurant;

class RestaurantTimings {
	
	/**
     * @var \Imbibe\Collections\Dictionary[\Drupal\food\Core\DateTime\TimeRange]
     */
	public $open_timings;

	/**
     * @var \Imbibe\Collections\Dictionary[\Drupal\food\Core\DateTime\TimeRange]
     */
	public $delivery_timings;
	
}
