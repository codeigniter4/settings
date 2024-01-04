<?php

namespace CodeIgniter\Settings\Handlers;

/**
 * Array Settings Handler
 *
 * Uses local storage to handle non-persistent
 * Settings requests. Useful mostly for testing
 * or extension by true persistent handlers.
 */
class ArrayHandler extends BaseHandler
{
    /**
     * Storage for general settings.
     * Format: ['class' => ['property' => ['value', 'type']]]
     *
     * @var array<string,array<string,array>>
     */
    private array $general = [];

    /**
     * Storage for context settings.
     * Format: ['context' => ['class' => ['property' => ['value', 'type']]]]
     *
     * @var array<string,array|null>
     */
    private array $contexts = [];

    public function has(string $class, string $property, ?string $context = null): bool
    {
        return $this->hasStored($class, $property, $context);
    }

    public function get(string $class, string $property, ?string $context = null)
    {
        return $this->getStored($class, $property, $context);
    }

    public function set(string $class, string $property, $value = null, ?string $context = null)
    {
        $this->setStored($class, $property, $value, $context);
    }

    public function forget(string $class, string $property, ?string $context = null)
    {
        $this->forgetStored($class, $property, $context);
    }

    public function flush()
    {
        $this->general  = [];
        $this->contexts = [];
    }

    /**
     * Checks whether this value is in storage.
     */
    protected function hasStored(string $class, string $property, ?string $context): bool
    {
        if ($context === null) {
            return isset($this->general[$class]) && array_key_exists($property, $this->general[$class]);
        }

        return isset($this->contexts[$context][$class]) && array_key_exists($property, $this->contexts[$context][$class]);
    }

    /**
     * Retrieves a value from storage.
     *
     * @return mixed|null
     */
    protected function getStored(string $class, string $property, ?string $context)
    {
        if (! $this->has($class, $property, $context)) {
            return null;
        }

        return $context === null
            ? $this->parseValue(...$this->general[$class][$property])
            : $this->parseValue(...$this->contexts[$context][$class][$property]);
    }

    /**
     * Adds values to storage.
     *
     * @param mixed $value
     */
    protected function setStored(string $class, string $property, $value, ?string $context): void
    {
        $type  = gettype($value);
        $value = $this->prepareValue($value);

        if ($context === null) {
            $this->general[$class][$property] = [
                $value,
                $type,
            ];
        } else {
            $this->contexts[$context][$class][$property] = [
                $value,
                $type,
            ];
        }
    }

    /**
     * Deletes an item from storage.
     */
    protected function forgetStored(string $class, string $property, ?string $context): void
    {
        if ($context === null) {
            unset($this->general[$class][$property]);
        } else {
            unset($this->contexts[$context][$class][$property]);
        }
    }
}
