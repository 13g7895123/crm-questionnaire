<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTemplatesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 200,
            ],
            'type' => [
                'type' => 'ENUM',
                'constraint' => ['SAQ', 'CONFLICT'],
            ],
            'latest_version' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('type');
        $this->forge->createTable('templates');
    }

    public function down()
    {
        $this->forge->dropTable('templates');
    }
}
