<?php
namespace Imbibe\Json;

class JsonMapper extends \JsonMapper {
    public function createInstance(
        $class, $useParameter = false, $parameter = null
    ) {
		if($class == '\Imbibe\Collections\Dictionary') {
			return new \Imbibe\Collections\Dictionary();
		}
		
		return parent::createInstance($class, $useParameter, $parameter);
    }
}
