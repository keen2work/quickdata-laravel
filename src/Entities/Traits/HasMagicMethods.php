<?php

namespace EMedia\QuickData\Entities\Traits;

/*
|--------------------------------------------------------------------------
| Allow an object to store and retrieve any property using magic methods
|--------------------------------------------------------------------------
|
|
|
*/


trait HasMagicMethods
{

	private $attributes = [];

	public function __get($key)
	{
		if (array_key_exists($key, $this->attributes)) {
			return $this->attributes[$key];
		}

		return null;
	}

	public function __set($key, $value)
	{
		$this->attributes[$key] = $value;
	}

	public function __isset($key)
	{
		return isset($this->attributes[$key]);
	}

	/**
	 *
	 * Create an empty property if it doesn't exist
	 *
	 * @param        $propertyName
	 * @param string $defaultValue
	 */
	private function validateProperty($propertyName, $defaultValue = '')
	{
		if (!isset($this->attributes[$propertyName])) {
			$this->attributes[$propertyName] = $defaultValue;
		}
	}

}