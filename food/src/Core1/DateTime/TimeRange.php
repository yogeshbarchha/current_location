<?php

namespace Drupal\food\Core\DateTime;

class TimeRange {
	
	/**
     * @var string
     */
	public $start_time;

	/**
     * @var string
     */
	public $end_time;
	
	function isCurrent($time = NULL) {
		if($time == NULL) {
			$time = time();
		}
		
		$timeSlot = "";
		$startTimeTime = strtotime($this->start_time . ":00", $time);
		$endTimeTime = strtotime($this->end_time . ":00", $time);
		$relativeDateTimeTime = strtotime(date('Y-m-d H:i', $time) . ":00", $time);
		$relativeTimeTime = strtotime(date('H:i', $time) . ":00", $time);
		$nextDayTime = strtotime('+1 day', $time);
		
		if ($startTimeTime == $endTimeTime) {
			$timeSlot = "everytime";
		} else if ($startTimeTime > $endTimeTime) {
			$timeSlot = "next_day";
			$restaurantEndTime = date('Y-m-d', $nextDayTime) . " " . $this->end_time . ":00";
		} else {
			$timeSlot = "same_day";
			$restaurantEndTime = date('Y-m-d H:i', $endTimeTime);
		}

		if ($timeSlot == 'everytime') {
			return (TRUE);
		} else if ($timeSlot == 'next_day') {
			if ($relativeTimeTime < strtotime("23:59:59", $time) && ($relativeDateTimeTime > strtotime(date('Y-m-d', $time) . " " . $this->start_time . ":00", $time))) {
				return (TRUE);
			} elseif ($relativeDateTimeTime > strtotime(date('Y-m-d', $time) . " " . $this->start_time . ":00", $time) || $relativeDateTimeTime < strtotime(date('Y-m-d', $time) . " " . $this->end_time . ":00", $time)) {
				return (TRUE);
			} else {
				return (FALSE);
			}
		} else if ($timeSlot == 'same_day' && ($relativeTimeTime < $endTimeTime && $relativeTimeTime > $startTimeTime)) {
			return (TRUE);
		} else {
			return (FALSE);
		}

		return (NULL);
	}
}
