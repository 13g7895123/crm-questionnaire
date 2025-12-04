<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * 修改 answers 表結構
 * 
 * 將 project_id 改為 project_supplier_id
 * 答案現在關聯到「專案-供應商」而非直接關聯專案
 */
class ModifyAnswersTableUseProjectSupplierId extends Migration
{
    public function up()
    {
        // 移除舊的外鍵約束
        try {
            $this->forge->dropForeignKey('answers', 'answers_project_id_foreign');
        } catch (\Exception $e) {
            // 外鍵可能不存在，忽略錯誤
        }

        // 移除舊的唯一鍵
        try {
            $this->db->query('ALTER TABLE answers DROP INDEX project_id_question_id');
        } catch (\Exception $e) {
            // 索引可能不存在，忽略錯誤
        }

        // 重新命名欄位
        $this->forge->modifyColumn('answers', [
            'project_id' => [
                'name' => 'project_supplier_id',
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
        ]);

        // 新增新的唯一鍵
        $this->db->query('ALTER TABLE answers ADD UNIQUE KEY project_supplier_id_question_id (project_supplier_id, question_id)');
    }

    public function down()
    {
        // 移除新的唯一鍵
        try {
            $this->db->query('ALTER TABLE answers DROP INDEX project_supplier_id_question_id');
        } catch (\Exception $e) {
            // 索引可能不存在，忽略錯誤
        }

        // 還原欄位名稱
        $this->forge->modifyColumn('answers', [
            'project_supplier_id' => [
                'name' => 'project_id',
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
        ]);

        // 重新加回唯一鍵
        $this->db->query('ALTER TABLE answers ADD UNIQUE KEY project_id_question_id (project_id, question_id)');
    }
}
