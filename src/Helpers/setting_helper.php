<?php

if (! function_exists('setting')) {
    /**
     * Provides a convenience interface to the Settings service.
     *
     * @param mixed|null $value
     *
     * @return mixed
     */
    function setting(?string $key = null, $value = null)
    {
        $setting = service('settings');

        if (empty($key)) {
            return $setting;
        }

        // Getting the value?
        if (count(func_get_args()) === 1) {
            return $setting->get($key);
        }

        // Setting the value
        return $setting->set($key, $value);
    }
}
