<?php

namespace CodeIgniter\Settings\Handlers;

use CodeIgniter\Settings\Config\Settings;
use RuntimeException;

/**
 * Provides file-based persistence for Settings.
 * Uses ArrayHandler for storage to minimize file I/O operations.
 */
class FileHandler extends ArrayHandler
{
    /**
     * Array of class+context combinations that have been loaded from disk.
     * Format: ['ClassName::context', 'ClassName::null', ...]
     *
     * @var list<string>
     */
    private array $hydrated = [];

    /**
     * Base path where settings files are stored.
     */
    private string $path;

    private Settings $config;

    /**
     * Stores the configured file path and ensures it exists.
     */
    public function __construct()
    {
        $this->config = config('Settings');
        $this->path   = rtrim($this->config->file['path'] ?? WRITEPATH . 'settings', DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (! is_dir($this->path) && (! mkdir($this->path, 0755, true) && ! is_dir($this->path))) {
            throw new RuntimeException('Unable to create settings directory: ' . $this->path);
        }

        if (! is_writable($this->path)) {
            throw new RuntimeException('Settings directory is not writable: ' . $this->path);
        }
    }

    /**
     * Checks whether this handler has a value set.
     */
    public function has(string $class, string $property, ?string $context = null): bool
    {
        $this->hydrate($class, $context);

        return $this->hasStored($class, $property, $context);
    }

    /**
     * Attempt to retrieve a value from the file.
     * To boost performance, all values are read and stored
     * on the first call for each class+context, then retrieved from storage.
     *
     * @return mixed|null
     */
    public function get(string $class, string $property, ?string $context = null)
    {
        $this->hydrate($class, $context);

        return $this->getStored($class, $property, $context);
    }

    /**
     * Stores values into a file for later retrieval.
     *
     * @param mixed $value
     *
     * @return void
     *
     * @throws RuntimeException For file write failures
     */
    public function set(string $class, string $property, $value = null, ?string $context = null)
    {
        $this->hydrate($class, $context);

        // Update in-memory storage first
        $this->setStored($class, $property, $value, $context);

        // Persist to disk with file locking
        $this->persist($class, $context);
    }

    /**
     * Deletes the record from persistent storage, if found,
     * and from the local cache.
     *
     * @return void
     *
     * @throws RuntimeException For file write failures
     */
    public function forget(string $class, string $property, ?string $context = null)
    {
        $this->hydrate($class, $context);

        // Delete from local storage
        $this->forgetStored($class, $property, $context);

        // Persist to disk with file locking
        $this->persist($class, $context);
    }

    /**
     * Deletes all settings files from persistent storage
     * and clears the local cache.
     *
     * @return void
     *
     * @throws RuntimeException For file deletion failures
     */
    public function flush()
    {
        // Get all settings files
        $files = glob($this->path . '*.php', GLOB_NOSORT);

        if ($files === false) {
            throw new RuntimeException('Unable to read settings directory: ' . $this->path);
        }

        // Delete all files
        foreach ($files as $file) {
            if (! unlink($file)) {
                throw new RuntimeException('Unable to delete settings file: ' . $file);
            }
        }

        // Clear local storage and hydration tracking
        parent::flush();
        $this->hydrated = [];
    }

    /**
     * Fetches values from files in bulk to minimize I/O operations.
     * Loads all properties for a specific class+context combination.
     *
     * @throws RuntimeException For file read failures
     */
    private function hydrate(string $class, ?string $context): void
    {
        $key = $this->getHydrationKey($class, $context);

        // Check if already loaded
        if (in_array($key, $this->hydrated, true)) {
            return;
        }

        // Load the specific class+context file
        $this->loadFromFile($class, $context);
        $this->hydrated[] = $key;

        // Also load general context for this class if not already loaded
        if ($context !== null) {
            $generalKey = $this->getHydrationKey($class, null);

            if (! in_array($generalKey, $this->hydrated, true)) {
                $this->loadFromFile($class, null);
                $this->hydrated[] = $generalKey;
            }
        }
    }

    /**
     * Loads settings from a file for a given class+context.
     *
     * @throws RuntimeException For file read failures
     */
    private function loadFromFile(string $class, ?string $context): void
    {
        $filePath = $this->getFilePath($class, $context);

        // If file doesn't exist, that's fine - no settings stored yet
        if (! file_exists($filePath)) {
            return;
        }

        // Use include to get the data array
        $data = include $filePath;

        if (! is_array($data)) {
            throw new RuntimeException('Settings file does not return an array: ' . $filePath);
        }

        // Load data into in-memory storage
        foreach ($data as $property => $valueData) {
            if (! is_array($valueData) || ! isset($valueData['value'], $valueData['type'])) {
                continue;
            }

            $this->setStored($class, $property, $this->parseValue($valueData['value'], $valueData['type']), $context);
        }
    }

    /**
     * Persists current in-memory settings for a class+context to disk.
     * Uses file locking to prevent race conditions during concurrent writes.
     *
     * @throws RuntimeException For file write failures
     */
    private function persist(string $class, ?string $context): void
    {
        $filePath = $this->getFilePath($class, $context);

        // Ensure directory exists (especially for context subdirectories)
        $directory = dirname($filePath);

        if (! is_dir($directory) && (! mkdir($directory, 0755, true) && ! is_dir($directory))) {
            throw new RuntimeException('Unable to create directory: ' . $directory);
        }

        // Open/create file for locking
        $lockHandle = fopen($filePath, 'c+b');

        if ($lockHandle === false) {
            throw new RuntimeException('Unable to open file for locking: ' . $filePath);
        }

        try {
            // Acquire exclusive lock
            if (! flock($lockHandle, LOCK_EX)) {
                throw new RuntimeException('Unable to acquire lock on file: ' . $filePath);
            }

            // Clear file stat cache to get current file size
            clearstatcache(true, $filePath);

            $currentData = [];

            if (filesize($filePath) > 0) {
                $currentData = include $filePath;

                if (! is_array($currentData)) {
                    $currentData = [];
                }
            }

            // Merge our changes with current state
            $ourData    = $this->extractDataForContext($class, $context);
            $mergedData = array_merge($currentData, $ourData);

            // Generate PHP file content
            $content = '<?php' . PHP_EOL . PHP_EOL;
            $content .= 'return ' . var_export($mergedData, true) . ';' . PHP_EOL;

            // Write file
            if (file_put_contents($filePath, $content) === false) {
                throw new RuntimeException('Unable to write settings file: ' . $filePath);
            }

            @chmod($filePath, 0644);
        } finally {
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
        }
    }

    /**
     * Extracts settings data for a specific class+context from in-memory storage.
     *
     * @return array<string,array>
     */
    private function extractDataForContext(string $class, ?string $context): array
    {
        $data       = [];
        $storedData = $this->getAllStored($class, $context);

        foreach ($storedData as $property => $valueData) {
            $data[$property] = [
                'value' => $valueData[0],
                'type'  => $valueData[1],
            ];
        }

        return $data;
    }

    /**
     * Generates a file path for a given class+context combination.
     *
     * Structure:
     * - Null context: writable/settings/Class_Name.php
     * - With context: writable/settings/{hash(context)}/Class_Name.php
     */
    private function getFilePath(string $class, ?string $context): string
    {
        $className = str_replace('\\', '_', $class);

        if ($context === null) {
            return $this->path . $className . '.php';
        }

        $contextHash = hash('xxh128', $context);

        return $this->path . $contextHash . DIRECTORY_SEPARATOR . $className . '.php';
    }

    /**
     * Generates a hydration key for a class+context combination.
     * Format: $class when context is null, $class::$context otherwise.
     */
    private function getHydrationKey(string $class, ?string $context): string
    {
        return $context === null ? $class : $class . '::' . $context;
    }
}
