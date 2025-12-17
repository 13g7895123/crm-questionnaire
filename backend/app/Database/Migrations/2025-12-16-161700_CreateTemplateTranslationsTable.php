<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTemplateTranslationsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'translatable_type' => [
                'type' => 'ENUM',
                'constraint' => ['section', 'subsection', 'question'],
                'comment' => 'Type of translatable entity',
            ],
            'translatable_id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'comment' => 'ID of the translatable entity',
            ],
            'locale' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
                'comment' => 'Locale code: en, zh-TW, zh-CN, ja, etc.',
            ],
            'field' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'comment' => 'Field name: title, text, description',
            ],
            'value' => [
                'type' => 'TEXT',
                'comment' => 'Translated value',
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
        $this->forge->addKey(['translatable_type', 'translatable_id', 'locale', 'field'], false, true, 'unique_translation');
        $this->forge->addKey(['translatable_type', 'translatable_id'], false, false, 'idx_translatable');
        $this->forge->addKey('locale', false, false, 'idx_locale');
        $this->forge->createTable('template_translations');
    }

    public function down()
    {
        $this->forge->dropTable('template_translations');
    }
}
