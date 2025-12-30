<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddStageToQuestionReviews extends Migration
{
    public function up()
    {
        $this->forge->addColumn('question_reviews', [
            'stage' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 1,
                'after' => 'project_supplier_id',
            ],
        ]);

        // Update existing records to have stage = 1
        $this->db->query("UPDATE question_reviews SET stage = 1 WHERE stage IS NULL OR stage = 0");

        // Drop existing unique index if exists and recreate with stage
        $this->db->query("ALTER TABLE question_reviews DROP INDEX IF EXISTS project_supplier_question");

        // Create new unique index including stage
        $this->forge->addKey(['project_supplier_id', 'stage', 'question_id'], false, true, 'ps_stage_question_unique');

        // Add index for stage
        $this->forge->addKey(['stage'], false, false, 'idx_stage');
    }

    public function down()
    {
        $this->forge->dropColumn('question_reviews', 'stage');
    }
}
