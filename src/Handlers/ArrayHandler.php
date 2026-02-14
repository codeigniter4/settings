<?php

declare(strict_types=1);

namespace CodeIgniter\Settings\Handlers;

use CodeIgniter\Events\Events;

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
     * @var array<string, array<string, array{mixed, string}>>
     */
    private array $general = [];

    /**
     * Storage for context settings.
     * Format: ['context' => ['class' => ['property' => ['value', 'type']]]]
     *
     * @var array<string, array<string, array<string, array{mixed, string}>>>
     */
    private array $contexts = [];

    /**
     * Whether to defer writes until the end of request.
     * Used by handlers that support deferred writes.
     */
    protected bool $deferWrites = false;

    /**
     * Array of properties that have been modified but not persisted.
     * Used by handlers that support deferred writes.
     * Format: ['key' => ['class' => ..., 'property' => ..., 'value' => ..., 'context' => ..., 'delete' => ...]]
     *
     * @var array<string, array{class: string, property: string, value: mixed, context: string|null, delete: bool}>
     */
    protected array $pendingProperties = [];

    public function has(string $class, string $property, ?string $context = null): bool
    {
        return $this->hasStored($class, $property, $context);
    }

    public function get(string $class, string $property, ?string $context = null)
    {
        return $this->getStored($class, $property, $context);
    }

    public function set(string $class, string $property, $value = null, ?string $context = null): void
    {
        $this->setStored($class, $property, $value, $context);
    }

    public function forget(string $class, string $property, ?string $context = null): void
    {
        $this->forgetStored($class, $property, $context);
    }

    public function flush(): void
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
     * @return mixed
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

    /**
     * Marks a property as pending (needs to be persisted).
     * Used by handlers that support deferred writes.
     *
     * @param mixed $value
     */
    protected function markPending(string $class, string $property, $value, ?string $context, bool $isDelete = false): void
    {
        $key                           = $class . '::' . $property . ($context === null ? '' : '::' . $context);
        $this->pendingProperties[$key] = [
            'class'    => $class,
            'property' => $property,
            'value'    => $value,
            'context'  => $context,
            'delete'   => $isDelete,
        ];
    }

    /**
     * Groups pending properties by class+context combination.
     * Useful for handlers that need to persist changes on a per-class basis.
     * Format: ['key' => ['class' => ..., 'context' => ..., 'changes' => [...]]]
     *
     * @return array<string, array{class: string, context: string|null, changes: list<array{class: string, property: string, value: mixed, context: string|null, delete: bool}>}>
     */
    protected function getPendingPropertiesGrouped(): array
    {
        $grouped = [];

        foreach ($this->pendingProperties as $info) {
            $key = $info['class'] . ($info['context'] === null ? '' : '::' . $info['context']);

            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'class'   => $info['class'],
                    'context' => $info['context'],
                    'changes' => [],
                ];
            }

            $grouped[$key]['changes'][] = $info;
        }

        return $grouped;
    }

    /**
     * Sets up deferred writes for handlers that support it.
     *
     * @param bool $enabled Whether deferred writes should be enabled
     */
    protected function setupDeferredWrites(bool $enabled): void
    {
        $this->deferWrites = $enabled;

        if ($this->deferWrites) {
            Events::on('post_system', $this->persistPendingProperties(...));
        }
    }
}
