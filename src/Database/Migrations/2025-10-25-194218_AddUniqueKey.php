<?php

namespace CodeIgniter\Settings\Database\Migrations;

use CodeIgniter\Database\Forge;
use CodeIgniter\Database\Migration;
use CodeIgniter\Settings\Config\Settings;

class AddUniqueKey extends Migration
{
    private Settings $config;

    public function __construct(?Forge $forge = null)
    {
        $this->config  = config('Settings');
        $this->DBGroup = $this->config->database['group'] ?? null;

        parent::__construct($forge);
    }

    public function up()
    {
        $table = $this->config->database['table'];

        $this->forge->addUniqueKey(['class', 'key', 'context'], 'settings_class_key_context_idx');
        $this->forge->processIndexes($table);
    }

    public function down()
    {
        $table = $this->config->database['table'];

        $this->forge->dropKey($table, 'settings_class_key_context_idx', false);
    }
}
