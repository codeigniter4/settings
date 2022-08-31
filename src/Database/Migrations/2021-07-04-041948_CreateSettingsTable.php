<?php

namespace CodeIgniter\Settings\Database\Migrations;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Database\Forge;
use CodeIgniter\Database\Migration;

class CreateSettingsTable extends Migration
{
    private BaseConfig $config;

    public function __construct(?Forge $forge = null)
    {
        $this->config  = $this->_getConfig();
        $this->DBGroup = (isset($this->config->database['group']) && $this->config->database['group']) ? $this->config->database['group'] : null;

        parent::__construct($forge);
    }

    public function up()
    {
        $this->forge->addField('id');
        $this->forge->addField([
            'class' => [
                'type'       => 'varchar',
                'constraint' => 255,
            ],
            'key' => [
                'type'       => 'varchar',
                'constraint' => 255,
            ],
            'value' => [
                'type' => 'text',
                'null' => true,
            ],
            'type' => [
                'type'       => 'varchar',
                'constraint' => 31,
                'default'    => 'string',
            ],
            'created_at' => [
                'type' => 'datetime',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'datetime',
                'null' => false,
            ],
        ]);
        $this->forge->createTable(config('Settings')->database['table'], true);
    }

    private function _getConfig()
    {
        return config('Settings');
    }

    public function down()
    {
        $this->forge->dropTable(config('Settings')->database['table']);
    }
}
