<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class InitialDataSeeder extends Seeder
{
    public function run()
    {
        // Create Organizations
        $organizations = [
            [
                'id' => 'org_host001',
                'name' => '製造商公司',
                'type' => 'HOST',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'id' => 'org_supplier001',
                'name' => '供應商 A 公司',
                'type' => 'SUPPLIER',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'id' => 'org_supplier002',
                'name' => '供應商 B 公司',
                'type' => 'SUPPLIER',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];

        $this->db->table('organizations')->insertBatch($organizations);

        // Create Departments
        $departments = [
            [
                'id' => 'dept_qm001',
                'name' => '品質管理部',
                'organization_id' => 'org_host001',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'id' => 'dept_proc001',
                'name' => '採購部',
                'organization_id' => 'org_host001',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'id' => 'dept_exec001',
                'name' => '高階主管部',
                'organization_id' => 'org_host001',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'id' => 'dept_sup001',
                'name' => '業務部',
                'organization_id' => 'org_supplier001',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'id' => 'dept_sup002',
                'name' => '業務部',
                'organization_id' => 'org_supplier002',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];

        $this->db->table('departments')->insertBatch($departments);

        // Create Users
        $users = [
            [
                'id' => 'usr_admin001',
                'username' => 'admin',
                'email' => 'admin@example.com',
                'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
                'phone' => '0912345678',
                'role' => 'ADMIN',
                'organization_id' => 'org_host001',
                'department_id' => 'dept_qm001',
                'is_active' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'id' => 'usr_host001',
                'username' => 'host_user',
                'email' => 'host@example.com',
                'password_hash' => password_hash('host1234', PASSWORD_DEFAULT),
                'phone' => '0923456789',
                'role' => 'HOST',
                'organization_id' => 'org_host001',
                'department_id' => 'dept_qm001',
                'is_active' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'id' => 'usr_host002',
                'username' => 'proc_user',
                'email' => 'proc@example.com',
                'password_hash' => password_hash('proc1234', PASSWORD_DEFAULT),
                'phone' => '0934567890',
                'role' => 'HOST',
                'organization_id' => 'org_host001',
                'department_id' => 'dept_proc001',
                'is_active' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'id' => 'usr_supplier001',
                'username' => 'supplier_a',
                'email' => 'supplier_a@example.com',
                'password_hash' => password_hash('supp1234', PASSWORD_DEFAULT),
                'phone' => '0945678901',
                'role' => 'SUPPLIER',
                'organization_id' => 'org_supplier001',
                'department_id' => 'dept_sup001',
                'is_active' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'id' => 'usr_supplier002',
                'username' => 'supplier_b',
                'email' => 'supplier_b@example.com',
                'password_hash' => password_hash('supp1234', PASSWORD_DEFAULT),
                'phone' => '0956789012',
                'role' => 'SUPPLIER',
                'organization_id' => 'org_supplier002',
                'department_id' => 'dept_sup002',
                'is_active' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];

        $this->db->table('users')->insertBatch($users);

        // Create Templates
        $templates = [
            [
                'id' => 'tmpl_saq001',
                'name' => 'SAQ 標準範本',
                'type' => 'SAQ',
                'latest_version' => '1.0.0',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'id' => 'tmpl_conflict001',
                'name' => '衝突資產調查範本',
                'type' => 'CONFLICT',
                'latest_version' => '1.0.0',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];

        $this->db->table('templates')->insertBatch($templates);

        // Create Template Versions
        $saqQuestions = [
            [
                'id' => 'q_saq001',
                'text' => '貴公司是否具有 ISO 9001 認證？',
                'type' => 'BOOLEAN',
                'required' => true,
            ],
            [
                'id' => 'q_saq002',
                'text' => '請選擇貴公司的主要產業類別',
                'type' => 'SINGLE_CHOICE',
                'required' => true,
                'options' => ['電子製造', '化工製造', '機械製造', '其他'],
            ],
            [
                'id' => 'q_saq003',
                'text' => '貴公司員工人數',
                'type' => 'NUMBER',
                'required' => true,
                'config' => ['numberMin' => 1, 'numberMax' => 100000],
            ],
            [
                'id' => 'q_saq004',
                'text' => '請簡述貴公司的品質管理流程',
                'type' => 'TEXT',
                'required' => true,
                'config' => ['maxLength' => 1000],
            ],
            [
                'id' => 'q_saq005',
                'text' => '請上傳貴公司的營業執照',
                'type' => 'FILE',
                'required' => true,
                'config' => ['maxFileSize' => 5242880, 'allowedFileTypes' => ['pdf', 'jpg', 'png']],
            ],
        ];

        $conflictQuestions = [
            [
                'id' => 'q_conf001',
                'text' => '貴公司是否使用錫、鉭、鎢或金等衝突礦物？',
                'type' => 'BOOLEAN',
                'required' => true,
            ],
            [
                'id' => 'q_conf002',
                'text' => '請列出貴公司的衝突礦物供應來源',
                'type' => 'TEXT',
                'required' => false,
                'config' => ['maxLength' => 2000],
            ],
            [
                'id' => 'q_conf003',
                'text' => '貴公司是否有衝突礦物政策？',
                'type' => 'BOOLEAN',
                'required' => true,
            ],
        ];

        $templateVersions = [
            [
                'id' => 'tv_saq001',
                'template_id' => 'tmpl_saq001',
                'version' => '1.0.0',
                'questions' => json_encode($saqQuestions),
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'id' => 'tv_conflict001',
                'template_id' => 'tmpl_conflict001',
                'version' => '1.0.0',
                'questions' => json_encode($conflictQuestions),
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
