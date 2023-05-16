<?php

use CodeIgniter\Settings\Settings;

if (! function_exists('setting')) {
    /**
     * Provides a convenience interface to the Settings service.
     *
     * @param mixed $value
     *
     * @return array|bool|float|int|object|Settings|string|void|null
     * @phpstan-return ($key is null ? Settings : ($value is null ? array|bool|float|int|object|string|null : void))
     */
    function setting(?string $key = null, $value = null)
    {
        /** @var Settings $setting */
        $setting = service('settings');

        if (empty($key)) {
            return $setting;
        }

        // Getting the value?
        if (count(func_get_args()) === 1) {
            return $setting->get($key);
        }

        // Setting the value
        $setting->set($key, $value);
    }
}
