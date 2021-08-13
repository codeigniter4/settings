<?php

if (! function_exists('setting')) {
    /**
     * Provides a convenience interface to the Settings service.
     *
     * @param string|null $field
     * @param mixed|null  $value
     *
     * @return mixed
     */
    function setting(string $field = null, $value = null)
    {
        $setting = service('settings');

        if (empty($field)) {
            return $setting;
        }

        // Parse the field name for class.field
        $parts = explode('.', $field);

        if (count($parts) === 1) {
            throw new \RuntimeException('$field must contain both the class and field name, i.e. Foo.bar');
        }

        [
            $class,
            $field,
        ] = $parts;

        // Getting the value?
        if ($value === null) {
            return $setting->get($class, $field);
        }

        // Setting the value
        return $setting->set($class, $field, $value);
    }
}
