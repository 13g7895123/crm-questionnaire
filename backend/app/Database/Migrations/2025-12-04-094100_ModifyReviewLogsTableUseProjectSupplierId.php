<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * 修改 review_logs 表結構
 * 
 * 將 project_id 改為 project_supplier_id
 * 審核紀錄現在關聯到「專案-供應商」而非直接關聯專案
 */
class ModifyReviewLogsTableUseProjectSupplierId extends Migration
{
    public function up()
    {
        // 移除舊的外鍵約束
        try {
            $this->forge->dropForeignKey('review_logs', 'review_logs_project_id_foreign');
        } catch (\Exception $e) {
            // 外鍵可能不存在，忽略錯誤
        }

        // 重新命名欄位
        $this->forge->modifyColumn('review_logs', [
            'project_id' => [
                'name' => 'project_supplier_id',
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
        ]);
    }

    public function down()
    {
        // 還原欄位名稱
        $this->forge->modifyColumn('review_logs', [
            'project_supplier_id' => [
                'name' => 'project_id',
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
        ]);
    }
}
