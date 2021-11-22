<?php

namespace CodeIgniter\Settings\Handlers;

use RuntimeException;

abstract class BaseHandler
{
    /**
     * Checks whether this handler has a value set.
     */
    abstract public function has(string $class, string $property, ?string $context = null): bool;

    /**
     * Returns a single value from the handler, if stored.
     *
     * @return mixed
     */
    abstract public function get(string $class, string $property, ?string $context = null);

    /**
     * If the Handler supports saving values, it
     * MUST override this method to provide that functionality.
     * Not all Handlers will support writing values.
     * Must throw RuntimeException for any failures.
     *
     * @param mixed $value
     *
     * @throws RuntimeException
     *
     * @return void
     */
    public function set(string $class, string $property, $value = null, ?string $context = null)
    {
        throw new RuntimeException('Set method not implemented for current Settings handler.');
    }

    /**
     * If the Handler supports forgetting values, it
     * MUST override this method to provide that functionality.
     * Not all Handlers will support writing values.
     * Must throw RuntimeException for any failures.
     *
     * @throws RuntimeException
     *
     * @return void
     */
    public function forget(string $class, string $property, ?string $context = null)
    {
        throw new RuntimeException('Forget method not implemented for current Settings handler.');
    }

    /**
     * Takes care of converting some item types so they can be safely
     * stored and re-hydrated into the config files.
     *
     * @param mixed $value
     *
     * @return mixed|string
     */
    protected function prepareValue($value)
    {
        if (is_bool($value)) {
            return (int) $value;
        }

        if (is_array($value) || is_object($value)) {
            return serialize($value);
        }

        return $value;
    }

    /**
     * Handles some special case conversions that
     * data might have been saved as, such as booleans
     * and serialized data.
     *
     * @param mixed $value
     *
     * @return bool|mixed
     */
    protected function parseValue($value, string $type)
    {
        // Serialized?
        if ($this->isSerialized($value)) {
            $value = unserialize($value);
        }

        settype($value, $type);

        return $value;
    }

    /**
     * Checks to see if an object is serialized and correctly formatted.
     *
     * Taken from Wordpress core functions.
     *
     * @param mixed $data
     * @param bool  $strict Whether to be strict about the end of the string.
     */
    protected function isSerialized($data, $strict = true): bool
    {
        // If it isn't a string, it isn't serialized.
        if (! is_string($data)) {
            return false;
        }
        $data = trim($data);
        if ('N;' === $data) {
            return true;
        }
        if (strlen($data) < 4) {
            return false;
        }
        if (':' !== $data[1]) {
            return false;
        }
        if ($strict) {
            $lastc = substr($data, -1);
            if (';' !== $lastc && '}' !== $lastc) {
                return false;
            }
        } else {
            $semicolon = strpos($data, ';');
            $brace     = strpos($data, '}');
            // Either ; or } must exist.
            if (false === $semicolon && false === $brace) {
                return false;
            }
            // But neither must be in the first X characters.
            if (false !== $semicolon && $semicolon < 3) {
                return false;
            }
            if (false !== $brace && $brace < 4) {
                return false;
            }
        }
        $token = $data[0];

        switch ($token) {
            case 's':
                if ($strict) {
                    if ('"' !== substr($data, -2, 1)) {
                        return false;
                    }
                } elseif (false === strpos($data, '"')) {
                    return false;
                }
                // Or else fall through.
                // no break
            case 'a':
            case 'O':
                return (bool) preg_match("/^{$token}:[0-9]+:/s", $data);

            case 'b':
            case 'i':
            case 'd':
                $end = $strict ? '$' : '';

                return (bool) preg_match("/^{$token}:[0-9.E+-]+;{$end}/", $data);
        }

        return false;
    }
}
