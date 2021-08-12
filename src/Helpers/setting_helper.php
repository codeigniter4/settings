<?php

if (! function_exists('setting')) {
    /**
     * Provides a convenience interface to the
     * Bonfire/Settings/Settings class.
     *
     * @param string|null $class
     * @param string|null $field
     */
    function setting(string $class = null, string $field = null)
    {
        $setting = service('settings');

        if (empty($class) || empty($field)) {
            return $setting;
        }

        return $setting->get($class, $field);
    }
}
