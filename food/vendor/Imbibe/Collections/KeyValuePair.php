<?php

namespace Imbibe\Collections;

interface KeyValuePair {
	public function getKey();
	public function setKey($key);

	public function getValue();
	public function setValue($value);
}
