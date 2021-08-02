<?php

namespace Imbibe\Util;

abstract class PhpHelper {

    public static function includeFile($fileName,
			$variablesArray = array()
    ) {
        extract($variablesArray);
        include($fileName);
    }

    public static function includeOnceFile($fileName,
			$variablesArray = array()
    ) {
        extract($variablesArray);
        include_once($fileName);
    }
	
    public static function requireFile($fileName,
			$variablesArray = array()
    ) {
        extract($variablesArray);
        require($fileName);
    }

    public static function requireFileWithOutputCapture($fileName,
			$variablesArray = array()
    ) {
		ob_start();
        extract($variablesArray);
        require($fileName);
		$output = ob_get_clean();
		
		return($output);
    }

    public static function requireOnceFile($fileName,
			$variablesArray = array()
    ) {
        extract($variablesArray);
        require_once $fileName;
    }

    public static function executeFile($fileName,
			$variablesArray = array()
    ) {
		ob_start();
        extract($variablesArray);
        require $fileName;
		return ob_get_clean();
    }
	
	public static function getArrayKeyValue(&$array, $key, $defaultValue = NULL) {
		return(isset($array[$key]) ? $array[$key] : $defaultValue);
	}
	
	public static function ensureArrayHasKey(&$array, $key, $defaultValue = NULL) {
		if(isset($array[$key])) {
			$value = $array[$key];
		} else {
			$value = $defaultValue;
			$array[$key] = $defaultValue;
		}
		
		return($value);
	}
	
	public static function getNestedValue($obj, $props, $defaultValue = NULL) {
		if(empty($obj)) {
			return ($defaultValue);
		}
		
		for($i = 0; $i < count($props); $i++) {
			$prop = $props[$i];
			
			if(is_a($obj, '\ArrayObject')) {
				$obj = $obj->offsetGet($prop);
			} elseif(is_array($obj)) {
				if(isset($obj[$prop])) {
					$obj = $obj[$prop];
				} else {
					return ($defaultValue);
				}
			} else {
				if(isset($obj->$prop)) {
					$obj = $obj->$prop;
				} else {
					return ($defaultValue);
				}
			}
		}
		
		return($obj);
	}
	
	public static function compareDeep($obj1, $obj2, $config = NULL) {
		$propertiesToIgnore = self::getNestedValue($config, ['propertiesToIgnore'], array());
		
		if(empty($obj1)) {
			if(empty($obj2)) {
				return (TRUE);
			} else {
				return (FALSE);
			}
		} else {
			if(empty($obj2)) {
				return (FALSE);
			}
		}
		
		if(is_scalar($obj1) && is_scalar($obj2)) {
			return($obj1 == $obj2);
		}
		
		if(is_object($obj1)) {
			$class1 = get_class ($obj1);
			$class2 = get_class ($obj2);
			
			if($class1 != $class2) {
				return (FALSE);
			}
		}
		
		foreach($obj1 as $key => $value1) {
			if(in_array($class1 . '::' . $key, $propertiesToIgnore) === TRUE) {
				continue;
			}
			
			if(is_array($obj1)) {
				$value2 = $obj2[$key];
			} else {
				$value2 = $obj2->$key;
			}
			
			if(is_array($value1) || is_object($value1)) {
				if (self::compareDeep($value1, $value2, $config) == FALSE) {
					return (FALSE);
				}
			} else {
				if($value1 != $value2) {
					//Uncomment to figure out why something is not merging correctly.
					//die($class1 . '::' . $key . '<br />' . json_encode($config) . '<br />' . json_encode($obj1) . '<br />' . json_encode($obj2));
					return (FALSE);
				}
			}
		}
		
		return (TRUE);
	}
}
