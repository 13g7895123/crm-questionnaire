<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class TemplateSectionsSeeder extends Seeder
{
    public function run()
    {
        // Get SAQ template ID (assuming id=1 based on earlier db:table output)
        $templateId = 1;
        $timestamp = date('Y-m-d H:i:s');

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
        $sectionAId = $this->db->insertID();

        // Subsection A.1
        $this->db->table('template_subsections')->insert([
            'section_id' => $sectionAId,
            'subsection_id' => 'A.1',
            'order' => 1,
            'title' => 'A.1 僱傭自由選擇',
            'description' => null,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        $subsectionA1Id = $this->db->insertID();

        // Question A.1.1
        $this->db->table('template_questions')->insert([
            'subsection_id' => $subsectionA1Id,
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

        // Subsection A.2
        $this->db->table('template_subsections')->insert([
            'section_id' => $sectionAId,
            'subsection_id' => 'A.2',
            'order' => 2,
            'title' => 'A.2 童工與未成年勞工',
            'description' => null,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        $subsectionA2Id = $this->db->insertID();

        // Question A.2.1 with TABLE type
        $this->db->table('template_questions')->insert([
            'subsection_id' => $subsectionA2Id,
            'question_id' => 'A.2.1',
            'order' => 1,
            'text' => '請提供過去三年未成年勞工僱用紀錄',
            'type' => 'TABLE',
            'required' => 1,
            'config' => null,
            'conditional_logic' => null,
            'table_config' => json_encode([
                'columns' => [
                    ['id' => 'year', 'label' => '年度', 'type' => 'text'],
                    ['id' => 'count', 'label' => '人數', 'type' => 'number'],
                    ['id' => 'remarks', 'label' => '備註', 'type' => 'text'],
                ],
                'minRows' => 1,
                'maxRows' => 10,
            ]),
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

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
        $sectionBId = $this->db->insertID();

        // Subsection B.1
        $this->db->table('template_subsections')->insert([
            'section_id' => $sectionBId,
            'subsection_id' => 'B.1',
            'order' => 1,
            'title' => 'B.1 職業安全',
            'description' => null,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        $subsectionB1Id = $this->db->insertID();

        // Question B.1.1
        $this->db->table('template_questions')->insert([
            'subsection_id' => $subsectionB1Id,
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

        echo "Template sections seeded successfully!\n";
    }
}
