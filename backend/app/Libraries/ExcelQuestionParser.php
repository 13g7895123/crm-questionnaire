<?php

namespace App\Libraries;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Excel Question Parser
 * 
 * 解析 SAQ 問卷 Excel 檔案，將其轉換為結構化的題目資料。
 * 
 * Excel 格式規範：
 * - 分頁標題需符合 A.xxx、B.xxx、C.xxx 格式
 * - 資料從第 3 列、B 欄開始
 * - B 欄: No. (題目編號)
 * - C 欄: Item (題目內容)
 * - D 欄: Self Assessment (公司自評，是/否)
 * - E 欄: Remark (備註，條件式追問)
 * - F~H 欄: Evidence (表格資料欄位)
 */
class ExcelQuestionParser
{
    /**
     * 解析 Excel 檔案
     * 
     * @param Spreadsheet $spreadsheet
     * @return array 結構化資料
     */
    public function parse(Spreadsheet $spreadsheet): array
    {
        $sections = [];

        foreach ($spreadsheet->getSheetNames() as $sheetName) {
            // 只解析符合 A.xxx、B.xxx 等格式的分頁
            if (!preg_match('/^([A-Z])\./', $sheetName, $matches)) {
                continue;
            }

            $sectionId = $matches[1];
            $sheet = $spreadsheet->getSheetByName($sheetName);
            $sectionOrder = count($sections) + 1;

            $section = $this->parseSheet($sheet, $sectionId, $sheetName, $sectionOrder);
            if ($section) {
                $sections[] = $section;
            }
        }

        // 計算統計資訊
        $metadata = $this->calculateMetadata($sections);

        return [
            'sections' => $sections,
            'metadata' => $metadata,
        ];
    }

    /**
     * 解析單一工作表
     */
    protected function parseSheet(Worksheet $sheet, string $sectionId, string $sheetName, int $sectionOrder): ?array
    {
        $subsections = [];
        $currentSubsection = null;
        $highestRow = $sheet->getHighestRow();
        $subsectionOrder = 0;
        $questionOrder = 0;

        // 從第 4 列開始（第 3 列是標題列）
        $row = 4;

        while ($row <= $highestRow) {
            $no = $this->getCellValue($sheet, 'B', $row);
            $item = $this->getCellValue($sheet, 'C', $row);

            // 跳過空行
            if (empty($no) && empty($item)) {
                $row++;
                continue;
            }

            // 判斷是區段標題、小標題還是題目
            // 注意：先檢查題目（更具體的格式 A.1.1），再檢查小標題（較寬鬆的格式 A.1.）
            if ($this->isSectionTitle($no, $sectionId)) {
                // 這是區段標題行 (如 A. Labor Rights)
                $row++;
                continue;
            }

            // 先檢查題目（A.1.1 格式更具體）
            if ($this->isQuestionNo($no, $sectionId)) {
                // 這是題目行 (如 A.1.1)
                $questionOrder++;
                $question = $this->parseQuestion($sheet, $row, $no, $item, $questionOrder);

                if ($currentSubsection) {
                    $currentSubsection['questions'][] = $question['question'];
                    $row = $question['nextRow'];
                } else {
                    // 沒有小標題，直接加入預設小標題
                    $subsectionOrder++;
                    $currentSubsection = [
                        'id' => $sectionId . '.0',
                        'order' => $subsectionOrder,
                        'title' => $sectionId . '.0 General',
                        'questions' => [$question['question']],
                    ];
                    $row = $question['nextRow'];
                }
                continue;
            }

            // 再檢查小標題（A.1. 格式較寬鬆）
            if ($this->isSubsectionTitle($no, $sectionId)) {
                // 這是小標題行 (如 A.1. Labor Management\n勞動管理)
                if ($currentSubsection) {
                    $subsections[] = $currentSubsection;
                }
                $subsectionOrder++;
                $questionOrder = 0; // Reset question order for new subsection

                // 解析多語系標題
                $i18nTitle = $this->parseI18nTitle($no);
                $currentSubsection = [
                    'id' => $this->extractSubsectionId($no),
                    'order' => $subsectionOrder,
                    'title' => trim($no),
                    'title_en' => $i18nTitle['en'],
                    'title_zh' => $i18nTitle['zh'],
                    'questions' => [],
                ];
                $row++;
                continue;
            }

            $row++;
        }

        // 加入最後的小標題
        if ($currentSubsection) {
            $subsections[] = $currentSubsection;
        }

        if (empty($subsections)) {
            return null;
        }

        // 解析區段標題的多語系
        // 從分頁第 4 列取得完整的區段標題
        $sectionTitleRow = $this->getCellValue($sheet, 'B', 4);
        $sectionI18n = $this->parseI18nTitle($sectionTitleRow ?? $sheetName);

        return [
            'id' => $sectionId,
            'order' => $sectionOrder,
            'title' => $sectionTitleRow ?? $sheetName,
            'title_en' => $sectionI18n['en'],
            'title_zh' => $sectionI18n['zh'],
            'subsections' => $subsections,
        ];
    }

    /**
     * 解析單一題目
     */
    protected function parseQuestion(Worksheet $sheet, int $startRow, string $no, ?string $item, int $order): array
    {
        // 解析題目文字的多語系
        $textI18n = $this->parseI18nText($item);

        $question = [
            'id' => $no,
            'order' => $order,
            'text' => $item ?? '',
            'text_en' => $textI18n['en'],
            'text_zh' => $textI18n['zh'],
            'type' => 'BOOLEAN',
            'required' => true,
        ];

        // 檢查是否為表格題型（B、C 欄有合併儲存格）
        $mergeRange = $this->getMergeRange($sheet, 'B', $startRow);
        $isTableQuestion = $mergeRange !== null;

        if ($isTableQuestion) {
            // 表格題型：解析多列追問
            $tableData = $this->parseTableQuestion($sheet, $startRow, $mergeRange);
            $question['conditionalLogic'] = [
                'followUpQuestions' => [
                    [
                        'condition' => ['operator' => 'equals', 'value' => true],
                        'questions' => [
                            [
                                'id' => $no . '.table',
                                'text' => '詳細資料',
                                'type' => 'TABLE',
                                'tableConfig' => $tableData['tableConfig'],
                            ],
                        ],
                    ],
                ],
            ];
            return [
                'question' => $question,
                'nextRow' => $tableData['endRow'] + 1,
            ];
        } else {
            // 一般題型：檢查 E 欄是否有條件式追問
            $remark = $this->getCellValue($sheet, 'E', $startRow);
            if ($remark && $this->isConditionalField($remark)) {
                $followUpText = $this->extractFollowUpText($remark);
                if ($followUpText) {
                    $question['conditionalLogic'] = [
                        'followUpQuestions' => [
                            [
                                'condition' => ['operator' => 'equals', 'value' => true],
                                'questions' => [
                                    [
                                        'id' => $no . '.remark',
                                        'text' => $followUpText,
                                        'type' => 'TEXT',
                                        'required' => false,
                                    ],
                                ],
                            ],
                        ],
                    ];
                }
            }
            return [
                'question' => $question,
                'nextRow' => $startRow + 1,
            ];
        }
    }

    /**
     * 解析表格題型
     */
    protected function parseTableQuestion(Worksheet $sheet, int $startRow, array $mergeRange): array
    {
        $endRow = $mergeRange['endRow'];
        $rowLabels = [];
        $columns = [];

        // 取得 F~H 欄的表頭（從第一列取得年度等資訊）
        for ($col = 'F'; $col <= 'H'; $col++) {
            $headerValue = $this->getCellValue($sheet, $col, $startRow);
            if ($headerValue !== null && $headerValue !== '') {
                $columns[] = (string) $headerValue;
            }
        }

        // 取得 E 欄的列標題
        for ($row = $startRow; $row <= $endRow; $row++) {
            $label = $this->getCellValue($sheet, 'E', $row);
            if ($label && $this->isConditionalField($label)) {
                $label = $this->extractFollowUpText($label);
            }
            if ($label) {
                $rowLabels[] = $label;
            }
        }

        // 將表頭轉換為 v2.0 TableColumn 格式
        $tableColumns = [];
        // 第一欄是列標籤欄位
        $tableColumns[] = [
            'id' => 'row_label',
            'label' => '項目',
            'type' => 'text',
            'required' => true,
        ];
        foreach ($columns as $idx => $colLabel) {
            $tableColumns[] = [
                'id' => 'col_' . ($idx + 1),
                'label' => (string) $colLabel,
                'type' => 'text',
                'required' => false,
            ];
        }

        return [
            'endRow' => $endRow,
            'tableConfig' => [
                'columns' => $tableColumns,
                'minRows' => count($rowLabels),
                'maxRows' => count($rowLabels),
            ],
        ];
    }

    /**
     * 取得儲存格值
     */
    protected function getCellValue(Worksheet $sheet, string $col, int $row): ?string
    {
        $cell = $sheet->getCell($col . $row);
        $value = $cell->getValue();

        if ($value === null) {
            return null;
        }

        // 如果是公式，嘗試取得計算值
        if (is_string($value) && str_starts_with($value, '=')) {
            // 對於 IF 公式，我們只需要提取顯示文字
            return $value;
        }

        return trim((string) $value);
    }

    /**
     * 檢查是否為區段標題
     */
    protected function isSectionTitle(?string $no, string $sectionId): bool
    {
        if (!$no) {
            return false;
        }
        // 匹配 "A. Labor Rights" 格式
        return preg_match('/^' . preg_quote($sectionId, '/') . '\.\s+\w/i', $no) === 1;
    }

    /**
     * 檢查是否為小標題
     */
    protected function isSubsectionTitle(?string $no, string $sectionId): bool
    {
        if (!$no) {
            return false;
        }
        // 匹配 "A.1." 或 "A.1. xxx" 格式，但排除題目編號格式 "A.1.1"
        // 小標題格式：A.1. (後面接文字或空白)
        // 排除題目格式：A.1.1 (後面接數字)
        if (preg_match('/^' . preg_quote($sectionId, '/') . '\\.\\d+\\.\\d+/i', $no)) {
            // 如果匹配題目格式 (X.n.n)，則不是小標題
            return false;
        }
        return preg_match('/^' . preg_quote($sectionId, '/') . '\\.\\d+\\.\\s*\\w/i', $no) === 1;
    }

    /**
     * 檢查是否為題目編號
     */
    protected function isQuestionNo(?string $no, string $sectionId): bool
    {
        if (!$no) {
            return false;
        }
        // 匹配 "A.1.1" 格式
        return preg_match('/^' . preg_quote($sectionId, '/') . '\.\d+\.\d+$/i', $no) === 1;
    }

    /**
     * 提取小標題 ID
     */
    protected function extractSubsectionId(string $title): string
    {
        if (preg_match('/^([A-Z]\.\d+)/', $title, $matches)) {
            return $matches[1];
        }
        return $title;
    }

    /**
     * 檢查是否為條件式欄位
     */
    protected function isConditionalField(string $value): bool
    {
        return str_starts_with($value, '=IF(');
    }

    /**
     * 從 IF 公式中提取追問文字
     */
    protected function extractFollowUpText(string $formula): ?string
    {
        // 匹配 =IF(D7=1, "Content of...", "")
        if (preg_match('/=IF\([^,]+,\s*"([^"]+)"/', $formula, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }

    /**
     * 取得合併儲存格範圍
     */
    protected function getMergeRange(Worksheet $sheet, string $col, int $row): ?array
    {
        $cellAddress = $col . $row;

        foreach ($sheet->getMergeCells() as $range) {
            if (preg_match('/^' . preg_quote($cellAddress, '/') . ':([A-Z]+)(\d+)$/', $range, $matches)) {
                return [
                    'startRow' => $row,
                    'endRow' => (int) $matches[2],
                ];
            }
        }

        return null;
    }

    /**
     * 計算統計資訊
     */
    protected function calculateMetadata(array $sections): array
    {
        $totalSubsections = 0;
        $totalQuestions = 0;

        foreach ($sections as $section) {
            $totalSubsections += count($section['subsections'] ?? []);
            foreach ($section['subsections'] ?? [] as $subsection) {
                $totalQuestions += count($subsection['questions'] ?? []);
            }
        }

        return [
            'totalSections' => count($sections),
            'totalSubsections' => $totalSubsections,
            'totalQuestions' => $totalQuestions,
        ];
    }

    /**
     * 解析多語系文本
     * 
     * 支援格式：
     * - "English Text\n中文文字" (換行分隔)
     * - "English Text 中文文字" (空白分隔，中文開頭判斷)
     * 
     * @param string|null $text
     * @return array ['en' => string, 'zh' => string]
     */
    public function parseI18nText(?string $text): array
    {
        if (!$text) {
            return ['en' => '', 'zh' => ''];
        }

        $text = trim($text);

        // 換行分隔優先
        if (str_contains($text, "\n")) {
            $parts = preg_split('/\r?\n/', $text, 2);
            return [
                'en' => trim($parts[0]),
                'zh' => trim($parts[1] ?? $parts[0]),
            ];
        }

        // 空白分隔，偵測中文開始位置
        // 匹配：英文部分 + 空白 + 中文開頭
        if (preg_match('/^(.+?)\s+([\x{4e00}-\x{9fff}].*)$/su', $text, $matches)) {
            return [
                'en' => trim($matches[1]),
                'zh' => trim($matches[2]),
            ];
        }

        // 無法分離，兩者相同
        return ['en' => trim($text), 'zh' => trim($text)];
    }

    /**
     * 解析區段/小標題的多語系標題
     * 
     * 格式：A. Labor Rights 勞動權益 或 A.1. Labor Management\n勞動管理
     * 
     * @param string $titleWithId 包含編號的標題
     * @return array ['id' => string, 'en' => string, 'zh' => string]
     */
    public function parseI18nTitle(string $titleWithId): array
    {
        $titleWithId = trim($titleWithId);

        // 提取編號部分（例如 A. 或 A.1.）
        if (preg_match('/^([A-Z](?:\.\d+)?\.)\s*(.*)$/s', $titleWithId, $matches)) {
            $id = trim($matches[1], '.');
            $titlePart = $matches[2];
            $i18n = $this->parseI18nText($titlePart);

            return [
                'id' => $id,
                'en' => $i18n['en'],
                'zh' => $i18n['zh'],
            ];
        }

        // 無法匹配編號，整個作為標題
        $i18n = $this->parseI18nText($titleWithId);
        return [
            'id' => '',
            'en' => $i18n['en'],
            'zh' => $i18n['zh'],
        ];
    }
}
