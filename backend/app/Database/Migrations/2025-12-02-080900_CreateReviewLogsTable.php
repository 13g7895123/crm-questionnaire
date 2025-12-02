<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateReviewLogsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'project_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'reviewer_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'stage' => [
                'type' => 'INT',
                'constraint' => 2,
            ],
            'action' => [
                'type' => 'ENUM',
                'constraint' => ['APPROVE', 'RETURN'],
            ],
            'comment' => [
                'type' => 'TEXT',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('project_id');
        $this->forge->addKey('reviewer_id');
        $this->forge->addForeignKey('project_id', 'projects', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('reviewer_id', 'users', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('review_logs');
    }

    public function down()
    {
        $this->forge->dropTable('review_logs');
    }
}
