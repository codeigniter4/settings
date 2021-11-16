<?php

namespace CodeIgniter\Settings\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddContextColumn extends Migration
{
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

    public function down()
    {
        $this->forge->dropColumn(config('Settings')->database['table'], 'context');
    }
}
