<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ClearSettings extends BaseCommand
{
    protected $group       = 'Housekeeping';
    protected $name        = 'settings:clear';
    protected $description = 'Clears all settings from the database.';

    public function run(array $params)
    {
        if (! CLI::prompt('This will delete all settings from the database. Are you sure you want to continue?', ['y', 'n'], 'required') === 'y') {
            return;
        }

    }
}
