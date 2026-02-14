<?php

declare(strict_types=1);

namespace CodeIgniter\Settings\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Settings\Config\Settings;

class ClearSettings extends BaseCommand
{
    protected $group       = 'Settings';
    protected $name        = 'settings:clear';
    protected $description = 'Clears all settings from persistent storage.';

    public function run(array $params): void
    {
        $config   = config('Settings');
        $handlers = $this->getHandlerNames($config);

        if ($handlers === null) {
            CLI::write('No handlers available to clear in the config file.');

            return;
        }

        if (CLI::prompt('This will delete all settings from ' . $handlers . '. Are you sure you want to continue?', ['y', 'n'], 'required') !== 'y') {
            return;
        }

        service('settings')->flush();

        CLI::write('Settings cleared from ' . $handlers . '.', 'green');
    }

    /**
     * Gets a human-readable list of handler names.
     */
    private function getHandlerNames(Settings $config): ?string
    {
        if ($config->handlers === []) {
            return null;
        }

        $handlerNames = [];

        foreach ($config->handlers as $handler) {
            // Get writeable handlers only (those that can be flushed)
            if (isset($config->{$handler}['writeable']) && $config->{$handler}['writeable'] === true) {
                $handlerNames[] = $handler;
            }
        }

        if ($handlerNames === []) {
            return null;
        }

        if (count($handlerNames) === 1) {
            return $handlerNames[0] . ' handler';
        }

        // Multiple handlers: "database and file"
        $last = array_pop($handlerNames);

        return implode(', ', $handlerNames) . ' and ' . $last . ' handlers';
    }
}
