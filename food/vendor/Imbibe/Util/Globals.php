<?php
namespace Imbibe\Util;

abstract class Globals {
	function generateRandomString($lenth = 10) {
		// makes a random alpha numeric string of a given lenth
		$chars = array_merge(range('A', 'Z'), range('a', 'z'), range(0, 9), array(
			'@',
			'%',
			'^',
			'*'));
			
		$out = '';
		for ($c = 0; $c < $lenth; $c++) {
			$out .= $chars[mt_rand(0, count($chars) - 1)];
		}
		
		return $out;
	}

	public static function generateConfirmationCode() {
		$confirmation_code = mt_rand(100000, 999999);
		return ($confirmation_code);
	}
}
