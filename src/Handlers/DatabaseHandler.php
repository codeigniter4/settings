<?php

namespace Sparks\Settings\Handlers;

use CodeIgniter\I18n\Time;
use RuntimeException;

/**
 * Provides database storage for Settings.
 * Uses local storage to minimize database calls.
 */
class DatabaseHandler extends BaseHandler
{
    /**
     * The database table to use.
     *
     * @var string
     */
    private $table;

    /**
     * Storage for cached general settings.
     * Format: ['class' => ['property' => ['value', 'type']]]
     *
     * @var array<string,array<string,array>>|null Will be null until hydrated
     */
    private $general;

    /**
     * Storage for cached context settings.
     * Format: ['context' => ['class' => ['property' => ['value', 'type']]]]
     *
     * @var array<string,array|null>
     */
    private $contexts = [];

    /**
     * Stores the configured database table.
     */
    public function __construct()
    {
        $this->table = config('Settings')->database['table'] ?? 'settings';
    }

    /**
     * Checks whether this handler has a value set.
     */
    public function has(string $class, string $property, ?string $context = null): bool
    {
        $this->hydrate($context);

        if ($context === null) {
            return isset($this->general[$class])
                ? array_key_exists($property, $this->general[$class])
                : false;
        }

        return isset($this->contexts[$context][$class])
            ? array_key_exists($property, $this->contexts[$context][$class])
            : false;
    }

    /**
     * Attempt to retrieve a value from the database.
     * To boost performance, all of the values are
     * read and stored the first call for each contexts
     * and then retrieved from storage.
     *
     * @return mixed|null
     */
    public function get(string $class, string $property, ?string $context = null)
    {
        if (! $this->has($class, $property, $context)) {
            return null;
        }

        return $context === null
            ? $this->parseValue(...$this->general[$class][$property])
            : $this->parseValue(...$this->contexts[$context][$class][$property]);
    }

    /**
     * Stores values into the database for later retrieval.
     *
     * @param mixed $value
     *
     * @throws RuntimeException For database failures
     *
     * @return mixed|void
     */
    public function set(string $class, string $property, $value = null, ?string $context = null)
    {
        $time  = Time::now()->format('Y-m-d H:i:s');
        $type  = gettype($value);
        $value = $this->prepareValue($value);

        // If it was stored then we need to update
        if ($this->has($class, $property, $context)) {
            $result = db_connect()->table($this->table)
                ->where('class', $class)
                ->where('key', $property)
                ->update([
                    'value'      => $value,
                    'type'       => $type,
                    'context'    => $context,
                    'updated_at' => $time,
                ]);
        // ...otherwise insert it
        } else {
            $result = db_connect()->table($this->table)
                ->insert([
                    'class'      => $class,
                    'key'        => $property,
                    'value'      => $value,
                    'type'       => $type,
                    'context'    => $context,
                    'created_at' => $time,
                    'updated_at' => $time,
                ]);
        }

        // Update storage
        if ($result === true) {
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
        } else {
            throw new RuntimeException(db_connect()->error()['message'] ?? 'Error writing to the database.');
        }

        return $result;
    }

    /**
     * Deletes the record from persistent storage, if found,
     * and from the local cache.
     */
    public function forget(string $class, string $property, ?string $context = null)
    {
        $this->hydrate($context);

        // Delete from the database
        $result = db_connect()->table($this->table)
            ->where('class', $class)
            ->where('key', $property)
            ->where('context', $context)
            ->delete();

        if (! $result) {
            return $result;
        }

        // Delete from local storage
        if ($context === null) {
            unset($this->general[$class][$property]);
        } else {
            unset($this->contexts[$context][$class][$property]);
        }

        return $result;
    }

    /**
     * Fetches values from the database in bulk to minimize calls.
     * General is always fetched once, contexts are fetched in their
     * entirety for each new request.
     *
     * @throws RuntimeException For database failures
     */
    private function hydrate(?string $context)
    {
        if ($context === null) {
            // Check for completion
            if ($this->general !== null) {
                return;
            }

            $this->general = [];
            $query         = db_connect()->table($this->table)->where('context', null);
        } else {
            // Check for completion
            if (isset($this->contexts[$context])) {
                return;
            }

            $query = db_connect()->table($this->table)->where('context', $context);

            // If general has not been hydrated we will do that at the same time
            if ($this->general === null) {
                $this->general = [];
                $query->orWhere('context', null);
            }

            $this->contexts[$context] = [];
        }

        if (is_bool($result = $query->get())) {
            throw new RuntimeException(db_connect()->error()['message'] ?? 'Error reading from database.');
        }

        foreach ($result->getResultObject() as $row) {
            $tuple = [
                $row->value,
                $row->type,
            ];

            if ($row->context === null) {
                $this->general[$row->class][$row->key] = $tuple;
            } else {
                $this->contexts[$row->context][$row->class][$row->key] = $tuple;
            }
        }
    }
}
