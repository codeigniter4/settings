<?php

namespace CodeIgniter\Settings\Database\Migrations;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Database\Forge;
use CodeIgniter\Database\Migration;

class AddContextColumn extends Migration
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
        $this->forge->addColumn(config('Settings')->database['table'], [
            'context' => [
                'type'       => 'varchar',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'type',
            ],
        ]);
    }

    private function _getConfig()
    {
        return config('Settings');
    }

    public function down()
    {
        $this->forge->dropColumn(config('Settings')->database['table'], 'context');
    }
}
