<?php

namespace Drupal\food\Core;

abstract class CacheController {
	
	public static function getItem($key, $callback, $callbackArgs) {
		$hash = sha1($key, FALSE);

        $rows = db_select('cache_food', 'enc')
                ->fields('enc')
                ->condition('hash', $hash)
                ->execute();
		$data = NULL;
        while($row = $rows->fetchObject()) {
            if($row->key == $key) {
				$data = $row->data;
				break;
			}
        }
		
		if($data != NULL) {
			$decodedData = unserialize($data);
            $decodedData = json_decode($decodedData, TRUE);
			return($decodedData);
		}
		
		$data = call_user_func_array($callback, $callbackArgs);
		$encodedData = json_encode($data);		
		$encodedData = serialize($encodedData);
		
		db_insert('cache_food')
				->fields(array(
					'hash' => $hash,
					'`key`' => $key,
					'data' => $encodedData,
					'created_time' => \Imbibe\Util\TimeUtil::now(),
				))
				->execute();
		
		return($data);
	}
	
	public static function flushCache() {
        db_delete('cache_food')
                ->execute();
	}
	
    public static function getCachedData($callback, $callbackArgs) {
		$key = 'food-search-cache-' . json_encode($callbackArgs);
		
		$data = CacheController::getItem($key, $callback, $callbackArgs);
		
		return($data);
    }
}
