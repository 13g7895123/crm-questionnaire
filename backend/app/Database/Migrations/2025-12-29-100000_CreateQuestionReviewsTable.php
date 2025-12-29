<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateQuestionReviewsTable extends Migration
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
            'project_supplier_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'question_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'approved' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'comment' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'reviewer_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
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

        $this->forge->addKey('id', true);
        $this->forge->addKey('project_supplier_id');
        $this->forge->addKey('reviewer_id');
        $this->forge->addUniqueKey(['project_supplier_id', 'question_id'], 'unique_ps_question');

        $this->forge->addForeignKey('project_supplier_id', 'project_suppliers', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('reviewer_id', 'users', 'id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('question_reviews');
    }

    public function down()
    {
        $this->forge->dropTable('question_reviews');
    }
}
