<?php
namespace Imbibe\Util;

abstract class TimeUtil {
	public static function now() {
		return (round(microtime(true) * 1000));
	}
}
