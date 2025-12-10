<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProjectBasicInfoTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'project_supplier_id' => [
                'type' => 'INT',
                'unsigned' => true,
                'comment' => 'FK to project_suppliers.id',
            ],
            'company_name' => [
                'type' => 'VARCHAR',
                'constraint' => 200,
            ],
            'company_address' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'employee_count' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true,
                'comment' => 'Total employee count',
            ],
            'male_count' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true,
            ],
            'female_count' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true,
            ],
            'foreign_count' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => true,
            ],
            'facilities' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Array of facilities: [{location, address, area, type}]',
            ],
            'certifications' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Array of certification names',
            ],
            'rba_online_member' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => true,
                'comment' => '1=Yes, 0=No, NULL=not answered',
            ],
            'contacts' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Array of contacts: [{name, title, department, email, phone}]',
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
        $this->forge->addUniqueKey('project_supplier_id');
        $this->forge->addForeignKey('project_supplier_id', 'project_suppliers', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('project_basic_info');
    }

    public function down()
    {
        $this->forge->dropTable('project_basic_info');
    }
}
