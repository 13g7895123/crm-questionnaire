# Excel 匯入功能技術文件

本文件詳述 SAQ 範本系統的 Excel 匯入功能實作細節，包含 API 端點、解析邏輯、資料庫結構與錯誤處理。

## 目錄

1. [API 端點](#api-端點)
2. [ExcelQuestionParser 解析器](#excelquestionparser-解析器)
3. [資料庫結構](#資料庫結構)
4. [錯誤處理](#錯誤處理)
5. [使用範例](#使用範例)

---

## API 端點

### 1. 測試 Excel 解析（Test Excel）

```http
POST /api/v1/templates/test-excel
Content-Type: multipart/form-data
```

**用途**: 測試 Excel 檔案格式，回傳解析結果但不儲存。

**Request Body**:
- `file`: Excel 檔案 (.xlsx, .xls)

**Response**:
```json
{
  "success": true,
  "data": {
    "fileName": "template.xlsx",
    "sections": [...],
    "metadata": {
      "totalSections": 5,
      "totalSubsections": 23,
      "totalQuestions": 156
    }
  }
}
```

**錯誤回應**:
```json
{
  "success": false,
  "error": {
    "code": "EXCEL_PARSE_ERROR",
    "message": "解析 Excel 檔案失敗：..."
  },
  "timestamp": "2025-12-16T07:06:54+00:00"
}
```

---

### 2. 匯入 Excel 至範本（Import Excel）

```http
POST /api/v1/templates/{id}/import-excel
Content-Type: multipart/form-data
```

**用途**: 將 Excel 解析結果匯入至指定範本。

**Request Body**:
- `file`: Excel 檔案 (.xlsx, .xls)

**Response**:
```json
{
  "success": true,
  "data": {
    "message": "Excel 匯入成功",
    "templateId": 18,
    "metadata": {
      "totalSections": 5,
      "totalSubsections": 23,
      "totalQuestions": 156
    }
  }
}
```

**錯誤回應**:
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "資料驗證失敗",
    "details": {
      "file": "未找到符合格式的分頁，請確保分頁名稱以 A.、B.、C. 等格式開頭"
    }
  }
}
```

---

## ExcelQuestionParser 解析器

### 類別位置
`app/Libraries/ExcelQuestionParser.php`

### 主要方法

#### `parse(Spreadsheet $spreadsheet): array`

解析整個 Excel 檔案並返回結構化資料。

**回傳格式**:
```php
[
    'sections' => [
        [
            'id' => 'A',
            'order' => 1,
            'title' => 'A. Labor Rights',
            'subsections' => [
                [
                    'id' => 'A.1',
                    'order' => 1,
                    'title' => 'A.1. Freely Chosen Employment',
                    'questions' => [
                        [
                            'id' => 'A.1.1',
                            'order' => 1,
                            'text' => '是否有強制勞動?',
                            'type' => 'BOOLEAN',
                            'required' => true,
                            'conditionalLogic' => [...] // 若為表格題
                        ]
                    ]
                ]
            ]
        ]
    ],
    'metadata' => [
        'totalSections' => 5,
        'totalSubsections' => 23,
        'totalQuestions' => 156
    ]
]
```

### 核心解析邏輯

#### 1. 分頁識別 (Sheet Identification)

```php
protected function extractSectionId(string $sheetName): ?string
{
    if (preg_match('/^([A-Z])\./', $sheetName, $matches)) {
        return $matches[1];
    }
    return null;
}
```

- 匹配分頁名稱開頭的 `A.`, `B.`, `C.` 等格式
- 提取大寫字母作為區段 ID

#### 2. 編號格式判定

**區段標題** (`isSectionTitle`):
```php
protected function isSectionTitle(?string $no, string $sectionId): bool
{
    // 匹配 "A. Labor Rights" 格式
    return preg_match('/^' . preg_quote($sectionId, '/') . '\.\s+\w/i', $no) === 1;
}
```

**小標題** (`isSubsectionTitle`):
```php
protected function isSubsectionTitle(?string $no, string $sectionId): bool
{
    // 排除題目格式 (X.n.n)
    if (preg_match('/^' . preg_quote($sectionId, '/') . '\.\d+\.\d+/i', $no)) {
        return false;
    }
    // 匹配 "A.1." 或 "A.1. xxx" 格式
    return preg_match('/^' . preg_quote($sectionId, '/') . '\.\d+\.\s*\w/i', $no) === 1;
}
```

**題目** (`isQuestionNo`):
```php
protected function isQuestionNo(?string $no, string $sectionId): bool
{
    // 匹配 "A.1.1" 格式（結尾為數字）
    return preg_match('/^' . preg_quote($sectionId, '/') . '\.\d+\.\d+$/i', $no) === 1;
}
```

#### 3. 表格題判定

```php
protected function parseQuestion(Worksheet $sheet, int $startRow, string $no, ?string $item, int $order): array
{
    // 檢查 B 欄是否有合併儲存格
    $mergeRange = $this->getMergeRange($sheet, 'B', $startRow);
    $isTableQuestion = $mergeRange !== null;

    if ($isTableQuestion) {
        // 解析為表格題型
        $tableData = $this->parseTableQuestion($sheet, $startRow, $mergeRange);
        
        return [
            'question' => [
                'id' => $no,
                'type' => 'BOOLEAN',
                'text' => $item,
                'required' => true,
                'conditionalLogic' => [
                    'followUpQuestions' => [
                        [
                            'condition' => ['operator' => 'equals', 'value' => true],
                            'questions' => [
                                [
                                    'id' => $no . '.table',
                                    'text' => '詳細資料',
                                    'type' => 'TABLE',
                                    'required' => true,
                                    'tableConfig' => $tableData['tableConfig']
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'nextRow' => $tableData['endRow'] + 1
        ];
    }
    
    // 一般是非題
    return [
        'question' => [
            'id' => $no,
            'type' => 'BOOLEAN',
            'text' => $item,
            'required' => true
        ],
        'nextRow' => $startRow + 1
    ];
}
```

#### 4. 表格欄位解析

```php
protected function parseTableQuestion(Worksheet $sheet, int $startRow, array $mergeRange): array
{
    $endRow = $mergeRange['endRow'];
    $columns = [];
    $rowLabels = [];

    // 從下一列開始解析表格標題
    $headerRow = $startRow + 1;
    
    // 讀取 D, E, F... 欄位作為表格欄位
    foreach (['D', 'E', 'F', 'G', 'H'] as $col) {
        $value = $this->getCellValue($sheet, $col, $headerRow);
        if ($value) {
            // 處理 IF 公式
            if ($this->isConditionalField($value)) {
                $value = $this->extractFollowUpText($value);
            }
            $columns[] = $value;
        }
    }

    // 讀取 E 欄的列標籤（若有 IF 公式）
    for ($row = $headerRow + 1; $row <= $endRow; $row++) {
        $label = $this->getCellValue($sheet, 'E', $row);
        if ($label && $this->isConditionalField($label)) {
            $rowLabels[] = $this->extractFollowUpText($label);
        }
    }

    // 轉換為 TableColumn 格式
    $tableColumns = [
        ['id' => 'row_label', 'label' => '項目', 'type' => 'text', 'required' => true]
    ];
    
    foreach ($columns as $idx => $colLabel) {
        $tableColumns[] = [
            'id' => 'col_' . ($idx + 1),
            'label' => (string) $colLabel,
            'type' => 'text',
            'required' => false
        ];
    }

    return [
        'endRow' => $endRow,
        'tableConfig' => [
            'columns' => $tableColumns,
            'minRows' => count($rowLabels),
            'maxRows' => count($rowLabels)
        ]
    ];
}
```

---

## 資料庫結構

### v2.0 分離式結構

Excel 匯入後，資料儲存至以下三個資料表：

#### 1. `template_sections`

| 欄位 | 類型 | 說明 |
|------|------|------|
| id | INT AUTO_INCREMENT | Primary Key |
| template_id | INT | 範本 ID (FK) |
| section_id | VARCHAR(10) | 區段代號 (e.g., 'A', 'B') |
| order | INT | 排序 |
| title | VARCHAR(200) | 區段標題 |
| description | TEXT | 描述（可選） |
| created_at | TIMESTAMP | 建立時間 |
| updated_at | TIMESTAMP | 更新時間 |

**唯一鍵**: `(template_id, section_id)`

#### 2. `template_subsections`

| 欄位 | 類型 | 說明 |
|------|------|------|
| id | INT AUTO_INCREMENT | Primary Key |
| section_id | INT | 區段 ID (FK) |
| subsection_id | VARCHAR(20) | 小標題代號 (e.g., 'A.1') |
| order | INT | 排序 |
| title | VARCHAR(200) | 小標題 |
| description | TEXT | 描述（可選） |
| created_at | TIMESTAMP | 建立時間 |
| updated_at | TIMESTAMP | 更新時間 |

**唯一鍵**: `(section_id, subsection_id)`

#### 3. `template_questions`

| 欄位 | 類型 | 說明 |
|------|------|------|
| id | INT AUTO_INCREMENT | Primary Key |
| subsection_id | INT | 小標題 ID (FK) |
| question_id | VARCHAR(50) | 題目代號 (e.g., 'A.1.1') |
| order | INT | 排序 |
| text | TEXT | 題目文字 |
| type | ENUM | 題型 (BOOLEAN, TEXT, TABLE 等) |
| required | TINYINT(1) | 是否必填 |
| config | JSON | 選項設定（可選） |
| conditional_logic | JSON | 條件邏輯（可選） |
| table_config | JSON | 表格設定（可選） |
| created_at | TIMESTAMP | 建立時間 |
| updated_at | TIMESTAMP | 更新時間 |

**唯一鍵**: `(subsection_id, question_id)`

### 儲存邏輯

`TemplateStructureRepository::saveTemplateStructure()` 負責將解析結果儲存至資料庫：

```php
public function saveTemplateStructure(int $templateId, array $sections): bool
{
    $db = \Config\Database::connect();
    $db->transStart();

    try {
        // 1. 刪除舊結構
        $this->sectionModel->deleteSectionsByTemplateId($templateId);

        // 2. 插入新結構（三層巢狀）
        foreach ($sections as $sectionIndex => $sectionData) {
            // 插入 Section
            $sectionId = $this->sectionModel->insert([...]);
            
            foreach ($sectionData['subsections'] as $subsectionIndex => $subsectionData) {
                // 插入 Subsection
                $subsectionId = $this->subsectionModel->insert([...]);
                
                foreach ($subsectionData['questions'] as $questionIndex => $questionData) {
                    // 插入 Question
                    $inserted = $this->questionModel->insert([...]);
                }
            }
        }

        $db->transComplete();
        return $db->transStatus();
    } catch (\Exception $e) {
        $db->transRollback();
        log_message('error', 'Failed to save template structure: ' . $e->getMessage());
        return false;
    }
}
```

---

## 錯誤處理

### 1. 常見錯誤類型

| 錯誤代碼 | 說明 | 原因 |
|---------|------|------|
| `VALIDATION_ERROR` | 驗證失敗 | 檔案格式錯誤、未找到符合分頁 |
| `EXCEL_PARSE_ERROR` | 解析失敗 | PhpSpreadsheet 讀取錯誤 |
| `EXCEL_IMPORT_ERROR` | 匯入失敗 | 解析成功但儲存失敗 |
| `INTERNAL_ERROR` | 內部錯誤 | 資料庫儲存失敗 |

### 2. 詳細錯誤日誌

所有錯誤會記錄至 CodeIgniter 日誌：

```php
log_message('error', 'Failed to insert section: ' . json_encode($errors) . 
            ' Data: ' . json_encode($sectionInsertData));
```

**日誌位置**: `writable/logs/log-{date}.log`

**查看方式**:
```bash
docker logs crm_backend --tail 100
# 或
docker exec crm_backend cat /var/www/html/writable/logs/log-$(date +%Y-%m-%d).log
```

### 3. Duplicate Entry 錯誤

**原因**: 同一個 section 下有重複的 subsection_id。

**解決方案**: 
- 檢查 Excel 編號格式
- 確認解析器的 `isSubsectionTitle` 邏輯正確排除題目格式

**範例錯誤**:
```
ERROR - 2025-12-16 07:06:54 --> mysqli_sql_exception: Duplicate entry '17-C.2' 
for key 'section_id_subsection_id'

Failed to insert subsection: {"CodeIgniter\\Database\\MySQLi\\Connection":
"Duplicate entry '17-C.2' for key 'section_id_subsection_id'"} 
Data: {"section_id":17,"subsection_id":"C.2","order":3,"title":"C.2.4.1"}
```

**修正**: Title `C.2.4.1` 應該被識別為題目 (question)，而非小標題。

---

## 使用範例

### 1. 前端呼叫測試 API

```javascript
const testExcelImport = async (file) => {
  const formData = new FormData()
  formData.append('file', file)

  const response = await fetch(`${apiBase}/api/v1/templates/test-excel`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`
    },
    body: formData
  })

  const result = await response.json()
  
  if (result.success) {
    console.log('解析成功:', result.data.sections)
    console.log('統計:', result.data.metadata)
  } else {
    console.error('解析失敗:', result.error.message)
  }
}
```

### 2. 前端呼叫匯入 API

```javascript
const importExcel = async (templateId, file) => {
  const formData = new FormData()
  formData.append('file', file)

  const response = await fetch(
    `${apiBase}/api/v1/templates/${templateId}/import-excel`,
    {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`
      },
      body: formData
    }
  )

  const result = await response.json()
  
  if (result.success) {
    console.log('匯入成功!')
  } else {
    console.error('匯入失敗:', result.error.message)
  }
}
```

### 3. 後端直接呼叫解析器

```php
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Libraries\ExcelQuestionParser;

$spreadsheet = IOFactory::load($filePath);
$parser = new ExcelQuestionParser();
$result = $parser->parse($spreadsheet);

// 檢查結果
if (empty($result['sections'])) {
    throw new \RuntimeException('未找到符合格式的分頁');
}

// 儲存至資料庫
$repo = new \App\Repositories\TemplateStructureRepository();
$saved = $repo->saveTemplateStructure($templateId, $result['sections']);

if (!$saved) {
    throw new \RuntimeException('儲存範本結構失敗');
}
```

---

## 附錄

### A. 支援的題型

| 類型 | 說明 | 判定方式 |
|------|------|----------|
| `BOOLEAN` | 是非題 | 預設類型 |
| `TABLE` | 表格題 | B 欄有合併儲存格 |
| `TEXT` | 文字題 | 未實作（保留） |
| `NUMBER` | 數字題 | 未實作（保留） |
| `RADIO` | 單選題 | 未實作（保留） |
| `CHECKBOX` | 多選題 | 未實作（保留） |
| `SELECT` | 下拉選單 | 未實作（保留） |
| `DATE` | 日期 | 未實作（保留） |
| `FILE` | 檔案上傳 | 未實作（保留） |

### B. 相關檔案清單

**後端**:
- `app/Libraries/ExcelQuestionParser.php` - 解析器核心
- `app/Controllers/Api/V1/TemplateController.php` - API 端點
- `app/Repositories/TemplateStructureRepository.php` - 儲存邏輯
- `app/Models/TemplateSectionModel.php` - Section Model
- `app/Models/TemplateSubsectionModel.php` - Subsection Model
- `app/Models/TemplateQuestionModel.php` - Question Model

**前端**:
- `pages/saq/templates/index.vue` - 範本管理頁面
- `docs/EXCEL_IMPORT_RULES.md` - 使用者指南
