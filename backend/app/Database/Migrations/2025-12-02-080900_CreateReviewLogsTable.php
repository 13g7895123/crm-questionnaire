<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateReviewLogsTable extends Migration
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
            'reviewer_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
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
