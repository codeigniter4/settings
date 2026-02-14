<?php

declare(strict_types=1);

namespace CodeIgniter\Settings\Database\Migrations;

use CodeIgniter\Database\Forge;
use CodeIgniter\Database\Migration;
use CodeIgniter\Settings\Config\Settings;

class AddContextColumn extends Migration
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
        $this->forge->addColumn($this->config->database['table'], [
            'context' => [
                'type'       => 'varchar',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'type',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn($this->config->database['table'], 'context');
    }
}
