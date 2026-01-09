<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRmAnswerTables extends Migration
{
    public function up()
    {
        // 1. RMI Smelter 主檔 (用於比對)
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'smelter_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'comment' => 'RMI Smelter ID',
            ],
            'smelter_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'comment' => '冶煉廠名稱',
            ],
            'metal_type' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'comment' => '金屬類型 (Tin, Tantalum, Tungsten, Gold, etc.)',
            ],
            'country' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'facility_type' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'comment' => '設施類型 (Smelter, Refiner, Processor)',
            ],
            'rmi_conformant' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'comment' => '是否符合 RMI 標準',
            ],
            'last_updated' => [
                'type' => 'DATE',
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

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['smelter_id', 'metal_type']);
        $this->forge->addKey('metal_type');
        $this->forge->createTable('rm_smelters');

        // 2. 問卷回答主表
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'assignment_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'comment' => '關聯至 rm_supplier_assignments',
            ],
            'template_type' => [
                'type' => 'ENUM',
                'constraint' => ['CMRT', 'EMRT', 'AMRT'],
                'comment' => '範本類型',
            ],
            'company_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'declaration_scope' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'comment' => '宣告範圍 (Company, Product, User defined)',
            ],
            'mineral_declaration' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => '各金屬的使用與回答狀況',
            ],
            'policy_answers' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => '公司政策相關回答',
            ],
            'excel_file_path' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => '原始上傳的 Excel 路徑',
            ],
            'validation_warnings' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => '驗證警告 (例如：包含未驗證噴廠)',
            ],
            'submitted_at' => [
                'type' => 'DATETIME',
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

        $this->forge->addKey('id', true);
        $this->forge->addKey('assignment_id');
        $this->forge->addUniqueKey(['assignment_id', 'template_type']);
        $this->forge->addForeignKey('assignment_id', 'rm_supplier_assignments', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('rm_answers');

        // 3. 冶煉廠清單回答
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'answer_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'metal_type' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'smelter_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
            ],
            'smelter_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'smelter_country' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'source_of_smelter_id' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'is_conformant_expected' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'rmi_smelter_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'comment' => '比對後關聯至 rm_smelters.id',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('answer_id');
        $this->forge->addKey('rmi_smelter_id');
        $this->forge->addForeignKey('answer_id', 'rm_answers', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('rm_answer_smelters');

        // 4. 礦場清單 (EMRT 專用)
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'answer_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'metal_type' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'mine_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'mine_country' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'mine_location' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('answer_id');
        $this->forge->addForeignKey('answer_id', 'rm_answers', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('rm_answer_mines');

        // 5. 審核紀錄 (RM 專用)
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'answer_id' => [
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
                'default' => 1,
            ],
            'action' => [
                'type' => 'ENUM',
                'constraint' => ['APPROVE', 'RETURN', 'COMMENT'],
            ],
            'comment' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('answer_id');
        $this->forge->addForeignKey('answer_id', 'rm_answers', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('rm_review_logs');
    }

    public function down()
    {
        $this->forge->dropTable('rm_review_logs');
        $this->forge->dropTable('rm_answer_mines');
        $this->forge->dropTable('rm_answer_smelters');
        $this->forge->dropTable('rm_answers');
        $this->forge->dropTable('rm_smelters');
    }
}
