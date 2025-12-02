<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProjectsTable extends Migration
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
            'year' => [
                'type' => 'INT',
                'constraint' => 4,
            ],
            'type' => [
                'type' => 'ENUM',
                'constraint' => ['SAQ', 'CONFLICT'],
            ],
            'template_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'template_version' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
            ],
            'supplier_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['DRAFT', 'IN_PROGRESS', 'SUBMITTED', 'REVIEWING', 'APPROVED', 'RETURNED'],
                'default' => 'IN_PROGRESS',
            ],
            'current_stage' => [
                'type' => 'INT',
                'constraint' => 2,
                'default' => 0,
            ],
            'submitted_at' => [
                'type' => 'DATETIME',
                'null' => true,
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
        $this->forge->addKey('template_id');
        $this->forge->addKey('supplier_id');
        $this->forge->addKey('status');
        $this->forge->addKey('type');
        $this->forge->addKey('year');
        $this->forge->addForeignKey('template_id', 'templates', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('supplier_id', 'organizations', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('projects');
    }

    public function down()
    {
        $this->forge->dropTable('projects');
    }
}
