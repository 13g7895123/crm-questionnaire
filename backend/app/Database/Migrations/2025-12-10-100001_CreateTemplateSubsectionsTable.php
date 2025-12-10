<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTemplateSubsectionsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'section_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'comment' => 'FK to template_sections.id',
            ],
            'subsection_id' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'comment' => 'Subsection identifier (A1, A2, B1, etc)',
            ],
            'order' => [
                'type' => 'INT',
                'unsigned' => true,
                'comment' => 'Display order within section',
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
        $this->forge->addKey(['section_id', 'subsection_id'], false, true); // UNIQUE KEY
        $this->forge->addKey('section_id');
        $this->forge->addForeignKey('section_id', 'template_sections', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('template_subsections');
    }

    public function down()
    {
        $this->forge->dropTable('template_subsections');
    }
}
