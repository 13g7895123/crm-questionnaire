<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRmTemplateSetsTables extends Migration
{
    public function up()
    {
        // 範本組表
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
                'comment' => '範本組名稱',
            ],
            'year' => [
                'type' => 'INT',
                'constraint' => 4,
                'comment' => '年份',
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => '說明',
            ],
            'cmrt_enabled' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'comment' => '是否啟用 CMRT',
            ],
            'cmrt_version' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'comment' => 'CMRT 版本號',
            ],
            'emrt_enabled' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'comment' => '是否啟用 EMRT',
            ],
            'emrt_version' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'comment' => 'EMRT 版本號',
            ],
            'amrt_enabled' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'comment' => '是否啟用 AMRT',
            ],
            'amrt_version' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'comment' => 'AMRT 版本號',
            ],
            'amrt_minerals' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'AMRT 選擇的礦產（JSON 陣列）',
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
        $this->forge->addKey('year');
        $this->forge->addKey('created_at');
        $this->forge->createTable('rm_template_sets');
    }

    public function down()
    {
        $this->forge->dropTable('rm_template_sets');
    }
}
