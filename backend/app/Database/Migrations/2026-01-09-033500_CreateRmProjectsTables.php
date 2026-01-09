<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRmProjectsTables extends Migration
{
    public function up()
    {
        // 專案表
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'comment' => '專案名稱',
            ],
            'year' => [
                'type' => 'INT',
                'constraint' => 4,
                'comment' => '年份',
            ],
            'type' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'default' => 'CONFLICT',
                'comment' => '專案類型',
            ],
            'template_set_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'comment' => '範本組 ID',
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['DRAFT', 'IN_PROGRESS', 'COMPLETED', 'ARCHIVED'],
                'default' => 'DRAFT',
                'comment' => '專案狀態',
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => '專案說明',
            ],
            'review_config' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => '審核流程設定（JSON）',
            ],
            'created_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
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

        $this->forge->addKey('id', true);
        $this->forge->addKey('template_set_id');
        $this->forge->addKey('year');
        $this->forge->addKey('status');
        $this->forge->addKey('created_at');
        $this->forge->addForeignKey('template_set_id', 'rm_template_sets', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('rm_projects');

        // 供應商指派表
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
                'comment' => '專案 ID',
            ],
            'supplier_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'comment' => '供應商 ID',
            ],
            'supplier_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'comment' => '供應商名稱',
            ],
            'supplier_email' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'comment' => '供應商 Email',
            ],
            'cmrt_required' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'comment' => '是否需要填寫 CMRT',
            ],
            'emrt_required' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'comment' => '是否需要填寫 EMRT',
            ],
            'amrt_required' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'comment' => '是否需要填寫 AMRT',
            ],
            'amrt_minerals' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'AMRT 指定的礦產（JSON 陣列）',
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['not_assigned', 'assigned', 'in_progress', 'submitted', 'reviewing', 'completed'],
                'default' => 'not_assigned',
                'comment' => '填寫狀態',
            ],
            'notified_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => '通知時間',
            ],
            'submitted_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => '提交時間',
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
        $this->forge->addKey('project_id');
        $this->forge->addKey('supplier_id');
        $this->forge->addKey('status');
        $this->forge->addUniqueKey(['project_id', 'supplier_id']);
        $this->forge->addForeignKey('project_id', 'rm_projects', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('rm_supplier_assignments');
    }

    public function down()
    {
        $this->forge->dropTable('rm_supplier_assignments');
        $this->forge->dropTable('rm_projects');
    }
}
