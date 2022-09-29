<?php

namespace CodeIgniter\Settings\Handlers;

use CodeIgniter\I18n\Time;
use Config\Cache;
use RuntimeException;

/**
 * Provides cache persistence for Settings.
 * Uses ArrayHandler for storage to minimize cache calls.
 */
class RedisHandler extends ArrayHandler
{
    /**
     * The Redis Handler for the Settings table.
     */
    private BaseRedisHandler $handler;

    /**
     * Array of contexts that have been stored.
     *
     * @var (string|null)[]
     */
    private array $hydrated = [];

    /**
     * Cache Config class
     */
    private Cache $config;

    public function __construct()
    {
        $this->config  = new Cache();
        $this->handler = new BaseRedisHandler($this->config);
        $this->handler->initialize();
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

        $result = $this->handler->save($this->prepareKey($class, $property, $context), [
            'class'      => $class,
            'key'        => $property,
            'value'      => $prepared,
            'type'       => $type,
            'context'    => $context,
            'updated_at' => $time,
        ]);

        if ($result !== true) {
            throw new RuntimeException('Error writing to the cache.');
        }

        // Update storage
        $this->setStored($class, $property, $value, $context);
    }

    /**
     * Deletes the record from persistent storage, if found,
     * and from the local cache.
     */
    public function forget(string $class, string $property, ?string $context = null): void
    {
        $this->hydrate($context);

        // Delete from the cache
        $this->handler->delete($this->prepareKey($class, $property, $context));

        // Delete from local storage
        $this->forgetStored($class, $property, $context);
    }

    /**
     * Fetches values from the database in bulk to minimize calls.
     * General (null) is always fetched once, contexts are fetched
     * in their entirety for each new request.
     */
    private function hydrate(?string $context): void
    {
        // Check for completion
        if (in_array($context, $this->hydrated, true)) {
            return;
        }

        if ($context === null) {
            $this->hydrated[] = null;
            $this->getHydrate('*--null');
        } else {
            $this->getHydrate('*--' . $context);
            // If general has not been hydrated we will do that at the same time
            if (! in_array(null, $this->hydrated, true)) {
                $this->hydrated[] = null;
                $this->getHydrate('*--null');
            }

            $this->hydrated[] = $context;
        }
    }

    /**
     * Prepare Cache keys
     */
    private function prepareKey(string $class, string $property, ?string $context): string
    {
        $replace = str_split($this->config->reservedCharacters, 1);
        if ($context) {
            $context = str_replace($replace, '|', $context);
        }
        $class = str_replace($replace, '|', $class);

        return ($context) ? $class . '.' . $property . '--' . $context : $class . '.' . $property . '--null';
    }

    /**
     * Search keys with pattern in Redis database
     */
    private function getHydrate(string $pattern): void
    {
        $iterator = null;

        while ($iterator !== 0) {
            // Scan for some keys
            $keys = $this->handler->getRedis()->scan($iterator, $pattern);

            // Redis may return empty results, so protect against that
            if ($keys !== false) {
                foreach ($keys as $key) {
                    [$class, $context] = explode('--', $key);
                    $value             = $this->handler->get($key);
                    $this->setStored($class, $context, $this->parseValue($value['value'], $value['type']), $context);
                }
            }
        }
    }
}
