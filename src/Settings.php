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
        foreach (config('Settings')->handlers as $handler) {
            $class = config('Settings')->{$handler}['class'] ?? null;

            if ($class === null) {
                continue;
            }

            $this->handlers[$handler] = new $class();

            $writeable = config('Settings')->{$handler}['writeable'] ?? null;

            if ($writeable) {
                $this->writeHandler = $handler;
            }
        }
    }

    /**
     * Retrieve a value from either the database
     * or from a config file matching the name
     * file.arg.optionalArg
     */
    public function get(string $key)
    {
        [$class, $property, $config] = $this->prepareClassAndProperty($key);

        // Try grabbing the values from any of our handlers
        foreach ($this->handlers as $name => $handler) {
            $value = $handler->get($class, $property);

            if ($value !== null) {
                return $value;
            }
        }

        return $config->{$property} ?? null;
    }

    /**
     * Save a value to the writable handler for later retrieval.
     *
     * @param mixed $value
     *
     * @return void|null
     */
    public function set(string $key, $value = null)
    {
        [$class, $property] = $this->prepareClassAndProperty($key);

        $handler = $this->getWriteHandler();

        return $handler->set($class, $property, $value);
    }

    /**
     * Removes a setting from the persistent storage,
     * effectively returning the value to the default value
     * found in the config file, if any.
     */
    public function forget(string $key)
    {
        [$class, $property] = $this->prepareClassAndProperty($key);

        $handler = $this->getWriteHandler();

        return $handler->forget($class, $property);
    }

    /**
     * Returns the handler that is set to store values.
     *
     * @return mixed
     */
    private function getWriteHandler()
    {
        if (empty($this->writeHandler) || ! isset($this->handlers[$this->writeHandler])) {
            throw new \RuntimeException('Unable to find a Settings handler that can store values.');
        }

        return $this->handlers[$this->writeHandler];
    }

    /**
     * Analyzes the given key and breaks it into the class.field parts.
     *
     * @return string[]
     */
    private function parseDotSyntax(string $key): array
    {
        // Parse the field name for class.field
        $parts = explode('.', $key);

        if (count($parts) === 1) {
            throw new \RuntimeException('$field must contain both the class and field name, i.e. Foo.bar');
        }

        return $parts;
    }

    /**
     * Given a key in class.property syntax, will split the values
     * and determine the fully qualified class name, if possible.
     */
    private function prepareClassAndProperty(string $key): array
    {
        [$class, $property] = $this->parseDotSyntax($key);

        $config = config($class);

        // Use a fully qualified class name if the
        // config file was found.
        if ($config !== null) {
            $class = get_class($config);
        }

        return [$class, $property, $config];
    }
}
