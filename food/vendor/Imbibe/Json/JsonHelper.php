<?php
namespace Imbibe\Json;

abstract class JsonHelper {
	public static function deserializeArray($json, $className) {
		if(empty($json)) {
			return (NULL);
		}
		
		$json = json_decode($json);
		$mapper = new JsonMapper();
		self::initializeMapper($mapper);
		
		$obj = $mapper->mapArray($json, array(), $className);
		
		return ($obj);
	}

	public static function deserializeDictionary($json, $className) {
		if(empty($json)) {
			return (NULL);
		}
		
		$json = json_decode($json);
		$mapper = new JsonMapper();
		self::initializeMapper($mapper);
		
		$obj = $mapper->mapArray($json, new \Imbibe\Collections\Dictionary(), $className);
		
		return ($obj);
	}

	public static function deserializeObject($json, $obj) {
		if(empty($json)) {
			return (NULL);
		}
		
		if(is_string($obj)) {
			//Its a class name, create object.
			$obj = new $obj();
		}
		
		$json = json_decode($json);
		$mapper = new JsonMapper();
		self::initializeMapper($mapper);
		
		$obj = $mapper->map($json, $obj);
		
		return ($obj);
	}
	
	private static function initializeMapper($mapper) {		
		//TODO: Need to revert the line below.
		$mapper->bStrictNullTypes = false;
		//TODO: Need to handle below more appropriately.
		$mapper->undefinedPropertyHandler = function($object, $propName, $jsonValue) use (&$mapper) {
                self::setUndefinedProperty($mapper, $object, $propName, $jsonValue);
            };
	}
	
	public static function setUndefinedProperty($mapper, $object, $propName, $jsonValue)	{
		//Ignore undefined properties.
		//$object->{'UNDEF' . $propName} = $jsonValue;
		if(method_exists($object, 'setUndefinedJsonProperty')) {
			$object->setUndefinedJsonProperty($mapper, $propName, $jsonValue);
		}
	}
}
