<?php

namespace CodeIgniter\Settings\Config;

use CodeIgniter\Config\BaseService;
use CodeIgniter\Settings\Config\Settings as SettingsConfig;
use CodeIgniter\Settings\Settings;

/**
 * Services Configuration file.
 *
 * Services are simply other classes/libraries that the system uses
 * to do its job. This is used by CodeIgniter to allow the core of the
 * framework to be swapped out easily without affecting the usage within
 * the rest of your application.
 *
 * This file holds any application-specific services, or service overrides
 * that you might need. An example has been included with the general
 * method format you should use for your service methods. For more examples,
 * see the core Services file at system/Config/Services.php.
 */
class Services extends BaseService
{
    /**
     * Returns the Settings manager class.
     */
    public static function settings(?SettingsConfig $config = null, bool $getShared = true): Settings
    {
        if ($getShared) {
            return static::getSharedInstance('settings', $config);
        }

        /** @var SettingsConfig $config */
        $config = $config ?? config('Settings');

        return new Settings($config);
    }
}
