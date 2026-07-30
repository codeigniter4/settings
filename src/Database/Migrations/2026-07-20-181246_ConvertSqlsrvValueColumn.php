<?php

declare(strict_types=1);

namespace CodeIgniter\Settings\Database\Migrations;

use CodeIgniter\Database\Forge;
use CodeIgniter\Database\Migration;
use CodeIgniter\Settings\Config\Settings;

class ConvertSqlsrvValueColumn extends Migration
{
    private readonly Settings $config;

    public function __construct(?Forge $forge = null)
    {
        $this->config  = config('Settings');
        $this->DBGroup = $this->config->database['group'] ?? null;

        parent::__construct($forge);
    }

    public function up(): void
    {
        if ($this->db->getPlatform() !== 'SQLSRV') {
            return;
        }

        $this->forge->modifyColumn($this->config->database['table'], [
            'value' => [
                'type'       => 'VARCHAR',
                'constraint' => 'MAX',
                'null'       => true,
            ],
        ]);
    }

    public function down(): void
    {
        if ($this->db->getPlatform() !== 'SQLSRV') {
            return;
        }

        $this->forge->modifyColumn($this->config->database['table'], [
            'value' => [
                'type' => 'TEXT',
                'null' => true,
            ],
        ]);
    }
}
