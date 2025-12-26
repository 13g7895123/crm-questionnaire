<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class InitialDataSeeder extends Seeder
{
    public function run()
    {
        // Create Organizations (IDs will be auto-generated: 1, 2, 3)
        $organizations = [
            [
                'name' => '製造商公司',
                'type' => 'HOST',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => '供應商 A 公司',
                'type' => 'SUPPLIER',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => '供應商 B 公司',
                'type' => 'SUPPLIER',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];

        $this->db->table('organizations')->insertBatch($organizations);

        // Create Departments (reference organization by numeric ID)
        $departments = [
            [
                'name' => '品質管理部',
                'organization_id' => 1, // 製造商公司
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => '採購部',
                'organization_id' => 1, // 製造商公司
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => '高階主管部',
                'organization_id' => 1, // 製造商公司
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => '業務部',
                'organization_id' => 2, // 供應商 A 公司
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => '業務部',
                'organization_id' => 3, // 供應商 B 公司
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];

        $this->db->table('departments')->insertBatch($departments);

        // Create Users (reference organization and department by numeric ID)
        $users = [
            [
                'username' => 'admin',
                'email' => 'admin@example.com',
                'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
                'phone' => '0912345678',
                'role' => 'ADMIN',
                'organization_id' => 1, // 製造商公司
                'department_id' => 1,   // 品質管理部
                'is_active' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'username' => 'host_user',
                'email' => 'host@example.com',
                'password_hash' => password_hash('host1234', PASSWORD_DEFAULT),
                'phone' => '0923456789',
                'role' => 'HOST',
                'organization_id' => 1, // 製造商公司
                'department_id' => 1,   // 品質管理部
                'is_active' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'username' => 'proc_user',
                'email' => 'proc@example.com',
                'password_hash' => password_hash('proc1234', PASSWORD_DEFAULT),
                'phone' => '0934567890',
                'role' => 'HOST',
                'organization_id' => 1, // 製造商公司
                'department_id' => 2,   // 採購部
                'is_active' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'username' => 'supplier_a',
                'email' => 'supplier_a@example.com',
                'password_hash' => password_hash('supp1234', PASSWORD_DEFAULT),
                'phone' => '0945678901',
                'role' => 'SUPPLIER',
                'organization_id' => 2, // 供應商 A 公司
                'department_id' => 4,   // 業務部 (供應商A)
                'is_active' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'username' => 'supplier_b',
                'email' => 'supplier_b@example.com',
                'password_hash' => password_hash('supp1234', PASSWORD_DEFAULT),
                'phone' => '0956789012',
                'role' => 'SUPPLIER',
                'organization_id' => 3, // 供應商 B 公司
                'department_id' => 5,   // 業務部 (供應商B)
                'is_active' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];

        $this->db->table('users')->insertBatch($users);

        // Create Templates
        $templates = [
            [
                'name' => 'SAQ 標準範本',
                'type' => 'SAQ',
                'latest_version' => '1.0.0',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => '衝突資產調查範本',
                'type' => 'CONFLICT',
                'latest_version' => '1.0.0',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];

        $this->db->table('templates')->insertBatch($templates);

        $templateVersions = [
            [
                'template_id' => 1, // SAQ 標準範本
                'version' => '1.0.0',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'template_id' => 2, // 衝突資產調查範本
                'version' => '1.0.0',
                'created_at' => date('Y-m-d H:i:s'),
            ],
        ];

        $this->db->table('template_versions')->insertBatch($templateVersions);

        echo "Initial data seeded successfully!\n";
        echo "\nTest Accounts:\n";
        echo "- Admin: admin / admin123\n";
        echo "- HOST (QM Dept): host_user / host1234\n";
        echo "- HOST (Proc Dept): proc_user / proc1234\n";
        echo "- Supplier A: supplier_a / supp1234\n";
        echo "- Supplier B: supplier_b / supp1234\n";
    }
}
