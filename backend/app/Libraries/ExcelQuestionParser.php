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

        // 檢查是否為表格題型（B 欄有合併儲存格，且 F~H 欄有表頭資料）
        $mergeRange = $this->getMergeRange($sheet, 'B', $startRow);
        $hasTableHeaders = $this->hasTableHeaders($sheet, $startRow);
        $isTableQuestion = $mergeRange !== null && $hasTableHeaders;
        $isMultiFollowUp = $mergeRange !== null && !$hasTableHeaders;

        if ($isTableQuestion) {
            // 表格題型：解析多列追問（轉置表格）
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
        } elseif ($isMultiFollowUp) {
            // 多題進階題目：B 欄有 merge 但 F~H 欄沒有資料
            $followUpQuestions = $this->parseMultiFollowUpQuestions($sheet, $startRow, $mergeRange);
            if (!empty($followUpQuestions)) {
                $question['conditionalLogic'] = [
                    'followUpQuestions' => [
                        [
                            'condition' => ['operator' => 'equals', 'value' => true],
                            'questions' => $followUpQuestions,
                        ],
                    ],
                ];
            }
            return [
                'question' => $question,
                'nextRow' => $mergeRange['endRow'] + 1,
            ];
        } else {
            // 一般題型：檢查 E 欄是否有條件式追問
            // 需要直接取原始值來判斷是否為 IF 公式（getCellValue 會處理公式回傳文字）
            $eCell = $sheet->getCell('E' . $startRow);
            $rawValue = $eCell->getValue();

            if ($rawValue && is_string($rawValue) && $this->isConditionalField($rawValue)) {
                // 取得解析後的進階題目文字
                $followUpText = $this->getCellValue($sheet, 'E', $startRow);
                if ($followUpText) {
                    // 解析 i18n
                    $i18n = $this->parseI18nText($followUpText);
                    $question['conditionalLogic'] = [
                        'followUpQuestions' => [
                            [
                                'condition' => ['operator' => 'equals', 'value' => true],
                                'questions' => [
                                    [
                                        'id' => $no . '.1',
                                        'text' => $i18n['zh'] ?: $i18n['en'],
                                        'text_en' => $i18n['en'],
                                        'text_zh' => $i18n['zh'],
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
     * 解析表格題型 (轉置：Excel 的 Columns 變成 Rows，Rows 變成 Columns)
     * 並支援 i18n 欄位標籤
     */
    protected function parseTableQuestion(Worksheet $sheet, int $startRow, array $mergeRange): array
    {
        $endRow = $mergeRange['endRow'];

        // 1. 取得 Excel 的 Column Headers (F~H 欄) -> 變成 UI 的 Year Rows
        // 年份直接從 Excel 讀取，不做額外計算
        $years = [];
        for ($col = 'F'; $col <= 'H'; $col++) {
            $yearValue = $this->getCellValue($sheet, $col, $startRow);
            if ($yearValue !== null && $yearValue !== '') {
                $years[] = (string)$yearValue;
            }
        }

        // 2. 取得 Excel 的 Row Labels (E 欄) -> 變成 UI 的 Columns
        // 並解析 i18n (例如 "Violation Count 違犯件數")
        $attributes = [];
        for ($row = $startRow; $row <= $endRow; $row++) {
            $label = $this->getCellValue($sheet, 'E', $row);
            if ($label && $this->isConditionalField($label)) {
                $label = $this->extractFollowUpText($label);
            }
            if ($label) {
                // 移除可能的冒號結尾
                $label = rtrim($label, ':');
                // 解析 i18n
                $i18n = $this->parseI18nText($label);
                $attributes[] = $i18n;
            }
        }

        // 3. 建構 v2.0 TableColumn 格式 (含 i18n)
        $tableColumns = [];

        // 第一欄：固定為「年度」(i18n)
        $tableColumns[] = [
            'id' => 'year',
            'label' => '年度',
            'label_en' => 'Year',
            'label_zh' => '年度',
            'type' => 'text',
            'required' => true,
        ];

        // 後續欄位：來自 E 欄的屬性 (含 i18n)
        foreach ($attributes as $idx => $attr) {
            $tableColumns[] = [
                'id' => 'col_' . ($idx + 1),
                'label' => $attr['zh'] ?: $attr['en'],  // 預設顯示中文
                'label_en' => $attr['en'],
                'label_zh' => $attr['zh'],
                'type' => 'text',
                'required' => false,
            ];
        }

        return [
            'endRow' => $endRow,
            'tableConfig' => [
                'columns' => $tableColumns,
                'minRows' => count($years),
                'maxRows' => count($years),
                'prefilledRows' => $years, // 從 Excel 讀取的實際年份
            ],
        ];
    }

    /**
     * 檢查 F~H 欄是否有表頭資料
     * 用於區分表格題和多題進階題目
     */
    protected function hasTableHeaders(Worksheet $sheet, int $row): bool
    {
        for ($col = 'F'; $col <= 'H'; $col++) {
            $value = $sheet->getCell($col . $row)->getValue();
            if ($value !== null && $value !== '') {
                return true;
            }
        }
        return false;
    }

    /**
     * 解析多題進階題目（B 欄 merge 但 F~H 欄無資料）
     * 每一行 E 欄是一個 TEXT 類型的進階問題
     */
    protected function parseMultiFollowUpQuestions(Worksheet $sheet, int $startRow, array $mergeRange): array
    {
        $endRow = $mergeRange['endRow'];
        $questions = [];
        $questionNo = $sheet->getCell('B' . $startRow)->getValue();

        for ($row = $startRow; $row <= $endRow; $row++) {
            $eCell = $sheet->getCell('E' . $row);
            $rawValue = $eCell->getValue();

            // 只處理 IF 公式的進階題目
            if ($rawValue && is_string($rawValue) && $this->isConditionalField($rawValue)) {
                $followUpText = $this->getCellValue($sheet, 'E', $row);
                if ($followUpText) {
                    // 移除結尾冒號和空白
                    $followUpText = rtrim($followUpText, ': ：');
                    // 解析 i18n
                    $i18n = $this->parseI18nText($followUpText);

                    $subIndex = $row - $startRow + 1;
                    $questions[] = [
                        'id' => $questionNo . '.' . $subIndex,
                        'text' => $i18n['zh'] ?: $i18n['en'],
                        'text_en' => $i18n['en'],
                        'text_zh' => $i18n['zh'],
                        'type' => 'TEXT',
                        'required' => false,
                    ];
                }
            }
        }

        return $questions;
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

        // 如果是公式
        if (is_string($value) && str_starts_with($value, '=')) {
            try {
                // 1. 嘗試計算
                $calculated = $cell->getCalculatedValue();

                // 檢查 YEAR 公式結果是否合理 (>2000)
                $isYearFormula = preg_match('/YEAR/i', $value);
                if ($isYearFormula) {
                    if (is_numeric($calculated) && (int)$calculated < 2000) {
                        throw new \Exception("Invalid calculated year");
                    }
                    return (string)$calculated;
                }

                // 對於 IF 公式，如果計算結果為空（條件不滿足），拋出異常以觸發手動解析
                if ($calculated === '' || $calculated === null || $calculated === false) {
                    throw new \Exception("Empty calculated value");
                }

                return (string)$calculated;
            } catch (\Exception $e) {
                // 2. 計算失敗或結果無效，嘗試手動解析

                // 處理年份公式 =YEAR(...)
                if (preg_match('/YEAR/i', $value)) {
                    $offset = 0;
                    if (preg_match('/-(\d+)/', $value, $matches)) {
                        $offset = (int)$matches[1];
                    }
                    return (string)(date('Y') - $offset);
                }

                // 處理 IF 公式 (提取最長引號字串)
                if (preg_match_all('/"([^"]+)"/', $value, $matches)) {
                    $candidates = $matches[1];
                    // 排序取最長的
                    usort($candidates, function ($a, $b) {
                        return strlen($b) - strlen($a);
                    });
                    if (!empty($candidates)) {
                        return $candidates[0];
                    }
                }

                // 回退到原始值
                return $value;
            }
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
    /**
     * 從 IF 公式中提取追問文字
     */
    protected function extractFollowUpText(string $formula): ?string
    {
        // 嘗試提取所有雙引號中的內容
        // 啟發式：通常顯示文字是公式中最長的字串，且非空
        if (preg_match_all('/"([^"]+)"/', $formula, $matches)) {
            $candidates = $matches[1];
            // 排序取最長的
            usort($candidates, function ($a, $b) {
                return strlen($b) - strlen($a);
            });
            if (!empty($candidates)) {
                return trim($candidates[0]);
            }
        }

        // Regfallback: 匹配 =IF(D7=1, "Content of...", "")
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
