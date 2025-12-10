<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTemplateQuestionsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'subsection_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'comment' => 'FK to template_subsections.id',
            ],
            'question_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'comment' => 'Question identifier (q_001, q_002, etc)',
            ],
            'order' => [
                'type' => 'INT',
                'unsigned' => true,
                'comment' => 'Display order within subsection',
            ],
            'text' => [
                'type' => 'TEXT',
                'comment' => 'Question text',
            ],
            'type' => [
                'type' => 'ENUM',
                'constraint' => ['BOOLEAN', 'TEXT', 'NUMBER', 'RADIO', 'CHECKBOX', 'SELECT', 'DATE', 'FILE', 'TABLE'],
                'comment' => 'Question type',
            ],
            'required' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'comment' => 'Is question required',
            ],
            'config' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Question config (options, maxLength, etc)',
            ],
            'conditional_logic' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Conditional logic (showWhen, followUpQuestions)',
            ],
            'table_config' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Table configuration (columns, rows) for TABLE type',
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
        $this->forge->addKey(['subsection_id', 'question_id'], false, true); // UNIQUE KEY
        $this->forge->addKey('subsection_id');
        $this->forge->addForeignKey('subsection_id', 'template_subsections', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('template_questions');
    }

    public function down()
    {
        $this->forge->dropTable('template_questions');
    }
}
