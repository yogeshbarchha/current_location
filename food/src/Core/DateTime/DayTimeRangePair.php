<?php

namespace Drupal\food\Core\DateTime;

class DayTimeRangePair implements \Imbibe\Collections\KeyValuePair
{
	/**
     * @var string
     */
	public $day;

	/**
     * @var \Drupal\food\Core\DateTime\TimeRange
     */
	public $timeRange;

	public function getKey() {
		return ($this->day);
	}
	
	public function setKey($key) {
		$this->day = $key;
	}
	
	public function getValue() {
		return ($this->timeRange);
	}
	
	public function setValue($value) {
		$this->timeRange = $value;
	}
}
