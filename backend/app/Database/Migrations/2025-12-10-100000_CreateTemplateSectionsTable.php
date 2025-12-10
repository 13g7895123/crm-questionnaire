<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTemplateSectionsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'template_id' => [
                'type' => 'INT',
                'unsigned' => true,
            ],
            'section_id' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
                'comment' => 'Section identifier (A, B, C, D, E)',
            ],
            'order' => [
                'type' => 'INT',
                'unsigned' => true,
                'comment' => 'Display order of section',
            ],
            'title' => [
                'type' => 'VARCHAR',
                'constraint' => 200,
            ],
            'description' => [
                'type' => 'TEXT',
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
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['template_id', 'section_id'], false, true); // UNIQUE KEY
        $this->forge->addKey('template_id');
        $this->forge->addForeignKey('template_id', 'templates', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('template_sections');
    }

    public function down()
    {
        $this->forge->dropTable('template_sections');
    }
}
