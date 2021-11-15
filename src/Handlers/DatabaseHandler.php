<?php

namespace Sparks\Settings\Handlers;

use CodeIgniter\I18n\Time;
use RuntimeException;

/**
 * Provides database storage for Settings.
 */
class DatabaseHandler extends BaseHandler
{
    /**
     * Stores our cached settings retrieved
     * from the database on the first get() call
     * to reduce the number of database calls
     * at the expense of a little bit of memory.
     *
     * @var array
     */
    private $settings = [];

    /**
     * Have the settings been read and cached
     * from the database yet?
     *
     * @var bool
     */
    private $hydrated = false;

    /**
     * The settings table
     *
     * @var string
     */
    private $table;

    /**
     * Checks whether this handler has a value set.
     */
    public function has(string $class, string $property, ?string $context = null): bool
    {
        $this->hydrate();

        if (! isset($this->settings[$class][$property])) {
            return false;
        }

        return array_key_exists($context ?? 0, $this->settings[$class][$property]);
    }

    /**
     * Attempt to retrieve a value from the database.
     * To boost performance, all of the values are
     * read and stored in $this->settings the first
     * time, and then used from there the rest of the request.
     *
     * @return mixed|null
     */
    public function get(string $class, string $property, ?string $context = null)
    {
        if (! $this->has($class, $property, $context)) {
            return null;
        }

        return $this->parseValue(...$this->settings[$class][$property][$context ?? 0]);
    }

    /**
     * Stores values into the database for later retrieval.
     *
     * @param mixed $value
     *
     * @return mixed|void
     */
    public function set(string $class, string $property, $value = null, ?string $context = null)
    {
        $this->hydrate();
        $time  = Time::now()->format('Y-m-d H:i:s');
        $type  = gettype($value);
        $value = $this->prepareValue($value);

        // If we found it in our cache, then we need to update
        if (isset($this->settings[$class][$property][$context ?? 0])) {
            $result = db_connect()->table($this->table)
                ->where('class', $class)
                ->where('key', $property)
                ->update([
                    'value'      => $value,
                    'type'       => $type,
                    'context'    => $context,
                    'updated_at' => $time,
                ]);
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

        // Update our cache
        if ($result === true) {
            if (! array_key_exists($class, $this->settings)) {
                $this->settings[$class] = [];
            }
            if (! array_key_exists($property, $this->settings[$class])) {
                $this->settings[$class][$property] = [];
            }

            $this->settings[$class][$property][$context ?? 0] = [
                $value,
                $type,
            ];
        }

        return $result;
    }

    /**
     * Deletes the record from persistent storage, if found,
     * and from the local cache.
     */
    public function forget(string $class, string $property, ?string $context = null)
    {
        $this->hydrate();

        // Delete from persistent storage
        $result = db_connect()->table($this->table)
            ->where('class', $class)
            ->where('key', $property)
            ->where('context', $context)
            ->delete();

        if (! $result) {
            return $result;
        }

        // Delete from local storage
        unset($this->settings[$class][$property][$context ?? 0]);

        return $result;
    }

    /**
     * Ensures we've pulled all of the values from the database.
     *
     * @throws RuntimeException
     */
    private function hydrate()
    {
        if ($this->hydrated) {
            return;
        }

        $this->table = config('Settings')->database['table'] ?? 'settings';

        $rawValues = db_connect()->table($this->table)->get();

        if (is_bool($rawValues)) {
            throw new RuntimeException(db_connect()->error()['message'] ?? 'Error reading from database.');
        }

        $rawValues = $rawValues->getResultObject();

        foreach ($rawValues as $row) {
            if (! array_key_exists($row->class, $this->settings)) {
                $this->settings[$row->class] = [];
            }
            if (! array_key_exists($row->key, $this->settings[$row->class])) {
                $this->settings[$row->class][$row->key] = [];
            }

            $this->settings[$row->class][$row->key][$row->context ?? 0] = [
                $row->value,
                $row->type,
            ];
        }

        $this->hydrated = true;
    }
}
