<?php
namespace Imbibe\Language;

//http://www.whitewashing.de/2009/08/31/enums-in-php.html
//http://stackoverflow.com/questions/254514/php-and-enumerations
//http://php.net/manual/en/class.splenum.php
abstract class EnumBase {
    private static $constCacheArray = NULL;

	private function __construct(){
		/*
		Preventing instance :)
		*/
     }

    private static function getConstants() {
        if (self::$constCacheArray == NULL) {
            self::$constCacheArray = array();
        }
		
        $calledClass = get_called_class();
        if (!array_key_exists($calledClass, self::$constCacheArray)) {
            $reflect = new \ReflectionClass($calledClass);
            self::$constCacheArray[$calledClass] = $reflect->getConstants();
        }
		
        return self::$constCacheArray[$calledClass];
    }

    public static function isValidName($name, $strict = false) {
        $constants = self::getConstants();

        if ($strict) {
            return array_key_exists($name, $constants);
        }

        $keys = array_map('strtolower', array_keys($constants));
        return in_array(strtolower($name), $keys);
    }

    public static function isValidValue($value) {
        $values = array_values(self::getConstants());
        return in_array($value, $values, $strict = true);
    }

    public static function getValueName($value) {
		$arr = self::getConstants();
        foreach($arr as $key => $keyValue) {
			if($keyValue == $value) {
				return ($key);
			}
		}
		
		throw new \Exception('Enum value not found:- ' . $value);
    }
}
