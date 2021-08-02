<?php

namespace Imbibe\Collections;

class Dictionary extends \ArrayObject {

	public function offsetSet($index, $newVal) {
		if ($newVal instanceof KeyValuePair) {
			$key = $newVal->getKey();
			if(empty($key) && !empty($index)) {
				$key = $index;
				$newVal->getKey($key);
			}
			
			parent::offsetSet($key, $newVal->getValue());
		} else {
			parent::offsetSet($index, $newVal);
		}
	}
	
	public function __call($func, $argv) {
		if (!is_callable($func) || substr($func, 0, 6) !== 'array_') {
			throw new BadMethodCallException(__CLASS__ . '->' . $func);
		}
		
		return call_user_func_array($func, array_merge(array($this->getArrayCopy()), $argv));
	}
	
}
