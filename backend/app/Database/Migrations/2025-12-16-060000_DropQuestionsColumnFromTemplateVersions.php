<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Remove the deprecated 'questions' column from template_versions table.
 * In v2.0, template structure is stored in separate tables:
 * - template_sections
 * - template_subsections
 * - template_questions
 */
class DropQuestionsColumnFromTemplateVersions extends Migration
{
    public function up()
    {
        $this->forge->dropColumn('template_versions', 'questions');
    }

    public function down()
    {
        $this->forge->addColumn('template_versions', [
            'questions' => [
                'type' => 'JSON',
                'null' => true,
                'after' => 'version',
            ],
        ]);
    }
}
