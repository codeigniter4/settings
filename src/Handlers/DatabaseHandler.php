<?php

namespace CodeIgniter\Settings\Handlers;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\I18n\Time;
use CodeIgniter\Settings\Config\Settings;
use RuntimeException;

/**
 * Provides database persistence for Settings.
 * Uses ArrayHandler for storage to minimize database calls.
 */
class DatabaseHandler extends ArrayHandler
{
    /**
     * The DB connection for the Settings.
     */
    private BaseConnection $db;

    /**
     * The Query Builder for the Settings table.
     */
    private BaseBuilder $builder;

    /**
     * Array of contexts that have been stored.
     *
     * @var list<null>|list<string>
     */
    private array $hydrated = [];

    private Settings $config;
    private string $key = 'ci_settings';

    /**
     * Index key to keep track of cached context keys so we can invalidate them all.
     */
    private string $indexKey = 'ci_settings_index';

    public function __construct()
    {
        $this->config  = config('Settings');
        $this->db      = db_connect($this->config->database['group']);
        $this->builder = $this->db->table($this->config->database['table']);
    }

    /**
     * Build cache key for a given context.
     */
    private function cacheKeyFor(?string $context): string
    {
        return $this->key . '_' . ($context === null ? 'general' : 'ctx_' . $context);
    }

    /**
     * Mark a context as cached (store in index).
     */
    private function markCachedContext(?string $context): void
    {
        $idx = cache()->get($this->indexKey) ?? [];

        $name = $context === null ? 'general' : 'ctx_' . $context;

        if (! in_array($name, $idx, true)) {
            $idx[] = $name;
            cache()->save($this->indexKey, $idx, DAY);
        }
    }

    /**
     * Clear all cached context keys tracked in index.
     */
    private function clearCacheAll(): void
    {
        $idx = cache()->get($this->indexKey) ?? [];

        foreach ($idx as $name) {
            cache()->delete($this->key . '_' . $name);
        }

        cache()->delete($this->indexKey);
    }

    /**
     * Checks whether this handler has a value set.
     */
    public function has(string $class, string $property, ?string $context = null): bool
    {
        $this->hydrate($context);

        return $this->hasStored($class, $property, $context);
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
        return $this->getStored($class, $property, $context);
    }

    /**
     * Stores values into the database for later retrieval.
     *
     * @param mixed $value
     *
     * @return void
     *
     * @throws RuntimeException For database failures
     */
    public function set(string $class, string $property, $value = null, ?string $context = null)
    {
        $time     = Time::now()->format('Y-m-d H:i:s');
        $type     = gettype($value);
        $prepared = $this->prepareValue($value);

        // If it was stored then we need to update
        if ($this->has($class, $property, $context)) {
            $result = $this->builder
                ->where('class', $class)
                ->where('key', $property)
                ->where('context', $context)
                ->update([
                    'value'      => $prepared,
                    'type'       => $type,
                    'context'    => $context,
                    'updated_at' => $time,
                ]);
            // ...otherwise insert it
        } else {
            $result = $this->builder
                ->insert([
                    'class'      => $class,
                    'key'        => $property,
                    'value'      => $prepared,
                    'type'       => $type,
                    'context'    => $context,
                    'created_at' => $time,
                    'updated_at' => $time,
                ]);
        }

        if ($result !== true) {
            throw new RuntimeException($this->db->error()['message'] ?? 'Error writing to the database.');
        }

        // Update storage
        $this->setStored($class, $property, $value, $context);

        // Invalidate cached contexts (we track index to remove all related cache keys)
        $this->clearCacheAll();
    }

    /**
     * Deletes the record from persistent storage, if found,
     * and from the local cache.
     *
     * @return void
     */
    public function forget(string $class, string $property, ?string $context = null)
    {
        $this->hydrate($context);

        // Delete from the database
        $result = $this->builder
            ->where('class', $class)
            ->where('key', $property)
            ->where('context', $context)
            ->delete();

        if (! $result) {
            throw new RuntimeException($this->db->error()['message'] ?? 'Error writing to the database.');
        }

        // Delete from local storage
        $this->forgetStored($class, $property, $context);

        // Invalidate cached contexts
        $this->clearCacheAll();
    }

    /**
     * Deletes all records from persistent storage, if found,
     * and from the local cache.
     *
     * @return void
     */
    public function flush()
    {
        $this->builder->truncate();

        parent::flush();

        // Invalidate cached contexts
        $this->clearCacheAll();
    }

    /**
     * Fetches values from the database in bulk to minimize calls.
     * General (null) is always fetched once, contexts are fetched
     * in their entirety for each new request.
     *
     * @throws RuntimeException For database failures
     */
    private function hydrate(?string $context): void
    {
        // Check for completion
        if (in_array($context, $this->hydrated, true)) {
            return;
        }

        // If requesting general context, fetch only general rows
        if ($context === null) {
            $cacheKey = $this->cacheKeyFor(null);

            if (! $result = cache()->get($cacheKey)) {
                $builder  = $this->db->table($this->config->database['table']);
                $dbResult = $builder->where('context', null)->get();

                if (is_bool($dbResult)) {
                    throw new RuntimeException($this->db->error()['message'] ?? 'Error reading from database.');
                }

                $result = $dbResult->getResultObject();
                cache()->save($cacheKey, $result, DAY);
                $this->markCachedContext(null);
            }

            foreach ($result as $row) {
                $this->setStored($row->class, $row->key, $this->parseValue($row->value, $row->type), $row->context);
            }

            $this->hydrated[] = null;

            return;
        }

        // For specific context: ensure general values are loaded first
        if (! in_array(null, $this->hydrated, true)) {
            $this->hydrate(null);
        }

        $cacheKeyCtx = $this->cacheKeyFor($context);

        if (! $result = cache()->get($cacheKeyCtx)) {
            $builder  = $this->db->table($this->config->database['table']);
            $dbResult = $builder->where('context', $context)->get();

            if (is_bool($dbResult)) {
                throw new RuntimeException($this->db->error()['message'] ?? 'Error reading from database.');
            }

            $result = $dbResult->getResultObject();
            cache()->save($cacheKeyCtx, $result, DAY);
            $this->markCachedContext($context);
        }

        foreach ($result as $row) {
            $this->setStored($row->class, $row->key, $this->parseValue($row->value, $row->type), $row->context);
        }

        $this->hydrated[] = $context;
    }
}
