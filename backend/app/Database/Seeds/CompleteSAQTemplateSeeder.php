<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Complete SAQ Template v2.0 Seeder
 * Creates a full SAQ template with all 5 sections (A-E), conditional logic, and table questions
 */
class CompleteSAQTemplateSeeder extends Seeder
{
    public function run()
    {
        $timestamp = date('Y-m-d H:i:s');
        
        // Check if template already exists
        $existingTemplate = $this->db->table('templates')
            ->where('name', 'SAQ 完整範本 v2.0')
            ->get()
            ->getRow();
        
        if ($existingTemplate) {
            echo "Template already exists, skipping...\n";
            return;
        }
        
        // Create template
        $this->db->table('templates')->insert([
            'name' => 'SAQ 完整範本 v2.0',
            'type' => 'SAQ',
            'latest_version' => '2.0.0',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        $templateId = $this->db->insertID();
        
        echo "Created template ID: {$templateId}\n";
        
        // Create all 5 sections
        $this->createSectionA($templateId, $timestamp);
        $this->createSectionB($templateId, $timestamp);
        $this->createSectionC($templateId, $timestamp);
        $this->createSectionD($templateId, $timestamp);
        $this->createSectionE($templateId, $timestamp);
        
        echo "Complete SAQ template seeded successfully!\n";
    }
    
    protected function createSectionA(int $templateId, string $timestamp)
    {
        // Section A: Labor
        $this->db->table('template_sections')->insert([
            'template_id' => $templateId,
            'section_id' => 'A',
            'order' => 1,
            'title' => 'A. 勞工 (Labor)',
            'description' => '勞工權益與工作條件評估',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        $sectionId = $this->db->insertID();
        
        // A.1 僱傭自由選擇
        $this->db->table('template_subsections')->insert([
            'section_id' => $sectionId,
            'subsection_id' => 'A.1',
            'order' => 1,
            'title' => 'A.1 僱傭自由選擇',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        $subsectionId = $this->db->insertID();
        
        $this->db->table('template_questions')->insert([
            'subsection_id' => $subsectionId,
            'question_id' => 'A.1.1',
            'order' => 1,
            'text' => '貴公司是否有制定並執行禁止強迫勞動的政策？',
            'type' => 'BOOLEAN',
            'required' => 1,
            'config' => null,
            'conditional_logic' => json_encode([
                'showWhen' => null,
                'followUpQuestions' => [
                    [
                        'condition' => ['operator' => 'equals', 'value' => true],
                        'questions' => [
                            [
                                'id' => 'A.1.1.1',
                                'text' => '請簡述該政策的內容',
                                'type' => 'TEXT',
                                'required' => false,
                                'config' => ['maxLength' => 500],
                            ],
                        ],
                    ],
                ],
            ]),
            'table_config' => null,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        
        $this->db->table('template_questions')->insert([
            'subsection_id' => $subsectionId,
            'question_id' => 'A.1.2',
            'order' => 2,
            'text' => '是否有員工被要求繳交押金或身分證件？',
            'type' => 'BOOLEAN',
            'required' => 1,
            'config' => null,
            'conditional_logic' => null,
            'table_config' => null,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        
        // A.2 童工與未成年勞工
        $this->db->table('template_subsections')->insert([
            'section_id' => $sectionId,
            'subsection_id' => 'A.2',
            'order' => 2,
            'title' => 'A.2 童工與未成年勞工',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        $subsectionId = $this->db->insertID();
        
        $this->db->table('template_questions')->insert([
            'subsection_id' => $subsectionId,
            'question_id' => 'A.2.1',
            'order' => 1,
            'text' => '貴公司是否僱用未滿 16 歲的童工？',
            'type' => 'BOOLEAN',
            'required' => 1,
            'config' => null,
            'conditional_logic' => null,
            'table_config' => null,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        
        $this->db->table('template_questions')->insert([
            'subsection_id' => $subsectionId,
            'question_id' => 'A.2.2',
            'order' => 2,
            'text' => '請提供過去三年未成年勞工（16-18歲）僱用紀錄',
            'type' => 'TABLE',
            'required' => 1,
            'config' => null,
            'conditional_logic' => null,
            'table_config' => json_encode([
                'columns' => [
                    ['id' => 'year', 'label' => '年度', 'type' => 'text', 'required' => true],
                    ['id' => 'count', 'label' => '人數', 'type' => 'number', 'required' => true],
                    ['id' => 'department', 'label' => '部門', 'type' => 'text', 'required' => false],
                    ['id' => 'remarks', 'label' => '備註', 'type' => 'text', 'required' => false],
                ],
                'minRows' => 3,
                'maxRows' => 10,
            ]),
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }
    
    protected function createSectionB(int $templateId, string $timestamp)
    {
        // Section B: Health & Safety
        $this->db->table('template_sections')->insert([
            'template_id' => $templateId,
            'section_id' => 'B',
            'order' => 2,
            'title' => 'B. 健康與安全 (Health & Safety)',
            'description' => '工作環境安全與職業健康評估',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        $sectionId = $this->db->insertID();
        
        // B.1 職業安全
        $this->db->table('template_subsections')->insert([
            'section_id' => $sectionId,
            'subsection_id' => 'B.1',
            'order' => 1,
            'title' => 'B.1 職業安全',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        $subsectionId = $this->db->insertID();
        
        $this->db->table('template_questions')->insert([
            'subsection_id' => $subsectionId,
            'question_id' => 'B.1.1',
            'order' => 1,
            'text' => '貴公司是否具有 ISO 45001 或同等職業安全衛生管理系統認證？',
            'type' => 'BOOLEAN',
            'required' => 1,
            'config' => null,
            'conditional_logic' => null,
            'table_config' => null,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        
        $this->db->table('template_questions')->insert([
            'subsection_id' => $subsectionId,
            'question_id' => 'B.1.2',
            'order' => 2,
            'text' => '是否定期進行工作場所安全檢查？',
            'type' => 'BOOLEAN',
            'required' => 1,
            'config' => null,
            'conditional_logic' => json_encode([
                'showWhen' => null,
                'followUpQuestions' => [
                    [
                        'condition' => ['operator' => 'equals', 'value' => true],
                        'questions' => [
                            [
                                'id' => 'B.1.2.1',
                                'text' => '檢查頻率為？',
                                'type' => 'SELECT',
                                'required' => true,
                                'config' => [
                                    'options' => [
                                        ['value' => 'weekly', 'label' => '每週'],
                                        ['value' => 'monthly', 'label' => '每月'],
                                        ['value' => 'quarterly', 'label' => '每季'],
                                        ['value' => 'annually', 'label' => '每年'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
            'table_config' => null,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        
        // B.2 緊急應變
        $this->db->table('template_subsections')->insert([
            'section_id' => $sectionId,
            'subsection_id' => 'B.2',
            'order' => 2,
            'title' => 'B.2 緊急應變',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        $subsectionId = $this->db->insertID();
        
        $this->db->table('template_questions')->insert([
            'subsection_id' => $subsectionId,
            'question_id' => 'B.2.1',
            'order' => 1,
            'text' => '是否制定緊急應變計畫（如火災、地震）？',
            'type' => 'BOOLEAN',
            'required' => 1,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }
    
    protected function createSectionC(int $templateId, string $timestamp)
    {
        // Section C: Environment
        $this->db->table('template_sections')->insert([
            'template_id' => $templateId,
            'section_id' => 'C',
            'order' => 3,
            'title' => 'C. 環境 (Environment)',
            'description' => '環境保護與資源管理評估',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        $sectionId = $this->db->insertID();
        
        // C.1 環境許可與報告
        $this->db->table('template_subsections')->insert([
            'section_id' => $sectionId,
            'subsection_id' => 'C.1',
            'order' => 1,
            'title' => 'C.1 環境許可與報告',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        $subsectionId = $this->db->insertID();
        
        $this->db->table('template_questions')->insert([
            'subsection_id' => $subsectionId,
            'question_id' => 'C.1.1',
            'order' => 1,
            'text' => '貴公司是否具有 ISO 14001 環境管理系統認證？',
            'type' => 'BOOLEAN',
            'required' => 1,
            'config' => null,
            'conditional_logic' => null,
            'table_config' => null,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        
        $this->db->table('template_questions')->insert([
            'subsection_id' => $subsectionId,
            'question_id' => 'C.1.2',
            'order' => 2,
            'text' => '是否定期進行溫室氣體盤查？',
            'type' => 'BOOLEAN',
            'required' => 1,
            'config' => null,
            'conditional_logic' => null,
            'table_config' => null,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }
    
    protected function createSectionD(int $templateId, string $timestamp)
    {
        // Section D: Ethics
        $this->db->table('template_sections')->insert([
            'template_id' => $templateId,
            'section_id' => 'D',
            'order' => 4,
            'title' => 'D. 道德規範 (Ethics)',
            'description' => '商業道德與誠信評估',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        $sectionId = $this->db->insertID();
        
        // D.1 商業誠信
        $this->db->table('template_subsections')->insert([
            'section_id' => $sectionId,
            'subsection_id' => 'D.1',
            'order' => 1,
            'title' => 'D.1 商業誠信',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        $subsectionId = $this->db->insertID();
        
        $this->db->table('template_questions')->insert([
            'subsection_id' => $subsectionId,
            'question_id' => 'D.1.1',
            'order' => 1,
            'text' => '貴公司是否制定反貪腐與反賄賂政策？',
            'type' => 'BOOLEAN',
            'required' => 1,
            'config' => null,
            'conditional_logic' => null,
            'table_config' => null,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        
        $this->db->table('template_questions')->insert([
            'subsection_id' => $subsectionId,
            'question_id' => 'D.1.2',
            'order' => 2,
            'text' => '是否提供檢舉管道？',
            'type' => 'BOOLEAN',
            'required' => 1,
            'config' => null,
            'conditional_logic' => null,
            'table_config' => null,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }
    
    protected function createSectionE(int $templateId, string $timestamp)
    {
        // Section E: Management System
        $this->db->table('template_sections')->insert([
            'template_id' => $templateId,
            'section_id' => 'E',
            'order' => 5,
            'title' => 'E. 管理系統 (Management System)',
            'description' => '公司管理系統與持續改進評估',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        $sectionId = $this->db->insertID();
        
        // E.1 公司承諾
        $this->db->table('template_subsections')->insert([
            'section_id' => $sectionId,
            'subsection_id' => 'E.1',
            'order' => 1,
            'title' => 'E.1 公司承諾',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        $subsectionId = $this->db->insertID();
        
        $this->db->table('template_questions')->insert([
            'subsection_id' => $subsectionId,
            'question_id' => 'E.1.1',
            'order' => 1,
            'text' => '貴公司是否制定企業社會責任（CSR）政策？',
            'type' => 'BOOLEAN',
            'required' => 1,
            'config' => null,
            'conditional_logic' => null,
            'table_config' => null,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        
        $this->db->table('template_questions')->insert([
            'subsection_id' => $subsectionId,
            'question_id' => 'E.1.2',
            'order' => 2,
            'text' => '是否定期發布永續報告書？',
            'type' => 'BOOLEAN',
            'required' => 1,
            'config' => null,
            'conditional_logic' => null,
            'table_config' => null,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }
}
