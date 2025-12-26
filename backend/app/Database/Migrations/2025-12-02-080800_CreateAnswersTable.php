<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAnswersTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'project_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'question_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'value' => [
                'type' => 'JSON',
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
        $this->forge->addKey('project_id');
        $this->forge->addUniqueKey(['project_id', 'question_id']);
        $this->forge->addForeignKey('project_id', 'projects', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('answers');
    }

    public function down()
    {
        $this->forge->dropTable('answers');
    }
}
