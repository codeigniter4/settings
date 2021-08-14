<?php

namespace Sparks\Settings;

/**
 * Allows developers a single location to store and
 * retrieve settings that were original set in config files
 * in the core application or any third-party module.
 */

class Settings
{
	/**
	 * An array of Setting Stores that handle
	 * the actual act of getting/setting the values.
	 *
	 * @var array
	 */
	private $handlers = [];

	/**
	 * The name of the handler that handles writes.
	 *
	 * @var string
	 */
	private $writeHandler;

	/**
	 * Grabs instances of our handlers.
	 */
	public function __construct()
	{
		foreach (config('Settings')->handlers as $handler)
		{
			$class = config('Settings')->{$handler}['class'] ?? null;

			if ($class === null)
			{
				continue;
			}

			$this->handlers[$handler] = new $class();

			$writeable = config('Settings')->{$handler}['writeable'] ?? null;

			if ($writeable)
			{
				$this->writeHandler = $handler;
			}
		}
	}

	/**
	 * Retrieve a value from either the database
	 * or from a config file matching the name
	 * file.arg.optionalArg
	 *
	 * @param string $class
	 * @param string $key
	 */
	public function get(string $class, string $key)
	{
		$config = config($class);

		if ($config !== null)
		{
			$class = get_class($config);
		}

		// Try grabbing the values from any of our handlers
		foreach ($this->handlers as $name => $handler)
		{
			$value = $handler->get($class, $key);

			if ($value !== null)
			{
				return $value;
			}
		}

		return $config->{$key} ?? null;
	}

	/**
	 * Save a value to the writable handler for later retrieval.
	 *
	 * @param string $class
	 * @param string $key
	 * @param mixed  $value
	 *
	 * @return void|null
	 */
	public function set(string $class, string $key, $value = null)
	{
		$config = config($class);

		// Use a fully qualified class name if the
		// config file was found.
		if ($config !== null)
		{
			$class = get_class($config);
		}

		$handler = $this->getWriteHandler();

		return $handler->set($class, $key, $value);
	}

	/**
	 * Returns the handler that is set to store values.
	 *
	 * @return mixed
	 */
	private function getWriteHandler()
	{
		if (empty($this->writeHandler) || ! isset($this->handlers[$this->writeHandler]))
		{
			throw new \RuntimeException('Unable to find a Settings handler that can store values.');
		}

		return $this->handlers[$this->writeHandler];
	}
}
