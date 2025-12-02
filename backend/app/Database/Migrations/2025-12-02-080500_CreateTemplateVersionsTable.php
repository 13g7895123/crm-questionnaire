<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTemplateVersionsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'template_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'version' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
            ],
            'questions' => [
                'type' => 'JSON',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('template_id');
        $this->forge->addUniqueKey(['template_id', 'version']);
        $this->forge->addForeignKey('template_id', 'templates', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('template_versions');
    }

    public function down()
    {
        $this->forge->dropTable('template_versions');
    }
}
