<?php

namespace CodeIgniter\Settings\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Publisher\Publisher;
use Throwable;

/**
 * Publish Settings config file into the current application.
 */
class SettingsPublisher extends BaseCommand
{
    /**
     * The group the command is lumped under
     * when listing commands.
     *
     * @var string
     */
    protected $group = 'Settings';

    /**
     * The Command's name
     *
     * @var string
     */
    protected $name = 'settings:publish';

    /**
     * the Command's usage
     *
     * @var string
     */
    protected $usage = <<<'EOL'
        settings:publish [options]

          Examples:
            settings:publish -f
        EOL;

    /**
     * the Command's short description
     *
     * @var string
     */
    protected $description = 'Publish Settings config file into the current application.';

    /**
     * The Command's options
     *
     * @var array<string, string>
     */
    protected $options = [
        '-f' => 'Set to enable overwrites.',
    ];

    /**
     * @var bool `true` to enable overwrites file
     */
    private bool $overwrites = false;

    public function run(array $params): void
    {
        if (array_key_exists('f', $params)) {
            $this->overwrites = true;
        }

        // Use the Autoloader to figure out the module path
        $source    = service('autoloader')->getNamespace('CodeIgniter\\Settings')[0];
        $publisher = new Publisher($source, APPPATH);

        try {
            $publisher->addPath('Config/Settings.php')
                ->merge($this->overwrites);
        } catch (Throwable $e) {
            $this->showError($e);
        }

        // If publication succeeded then update file
        foreach ($publisher->getPublished() as $file) {
            // Replace data in file
            $contents = file_get_contents($file);
            $contents = str_replace('namespace CodeIgniter\\Settings\\Config', 'namespace Config', $contents);
            $contents = str_replace('use CodeIgniter\\Config\\BaseConfig', 'use CodeIgniter\\Settings\\Config\\Settings as SettingsConfig', $contents);
            $contents = str_replace('class Settings extends BaseConfig', 'class Settings extends SettingsConfig', $contents);
            file_put_contents($file, $contents);
            CLI::write(CLI::color('  Published! ', 'green') . "You can customize the configuration by editing the \"{$file}\" file.");
        }
    }
}
