<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration to convert all table IDs from VARCHAR UUIDs to INT AUTO_INCREMENT
 * This migration will drop and recreate all tables with the new structure
 */
class ConvertToAutoIncrementIds extends Migration
{
    public function up()
    {
        // Drop tables in reverse order (due to foreign key constraints)
        $this->forge->dropTable('files', true);
        $this->forge->dropTable('review_logs', true);
        $this->forge->dropTable('answers', true);
        $this->forge->dropTable('review_stage_configs', true);
        $this->forge->dropTable('projects', true);
        $this->forge->dropTable('template_versions', true);
        $this->forge->dropTable('templates', true);
        $this->forge->dropTable('refresh_tokens', true);
        $this->forge->dropTable('users', true);
        $this->forge->dropTable('departments', true);
        $this->forge->dropTable('organizations', true);

        // ============================================
        // 1. Organizations Table
        // ============================================
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 200,
            ],
            'type' => [
                'type' => 'ENUM',
                'constraint' => ['HOST', 'SUPPLIER'],
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
        $this->forge->addUniqueKey('name');
        $this->forge->addKey('type');
        $this->forge->createTable('organizations');

        // ============================================
        // 2. Departments Table
        // ============================================
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'organization_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
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
        $this->forge->addKey('organization_id');
        $this->forge->addForeignKey('organization_id', 'organizations', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('departments');

        // ============================================
        // 3. Users Table
        // ============================================
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'username' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'password_hash' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'phone' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
            ],
            'role' => [
                'type' => 'ENUM',
                'constraint' => ['HOST', 'SUPPLIER', 'ADMIN'],
            ],
            'organization_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'department_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'is_active' => [
                'type' => 'BOOLEAN',
                'default' => true,
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
        $this->forge->addUniqueKey('username');
        $this->forge->addUniqueKey('email');
        $this->forge->addKey('organization_id');
        $this->forge->addKey('department_id');
        $this->forge->addKey('role');
        $this->forge->addForeignKey('organization_id', 'organizations', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('department_id', 'departments', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('users');

        // ============================================
        // 4. Refresh Tokens Table
        // ============================================
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'token_hash' => [
                'type' => 'VARCHAR',
                'constraint' => 64,
            ],
            'expires_at' => [
                'type' => 'DATETIME',
            ],
            'is_revoked' => [
                'type' => 'BOOLEAN',
                'default' => false,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('user_id');
        $this->forge->addKey('token_hash');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('refresh_tokens');

        // ============================================
        // 5. Templates Table
        // ============================================
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 200,
            ],
            'type' => [
                'type' => 'ENUM',
                'constraint' => ['SAQ', 'CONFLICT'],
            ],
            'latest_version' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
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
        $this->forge->addKey('type');
        $this->forge->createTable('templates');

        // ============================================
        // 6. Template Versions Table
        // ============================================
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'template_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'version' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
            ],
            'questions' => [
                'type' => 'JSON',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('template_id');
        $this->forge->addUniqueKey(['template_id', 'version']);
        $this->forge->addForeignKey('template_id', 'templates', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('template_versions');

        // ============================================
        // 7. Projects Table
        // ============================================
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 200,
            ],
            'year' => [
                'type' => 'INT',
                'constraint' => 4,
            ],
            'type' => [
                'type' => 'ENUM',
                'constraint' => ['SAQ', 'CONFLICT'],
            ],
            'template_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'template_version' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
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
        $this->forge->addKey('template_id');
        $this->forge->addKey('supplier_id');
        $this->forge->addKey('status');
        $this->forge->addKey('type');
        $this->forge->addKey('year');
        $this->forge->addForeignKey('template_id', 'templates', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('supplier_id', 'organizations', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('projects');

        // ============================================
        // 8. Review Stage Configs Table
        // ============================================
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
            'stage_order' => [
                'type' => 'INT',
                'constraint' => 2,
            ],
            'department_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'approver_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('project_id');
        $this->forge->addKey('department_id');
        $this->forge->addUniqueKey(['project_id', 'stage_order']);
        $this->forge->addForeignKey('project_id', 'projects', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('department_id', 'departments', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->addForeignKey('approver_id', 'users', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('review_stage_configs');

        // ============================================
        // 9. Answers Table
        // ============================================
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
            'question_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'value' => [
                'type' => 'JSON',
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
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('project_id');
        $this->forge->addUniqueKey(['project_id', 'question_id']);
        $this->forge->addForeignKey('project_id', 'projects', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('answers');

        // ============================================
        // 10. Review Logs Table
        // ============================================
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
            'reviewer_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'stage' => [
                'type' => 'INT',
                'constraint' => 2,
            ],
            'action' => [
                'type' => 'ENUM',
                'constraint' => ['APPROVE', 'RETURN'],
            ],
            'comment' => [
                'type' => 'TEXT',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('project_id');
        $this->forge->addKey('reviewer_id');
        $this->forge->addForeignKey('project_id', 'projects', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('reviewer_id', 'users', 'id', 'CASCADE', 'RESTRICT');
        $this->forge->createTable('review_logs');

        // ============================================
        // 11. Files Table
        // ============================================
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
            'question_id' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'file_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'file_path' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
            ],
            'file_url' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
            ],
            'file_size' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'content_type' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('project_id');
        $this->forge->addForeignKey('project_id', 'projects', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('files');
    }

    public function down()
    {
        // This migration is not reversible
        // To revert, restore from backup or re-run original migrations
    }
}
