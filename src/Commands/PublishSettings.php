<?php

declare(strict_types=1);

/**
 * This file is part of CodeIgniter Queue.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace CodeIgniter\Settings\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Publisher\Publisher;
use Throwable;

class PublishSettings extends BaseCommand
{
    protected $group       = 'Settings';
    protected $name        = 'settings:publish';
    protected $description = 'Publish Settings config file into the current application.';

    public function run(array $params): void
    {
        $source = service('autoloader')->getNamespace('CodeIgniter\\Settings')[0];

        $publisher = new Publisher($source, APPPATH);

        try {
            $publisher->addPaths([
                'Config/Settings.php',
            ])->merge(false);
        } catch (Throwable $e) {
            $this->showError($e);

            return;
        }

        foreach ($publisher->getPublished() as $file) {
            $contents = file_get_contents($file);
            $contents = str_replace('namespace CodeIgniter\\Settings\\Config', 'namespace Config', $contents);
            $contents = str_replace('use CodeIgniter\\Config\\BaseConfig', 'use CodeIgniter\\Settings\\Config\\Settings as BaseSettings', $contents);
            $contents = str_replace('class Settings extends BaseConfig', 'class Settings extends BaseSettings', $contents);
            file_put_contents($file, $contents);
        }

        CLI::write(CLI::color('  Published! ', 'green') . 'You can customize the configuration by editing the "app/Config/Settings.php" file.');
    }
}
