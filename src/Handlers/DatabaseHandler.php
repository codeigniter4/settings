<?php

namespace CodeIgniter\Settings\Handlers;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Exceptions\DatabaseException;
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

    /**
     * Stores the configured database table.
     */
    public function __construct()
    {
        $this->config  = config('Settings');
        $this->db      = db_connect($this->config->database['group']);
        $this->builder = $this->db->table($this->config->database['table']);

        $this->setupDeferredWrites($this->config->database['deferWrites'] ?? false);
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
     * @return mixed
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
        if ($this->deferWrites) {
            $this->markPending($class, $property, $value, $context);
        } else {
            $this->persist($class, $property, $value, $context);
        }

        // Update storage after persistence check
        $this->setStored($class, $property, $value, $context);
    }

    /**
     * Persists a single property to the database.
     *
     * @param mixed $value
     *
     * @throws RuntimeException For database failures
     */
    private function persist(string $class, string $property, $value, ?string $context): void
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

        if ($this->deferWrites) {
            $this->markPending($class, $property, null, $context, true);
        } else {
            $this->persistForget($class, $property, $context);
        }

        // Delete from local storage
        $this->forgetStored($class, $property, $context);
    }

    /**
     * Deletes a single property from the database.
     *
     * @throws RuntimeException For database failures
     */
    private function persistForget(string $class, string $property, ?string $context): void
    {
        $result = $this->builder
            ->where('class', $class)
            ->where('key', $property)
            ->where('context', $context)
            ->delete();

        if (! $result) {
            throw new RuntimeException($this->db->error()['message'] ?? 'Error writing to the database.');
        }
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

        if ($context === null) {
            $this->hydrated[] = null;

            $query = $this->builder->where('context', null);
        } else {
            $query = $this->builder->where('context', $context);

            // If general has not been hydrated we will do that at the same time
            if (! in_array(null, $this->hydrated, true)) {
                $this->hydrated[] = null;
                $query->orWhere('context', null);
            }

            $this->hydrated[] = $context;
        }

        if (is_bool($result = $query->get())) {
            throw new RuntimeException($this->db->error()['message'] ?? 'Error reading from database.');
        }

        foreach ($result->getResultObject() as $row) {
            $this->setStored($row->class, $row->key, $this->parseValue($row->value, $row->type), $row->context);
        }
    }

    /**
     * Persists all pending properties to the database.
     * Called automatically at the end of request via post_system
     * event when deferWrites is enabled.
     *
     * @return void
     */
    public function persistPendingProperties()
    {
        if ($this->pendingProperties === []) {
            return;
        }

        $time = Time::now()->format('Y-m-d H:i:s');

        // Separate deletes from upserts
        $deletes = [];
        $upserts = [];

        foreach ($this->pendingProperties as $info) {
            if ($info['delete']) {
                $deletes[] = $info;
            } else {
                $upserts[] = [
                    'class'      => $info['class'],
                    'key'        => $info['property'],
                    'value'      => $this->prepareValue($info['value']),
                    'type'       => gettype($info['value']),
                    'context'    => $info['context'],
                    'created_at' => $time,
                    'updated_at' => $time,
                ];
            }
        }

        try {
            $this->db->transStart();

            // Batch upsert all non-delete operations
            if ($upserts !== []) {
                $this->builder->upsertBatch($upserts);
            }

            // Batch delete all delete operations
            if ($deletes !== []) {
                $this->builder->groupStart();

                foreach ($deletes as $i => $info) {
                    if ($i > 0) {
                        $this->builder->orGroupStart();
                    } else {
                        $this->builder->groupStart();
                    }

                    $this->builder
                        ->where('class', $info['class'])
                        ->where('key', $info['property'])
                        ->where('context', $info['context']);

                    $this->builder->groupEnd();
                }

                $this->builder->groupEnd();

                $this->builder->delete();
            }

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                log_message('error', 'Failed to persist pending properties to database.');

                return;
            }

            $this->pendingProperties = [];
        } catch (DatabaseException $e) {
            log_message('error', 'Failed to persist pending properties: ' . $e->getMessage());
        }
    }
}
