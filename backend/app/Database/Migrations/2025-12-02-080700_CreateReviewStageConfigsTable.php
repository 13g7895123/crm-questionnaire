<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateReviewStageConfigsTable extends Migration
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
            'stage_order' => [
                'type' => 'INT',
                'constraint' => 2,
            ],
            'department_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'approver_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('project_id');
        $this->forge->addKey('department_id');
        $this->forge->addUniqueKey(['project_id', 'stage_order']);
        $this->forge->addForeignKey('project_id', 'projects', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('department_id', 'departments', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('approver_id', 'users', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('review_stage_configs');
    }

    public function down()
    {
        $this->forge->dropTable('review_stage_configs');
    }
}
