<?php

namespace CodeIgniter\Settings;

use CodeIgniter\Settings\Config\Settings as SettingsConfig;
use CodeIgniter\Settings\Handlers\BaseHandler;
use InvalidArgumentException;
use RuntimeException;

/**
 * Allows developers a single location to store and
 * retrieve settings that were original set in config files
 * in the core application or any third-party module.
 */
class Settings
{
    /**
     * An array of handlers for getting/setting the values.
     *
     * @var BaseHandler[]
     */
    private array $handlers = [];

    /**
     * An array of the config options for each handler.
     *
     * @var array<string,array<string,mixed>>
     */
    private ?array $options = null;

    /**
     * Grabs instances of our handlers.
     */
    public function __construct(SettingsConfig $config)
    {
        foreach ($config->handlers as $handler) {
            $class = $config->{$handler}['class'] ?? null;

            if ($class === null) {
                continue;
            }

            $this->handlers[$handler] = new $class();
            $this->options[$handler]  = $config->{$handler};
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
        foreach ($this->handlers as $handler) {
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
     * @return void
     */
    public function set(string $key, $value = null, ?string $context = null)
    {
        [$class, $property] = $this->prepareClassAndProperty($key);

        foreach ($this->getWriteHandlers() as $handler) {
            $handler->set($class, $property, $value, $context);
        }
    }

    /**
     * Removes a setting from the persistent storage,
     * effectively returning the value to the default value
     * found in the config file, if any.
     *
     * @return void
     */
    public function forget(string $key, ?string $context = null)
    {
        [$class, $property] = $this->prepareClassAndProperty($key);

        foreach ($this->getWriteHandlers() as $handler) {
            $handler->forget($class, $property, $context);
        }
    }

    /**
     * Returns the handler that is set to store values.
     *
     * @return BaseHandler[]
     *
     * @throws RuntimeException
     */
    private function getWriteHandlers()
    {
        $handlers = [];

        foreach ($this->options as $handler => $options) {
            if (! empty($options['writeable'])) {
                $handlers[] = $this->handlers[$handler];
            }
        }

        if ($handlers === []) {
            throw new RuntimeException('Unable to find a Settings handler that can store values.');
        }

        return $handlers;
    }

    /**
     * Analyzes the given key and breaks it into the class.field parts.
     *
     * @return string[]
     *
     * @throws InvalidArgumentException
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
