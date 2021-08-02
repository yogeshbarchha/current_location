<?php
namespace Imbibe\Util;

abstract class HttpUtil {
	public static function getAppUrl() {
		static $serverUrl;
		
		if(empty($serverUrl)) {
			$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
			$serverUrl = $protocol . $_SERVER['SERVER_NAME'];
			
			$port = $_SERVER['SERVER_PORT'];
			if(($protocol == "http://" && $port == 80) || ($protocol == "https://" && $port == 443)) {
				$port = '';
			} else {
				$port = ':' . $port;
			}
			
			$serverUrl = $serverUrl . $port . base_path();
		}
		
		return ($serverUrl);
	}

	public static function resolveAppUrl($relativeUrl) {
		$serverUrl = HttpUtil::getAppUrl();
		
		return ($serverUrl . $relativeUrl);
	}

	public static function getClientPath($path) {
		return (base_path() . $path);
	}
}
