<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * 修改 projects 表結構
 * 
 * 移除 supplier_id, status, current_stage, submitted_at 欄位
 * 這些欄位已移至 project_suppliers 表
 */
class ModifyProjectsTableRemoveSupplierFields extends Migration
{
    public function up()
    {
        // 移除外鍵約束（如果存在）
        try {
            $this->forge->dropForeignKey('projects', 'projects_supplier_id_foreign');
        } catch (\Exception $e) {
            // 外鍵可能不存在，忽略錯誤
        }

        // 移除欄位
        $this->forge->dropColumn('projects', ['supplier_id', 'status', 'current_stage', 'submitted_at']);
    }

    public function down()
    {
        // 重新加回欄位
        $this->forge->addColumn('projects', [
            'supplier_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'after' => 'template_version',
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['DRAFT', 'IN_PROGRESS', 'SUBMITTED', 'REVIEWING', 'APPROVED', 'RETURNED'],
                'default' => 'IN_PROGRESS',
                'after' => 'supplier_id',
            ],
            'current_stage' => [
                'type' => 'INT',
                'constraint' => 2,
                'default' => 0,
                'after' => 'status',
            ],
            'submitted_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'current_stage',
            ],
        ]);
    }
}
