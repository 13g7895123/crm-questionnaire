# Template v2.0 測試計劃

**版本**: 2.0.0  
**最後更新**: 2025-12-04  
**測試對象**: SAQ 問卷範本 v2.0 新功能

---

## 1. 測試環境準備

### 1.1 啟動服務

```bash
# 啟動所有服務
cd /home/jarvis/project/job/crm-questionnaire
docker-compose up -d

# 或使用開發腳本
./dev.sh
```

### 1.2 執行種子資料

```bash
cd backend
php spark db:seed CompleteSAQTemplateSeeder
```

**預期結果**：
- 建立範本 ID: 4
- 範本名稱: "SAQ 完整範本 v2.0"
- 包含 5 個區段 (A-E)
- 每個區段 1-2 個子區段
- 總共約 12 個問題

---

## 2. API 測試

### 2.1 取得範本結構

**端點**: `GET /api/v1/templates/4/structure`

**測試指令**:
```bash
curl -X GET "http://localhost:3001/api/v1/templates/4/structure" \
  -H "Content-Type: application/json" | jq '.'
```

**預期回應**:
```json
{
  "success": true,
  "data": {
    "templateId": 4,
    "hasV2Structure": true,
    "structure": {
      "includeBasicInfo": true,
      "sections": [
        {
          "id": "A",
          "order": 1,
          "title": "勞工 (Labor)",
          "subsections": [...]
        }
      ]
    }
  }
}
```

**驗證重點**:
- ✅ `hasV2Structure` 為 `true`
- ✅ 包含 5 個 sections (A-E)
- ✅ Section A 包含條件邏輯問題
- ✅ Section A 包含表格問題

---

### 2.2 取得基本資訊

**端點**: `GET /api/v1/project-suppliers/{projectSupplierId}/basic-info`

**前置條件**:
1. 建立一個使用範本 4 的專案
2. 指派供應商到專案（產生 project_supplier 記錄）

**測試指令**:
```bash
# 假設 project_supplier_id = 1
curl -X GET "http://localhost:3001/api/v1/project-suppliers/1/basic-info" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" | jq '.'
```

**預期回應（首次）**:
```json
{
  "success": true,
  "data": {
    "projectSupplierId": 1,
    "basicInfo": null
  }
}
```

---

### 2.3 儲存基本資訊

**端點**: `PUT /api/v1/project-suppliers/{projectSupplierId}/basic-info`

**測試指令**:
```bash
curl -X PUT "http://localhost:3001/api/v1/project-suppliers/1/basic-info" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "companyName": "測試供應商公司",
    "companyAddress": "台北市信義區信義路五段 7 號",
    "employees": {
      "total": 150,
      "male": 80,
      "female": 70,
      "foreign": 20
    },
    "facilities": [
      {
        "location": "台北工廠",
        "address": "新北市土城區工業路 123 號",
        "area": "5000",
        "type": "製造"
      }
    ],
    "certifications": ["ISO 9001", "ISO 14001"],
    "rbaOnlineMember": true,
    "contacts": [
      {
        "name": "張經理",
        "title": "品保經理",
        "department": "品質保證部",
        "email": "zhang@test.com",
        "phone": "02-1234-5678"
      }
    ]
  }' | jq '.'
```

**預期回應**:
```json
{
  "success": true,
  "data": {
    "projectSupplierId": 1,
    "saved": true,
    "savedAt": "2025-12-04T10:30:00.000Z"
  }
}
```

**驗證重點**:
- ✅ 資料成功儲存到 `project_basic_info` 表
- ✅ JSON 欄位正確儲存（employees, facilities, certifications, contacts）
- ✅ 再次查詢可以取回完整資料

---

### 2.4 儲存答案並測試條件邏輯

**端點**: `PUT /api/v1/project-suppliers/{projectSupplierId}/answers`

**測試指令 1 - 觸發條件邏輯**:
```bash
curl -X PUT "http://localhost:3001/api/v1/project-suppliers/1/answers" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "answers": {
      "A.1.1": {
        "questionId": "A.1.1",
        "value": true
      }
    }
  }' | jq '.'
```

**測試指令 2 - 查詢可見問題**:
```bash
curl -X GET "http://localhost:3001/api/v1/project-suppliers/1/visible-questions" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" | jq '.'
```

**預期回應**:
```json
{
  "success": true,
  "data": {
    "projectSupplierId": 1,
    "visibleQuestions": [
      "A.1.1",
      "A.1.1.1",  // <- 因為 A.1.1 = true 而顯示
      "A.1.2",
      "A.2.1",
      "A.2.2",
      "B.1.1",
      "B.1.2",
      "C.1.1",
      "C.1.2",
      "D.1.1",
      "D.1.2",
      "E.1.1",
      "E.1.2"
    ]
  }
}
```

**驗證重點**:
- ✅ A.1.1 回答 true 時，A.1.1.1 出現在可見清單中
- ✅ A.1.1 回答 false 時，A.1.1.1 不在可見清單中

---

### 2.5 表格答案測試

**測試指令**:
```bash
curl -X PUT "http://localhost:3001/api/v1/project-suppliers/1/answers" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "answers": {
      "A.2.2": {
        "questionId": "A.2.2",
        "value": [
          {
            "year": "2023",
            "count": "150",
            "department": "生產部",
            "remarks": "正常"
          },
          {
            "year": "2024",
            "count": "160",
            "department": "生產部",
            "remarks": "人員增加"
          },
          {
            "year": "2025",
            "count": "155",
            "department": "生產部",
            "remarks": "預估"
          }
        ]
      }
    }
  }' | jq '.'
```

**驗證重點**:
- ✅ 表格資料正確儲存為 JSON 陣列
- ✅ 每一列包含所有欄位（year, count, department, remarks）
- ✅ 資料型別正確（year=text, count=number）

---

### 2.6 計算分數

**測試指令**:
```bash
# 先填寫完整答案
curl -X PUT "http://localhost:3001/api/v1/project-suppliers/1/answers" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "answers": {
      "A.1.1": {"questionId": "A.1.1", "value": true},
      "A.1.1.1": {"questionId": "A.1.1.1", "value": "我們有明確的政策禁止強迫勞工"},
      "A.1.2": {"questionId": "A.1.2", "value": 5},
      "A.2.1": {"questionId": "A.2.1", "value": true},
      "A.2.2": {"questionId": "A.2.2", "value": [...]},
      "B.1.1": {"questionId": "B.1.1", "value": true},
      "B.1.2": {"questionId": "B.1.2", "value": "每季一次"},
      "B.1.2.1": {"questionId": "B.1.2.1", "value": "消防演練、地震演練"},
      "B.2.1": {"questionId": "B.2.1", "value": 100},
      "C.1.1": {"questionId": "C.1.1", "value": true},
      "C.1.2": {"questionId": "C.1.2", "value": 3},
      "D.1.1": {"questionId": "D.1.1", "value": true},
      "D.1.2": {"questionId": "D.1.2", "value": 5},
      "E.1.1": {"questionId": "E.1.1", "value": true},
      "E.1.2": {"questionId": "E.1.2", "value": 4}
    }
  }'

# 計算分數
curl -X POST "http://localhost:3001/api/v1/project-suppliers/1/calculate-score" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" | jq '.'
```

**預期回應**:
```json
{
  "success": true,
  "data": {
    "breakdown": {
      "A": {
        "sectionId": "A",
        "sectionName": "勞工 (Labor)",
        "score": 18,
        "maxScore": 20,
        "weight": 25,
        "answeredCount": 5,
        "totalCount": 5,
        "completionRate": 100
      },
      "B": {...},
      "C": {...},
      "D": {...},
      "E": {...}
    },
    "totalScore": 88,
    "grade": "良好",
    "calculatedAt": "2025-12-04T10:35:00.000Z"
  }
}
```

**驗證重點**:
- ✅ 各區段分數正確計算
- ✅ 總分在 0-100 範圍內
- ✅ 等級正確分配（優秀/良好/合格/待改進/不合格）
- ✅ 完成率正確（answeredCount / totalCount）

**分數計算邏輯**:
- BOOLEAN: true=1分, false=0分
- RATING: 數值標準化到 0-1（如 3/5 = 0.6）
- NUMBER: 正數=1分, 零或負數=0分
- 其他類型: 有回答=1分, 未回答=0分

---

### 2.7 驗證答案

**測試指令 1 - 不完整答案**:
```bash
curl -X POST "http://localhost:3001/api/v1/project-suppliers/1/validate" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" | jq '.'
```

**預期回應（驗證失敗）**:
```json
{
  "success": true,
  "data": {
    "projectSupplierId": 1,
    "valid": false,
    "errors": {
      "basicInfo": {
        "companyName": "公司名稱為必填欄位",
        "facilities": "至少需要提供一個設施資訊"
      },
      "missingRequired": [
        {
          "questionId": "A.1.1",
          "questionText": "貴公司是否禁止使用強迫勞工？"
        }
      ],
      "conditionalLogic": {
        "A.1.1.1": "當 A.1.1 回答「是」時，此問題為必填"
      }
    }
  }
}
```

**測試指令 2 - 完整答案**:
```bash
# 先補完所有必填資料
# ... (儲存基本資訊和所有必填答案)

curl -X POST "http://localhost:3001/api/v1/project-suppliers/1/validate" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" | jq '.'
```

**預期回應（驗證通過）**:
```json
{
  "success": true,
  "data": {
    "projectSupplierId": 1,
    "valid": true,
    "errors": {}
  }
}
```

**驗證重點**:
- ✅ 檢測缺少的必填問題
- ✅ 檢測條件邏輯觸發的必填問題
- ✅ 檢測基本資訊缺失
- ✅ 檢測表格行數不足
- ✅ 提供具體的錯誤訊息

---

## 3. 業務邏輯測試

### 3.1 條件邏輯引擎測試

**測試場景 1**: equals 運算子
```php
// 在 php spark 中測試
$engine = new \App\Libraries\ConditionalLogicEngine();

$condition = [
    'operator' => 'equals',
    'value' => true
];

$result = $engine->evaluateCondition(true, $condition);  // true
$result = $engine->evaluateCondition(false, $condition); // false
```

**測試場景 2**: contains 運算子
```php
$condition = [
    'operator' => 'contains',
    'value' => '每季'
];

$result = $engine->evaluateCondition('每季一次', $condition);  // true
$result = $engine->evaluateCondition('每月一次', $condition);  // false
```

**測試場景 3**: greaterThan 運算子
```php
$condition = [
    'operator' => 'greaterThan',
    'value' => 100
];

$result = $engine->evaluateCondition(150, $condition);  // true
$result = $engine->evaluateCondition(80, $condition);   // false
```

**驗證重點**:
- ✅ 所有 9 個運算子正確運作
- ✅ 支援不同資料型別（boolean, string, number, array）
- ✅ 邊界條件處理正確

---

### 3.2 評分引擎測試

**測試場景 1**: BOOLEAN 類型評分
```php
$scoringEngine = new \App\Libraries\ScoringEngine();

// 測試資料
$questions = [
    ['id' => 'q1', 'type' => 'BOOLEAN', 'config' => ['positiveAnswer' => true]],
    ['id' => 'q2', 'type' => 'BOOLEAN', 'config' => ['positiveAnswer' => true]],
    ['id' => 'q3', 'type' => 'BOOLEAN', 'config' => ['positiveAnswer' => false]]
];

$answers = [
    'q1' => ['value' => true],   // 正確答案，得分
    'q2' => ['value' => false],  // 錯誤答案，不得分
    'q3' => ['value' => false]   // 正確答案（negative case），得分
];

$sectionData = $scoringEngine->calculateSectionScore('A', $questions, $answers);
// 預期: score = 2, maxScore = 3
```

**測試場景 2**: RATING 類型評分
```php
$questions = [
    ['id' => 'q1', 'type' => 'RATING', 'config' => ['maxRating' => 5]]
];

$answers = [
    'q1' => ['value' => 4]  // 4/5 = 0.8 分
];

$sectionData = $scoringEngine->calculateSectionScore('B', $questions, $answers);
// 預期: score = 0.8, maxScore = 1
```

**驗證重點**:
- ✅ BOOLEAN 正確處理 positive/negative 答案
- ✅ RATING 正確標準化到 0-1
- ✅ NUMBER 正確判斷正負數
- ✅ 其他類型有答案即得分

---

### 3.3 答案驗證器測試

**測試場景 1**: 基本資訊驗證
```php
$validator = new \App\Libraries\AnswerValidator();

// 不完整的基本資訊
$basicInfo = [
    'companyName' => '',  // 缺少
    'employees' => [],    // 缺少
    'facilities' => [],   // 缺少
    'contacts' => []      // 缺少
];

$errors = $validator->validateBasicInfo($basicInfo);
// 預期: 包含 4 個錯誤訊息
```

**測試場景 2**: 表格答案驗證
```php
$tableConfig = [
    'minRows' => 3,
    'maxRows' => 10,
    'columns' => [
        ['id' => 'year', 'required' => true],
        ['id' => 'count', 'type' => 'number', 'required' => true]
    ]
];

// 不足的列數
$tableAnswer = [
    ['year' => '2023', 'count' => '100'],
    ['year' => '2024', 'count' => '110']
];

$errors = $validator->validateTableAnswer($tableAnswer, $tableConfig);
// 預期: "至少需要 3 筆資料"
```

**驗證重點**:
- ✅ 基本資訊必填欄位檢查
- ✅ Email 格式驗證
- ✅ 表格行數限制檢查
- ✅ 表格欄位必填檢查
- ✅ 資料型別驗證

---

## 4. 資料庫驗證

### 4.1 檢查範本結構

```sql
-- 檢查範本
SELECT * FROM templates WHERE id = 4;

-- 檢查區段
SELECT * FROM template_sections WHERE template_id = 4 ORDER BY `order`;

-- 檢查子區段
SELECT s.id, s.title, ss.id, ss.title
FROM template_sections s
LEFT JOIN template_subsections ss ON s.id = ss.section_id
WHERE s.template_id = 4
ORDER BY s.`order`, ss.`order`;

-- 檢查問題
SELECT q.id, q.text, q.type, q.required, q.conditional_logic
FROM template_questions q
JOIN template_subsections ss ON q.subsection_id = ss.id
JOIN template_sections s ON ss.section_id = s.id
WHERE s.template_id = 4
ORDER BY s.`order`, ss.`order`, q.`order`;
```

**驗證重點**:
- ✅ 範本存在且 `has_v2_structure` = 1
- ✅ 5 個區段按順序排列
- ✅ 每個區段有 1-2 個子區段
- ✅ 條件邏輯正確儲存為 JSON
- ✅ 表格配置正確儲存為 JSON

---

### 4.2 檢查基本資訊儲存

```sql
-- 檢查基本資訊表
SELECT * FROM project_basic_info WHERE project_supplier_id = 1;

-- 檢查 JSON 欄位格式
SELECT 
    company_name,
    JSON_PRETTY(employees) as employees,
    JSON_PRETTY(facilities) as facilities,
    JSON_PRETTY(certifications) as certifications,
    JSON_PRETTY(contacts) as contacts
FROM project_basic_info 
WHERE project_supplier_id = 1;
```

**驗證重點**:
- ✅ 所有 JSON 欄位格式正確
- ✅ 資料可以正確讀取和解析
- ✅ 更新操作正確覆蓋舊資料

---

### 4.3 檢查答案儲存

```sql
-- 檢查答案表
SELECT * FROM answers WHERE project_supplier_id = 1;

-- 檢查特定問題的答案
SELECT question_id, value, answer_type 
FROM answers 
WHERE project_supplier_id = 1 
  AND question_id = 'A.1.1';

-- 檢查表格答案
SELECT question_id, JSON_PRETTY(value) as table_data
FROM answers 
WHERE project_supplier_id = 1 
  AND question_id = 'A.2.2';
```

**驗證重點**:
- ✅ 答案正確關聯到 project_supplier_id
- ✅ question_id 使用新的 v2.0 格式（如 "A.1.1"）
- ✅ 表格答案儲存為 JSON 陣列
- ✅ answer_type 正確記錄類型

---

## 5. 整合測試流程

### 完整問卷填寫流程

1. **建立專案**
   ```bash
   # POST /api/v1/projects
   # 使用範本 ID = 4 (SAQ 完整範本 v2.0)
   ```

2. **指派供應商**
   ```bash
   # 將供應商加入專案
   # 產生 project_supplier 記錄
   ```

3. **取得範本結構**
   ```bash
   # GET /api/v1/templates/4/structure
   # 前端據此渲染問卷介面
   ```

4. **填寫基本資訊**
   ```bash
   # PUT /api/v1/project-suppliers/1/basic-info
   # 儲存公司基本資料
   ```

5. **填寫問題並觸發條件邏輯**
   ```bash
   # PUT /api/v1/project-suppliers/1/answers
   # 回答 A.1.1 = true
   
   # GET /api/v1/project-suppliers/1/visible-questions
   # 檢查 A.1.1.1 是否出現
   
   # PUT /api/v1/project-suppliers/1/answers
   # 填寫追問 A.1.1.1
   ```

6. **填寫表格問題**
   ```bash
   # PUT /api/v1/project-suppliers/1/answers
   # 回答 A.2.2，提供 3 筆表格資料
   ```

7. **計算分數**
   ```bash
   # POST /api/v1/project-suppliers/1/calculate-score
   # 查看當前得分和等級
   ```

8. **驗證答案**
   ```bash
   # POST /api/v1/project-suppliers/1/validate
   # 確認所有必填項目已完成
   ```

9. **提交問卷**
   ```bash
   # POST /api/v1/project-suppliers/1/submit
   # 將狀態改為 SUBMITTED
   ```

---

## 6. 效能測試

### 6.1 條件邏輯計算效能

**測試**: 100 個問題，20 個條件邏輯規則

```bash
ab -n 100 -c 10 \
  -H "Authorization: Bearer {token}" \
  "http://localhost:3001/api/v1/project-suppliers/1/visible-questions"
```

**預期**: 平均回應時間 < 200ms

---

### 6.2 分數計算效能

**測試**: 50 個已回答的問題

```bash
ab -n 100 -c 10 \
  -H "Authorization: Bearer {token}" \
  -X POST \
  "http://localhost:3001/api/v1/project-suppliers/1/calculate-score"
```

**預期**: 平均回應時間 < 300ms

---

## 7. 錯誤處理測試

### 7.1 無效的條件邏輯

**測試**: 提供不支援的運算子

```json
{
  "operator": "invalid_operator",
  "value": true
}
```

**預期**: 回傳錯誤訊息或預設為 false

---

### 7.2 表格資料驗證

**測試**: 提供超過 maxRows 的資料

```json
{
  "questionId": "A.2.2",
  "value": [
    // 11 筆資料（超過 maxRows: 10）
  ]
}
```

**預期**: 驗證失敗，回傳錯誤訊息

---

## 8. 測試檢查清單

### Phase 3 - 業務邏輯層
- [ ] ScoringEngine 計算分數正確
- [ ] ScoringEngine 分配等級正確
- [ ] ConditionalLogicEngine 評估所有運算子
- [ ] ConditionalLogicEngine 遞迴處理追問
- [ ] AnswerValidator 驗證基本資訊
- [ ] AnswerValidator 驗證表格答案
- [ ] AnswerValidator 驗證條件邏輯
- [ ] AnswerValidator 驗證必填欄位

### Phase 4 - 測試資料
- [ ] CompleteSAQTemplateSeeder 成功執行
- [ ] 範本包含 5 個區段
- [ ] 條件邏輯範例正常運作
- [ ] 表格問題範例正常運作

### Phase 5 - 整合與文件
- [ ] TypeScript 型別定義完整
- [ ] API 文件更新完成
- [ ] 前端整合指南完整
- [ ] 測試計劃文件完整

### API 端點測試
- [ ] GET /templates/{id}/structure
- [ ] GET /project-suppliers/{id}/basic-info
- [ ] PUT /project-suppliers/{id}/basic-info
- [ ] GET /project-suppliers/{id}/visible-questions
- [ ] POST /project-suppliers/{id}/calculate-score
- [ ] POST /project-suppliers/{id}/validate

### 資料庫測試
- [ ] templates 表正確儲存
- [ ] template_sections 表正確儲存
- [ ] template_subsections 表正確儲存
- [ ] template_questions 表正確儲存
- [ ] project_basic_info 表正確儲存
- [ ] answers 表正確儲存
- [ ] JSON 欄位格式正確

---

**測試負責人**: 開發團隊  
**測試時程**: Phase 5 完成後執行  
**測試環境**: 開發環境 (Docker)
