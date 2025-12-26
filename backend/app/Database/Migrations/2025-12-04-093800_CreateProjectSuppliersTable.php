<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * 建立 project_suppliers 中間表
 * 
 * 此表用於管理專案與供應商的 1:N 關係
 * - 一個專案可以有多個供應商
 * - 每個供應商有獨立的填寫狀態、審核進度
 * - 答案和審核紀錄關聯到 project_supplier_id
 */
class CreateProjectSuppliersTable extends Migration
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
            'supplier_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['DRAFT', 'IN_PROGRESS', 'SUBMITTED', 'REVIEWING', 'APPROVED', 'RETURNED'],
                'default' => 'IN_PROGRESS',
            ],
            'current_stage' => [
                'type' => 'INT',
                'constraint' => 2,
                'default' => 0,
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
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['project_id', 'supplier_id']);
        $this->forge->addKey('project_id');
        $this->forge->addKey('supplier_id');
        $this->forge->addKey('status');
        $this->forge->addForeignKey('project_id', 'projects', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('supplier_id', 'organizations', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('project_suppliers');
    }

    public function down()
    {
        $this->forge->dropTable('project_suppliers');
    }
}
