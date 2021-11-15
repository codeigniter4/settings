<?php

namespace Sparks\Settings;

use InvalidArgumentException;
use RuntimeException;
use Sparks\Settings\Config\Settings as SettingsConfig;

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
    public function __construct(?SettingsConfig $config = null)
    {
        /** @var SettingsConfig $config */
        $config = $config ?? config('Settings');

        foreach ($config->handlers as $handler) {
            $class = $config->{$handler}['class'] ?? null;

            if ($class === null) {
                continue;
            }

            $this->handlers[$handler] = new $class();

            $writeable = $config->{$handler}['writeable'] ?? null;

            if ($writeable) {
                $this->writeHandler = $handler;
            }
        }
    }

    /**
     * Retrieve a value from any handler
     * or from a config file matching the name
     * file.arg.optionalArg
     */
    public function get(string $key, ?string $context = null)
    {
        [$class, $property, $config] = $this->prepareClassAndProperty($key);

        // Check each of our handlers
        foreach ($this->handlers as $name => $handler) {
            if ($handler->has($class, $property, $context)) {
                return $handler->get($class, $property, $context);
            }
        }

        // If no contextual value was found then fall back to general
        if ($context !== null) {
            return $this->get($key);
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
    public function set(string $key, $value = null, ?string $context = null)
    {
        [$class, $property] = $this->prepareClassAndProperty($key);

        return $this->getWriteHandler()->set($class, $property, $value, $context);
    }

    /**
     * Removes a setting from the persistent storage,
     * effectively returning the value to the default value
     * found in the config file, if any.
     */
    public function forget(string $key, ?string $context = null)
    {
        [$class, $property] = $this->prepareClassAndProperty($key);

        return $this->getWriteHandler()->forget($class, $property, $context);
    }

    /**
     * Returns the handler that is set to store values.
     *
     * @throws RuntimeException
     *
     * @return mixed
     */
    private function getWriteHandler()
    {
        if (empty($this->writeHandler) || ! isset($this->handlers[$this->writeHandler])) {
            throw new RuntimeException('Unable to find a Settings handler that can store values.');
        }

        return $this->handlers[$this->writeHandler];
    }

    /**
     * Analyzes the given key and breaks it into the class.field parts.
     *
     * @throws InvalidArgumentException
     *
     * @return string[]
     */
    private function parseDotSyntax(string $key): array
    {
        // Parse the field name for class.field
        $parts = explode('.', $key);

        if (count($parts) === 1) {
            throw new InvalidArgumentException('$key must contain both the class and field name, i.e. Foo.bar');
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
