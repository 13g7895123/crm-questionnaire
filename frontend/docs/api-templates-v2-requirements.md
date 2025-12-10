# 範本管理 API v2.0 需求文件

**版本**: 2.0.0  
**日期**: 2025-12-09  
**狀態**: 🟡 待實作  
**負責人**: Backend Team  
**相關文件**: 
- `frontend/docs/api-templates.md` (v1.0 現有規格)
- `backend/docs/API-SPECIFICATION.md`
- `TEMPLATE-DEMO-README.md` (前端 Demo 說明)

---

## 📋 文件目的

本文件定義範本管理 API v2.0 的擴展需求，主要目標是支援：
1. **多步驟問卷結構**：支援分段（sections）、小節（subsections）的階層式問卷
2. **條件邏輯**：支援根據答案顯示後續問題
3. **表格題型**：支援二維資料輸入（如歷史紀錄、統計表格）
4. **公司基本資料**：SAQ 問卷的固定第一步（所有範本共用）
5. **五面向評估**：支援 A-E 五個評估面向的結構化問卷

---

## 🎯 整體架構變更

### 當前架構 (v1.0)
```json
{
  "id": "tmpl_001",
  "name": "SAQ 標準範本",
  "type": "SAQ",
  "questions": [
    {
      "id": "q_001",
      "text": "貴公司是否具有 ISO 9001 認證？",
      "type": "BOOLEAN",
      "required": true
    }
  ]
}
```

### 新架構 (v2.0)
```json
{
  "id": "tmpl_001",
  "name": "SAQ 標準範本",
  "type": "SAQ",
  "latestVersion": "2.0.0",
  "structure": {
    "includeBasicInfo": true,
    "sections": [
      {
        "id": "A",
        "order": 1,
        "title": "A. 勞工 (Labor)",
        "description": "勞工權益與工作條件評估",
        "subsections": [
          {
            "id": "A.1",
            "order": 1,
            "title": "A.1 勞動管理",
            "questions": [
              {
                "id": "q_001",
                "order": 1,
                "text": "貴公司是否有制定並執行勞動政策？",
                "type": "BOOLEAN",
                "required": true,
                "conditionalLogic": {
                  "showWhen": null,
                  "followUpQuestions": [
                    {
                      "condition": { "operator": "equals", "value": true },
                      "questions": [
                        {
                          "id": "q_001_1",
                          "text": "請描述貴公司的勞動政策內容",
                          "type": "TEXT",
                          "required": false,
                          "config": { "maxLength": 500 }
                        }
                      ]
                    }
                  ]
                }
              }
            ]
          }
        ]
      }
    ]
  }
}
```

---

## 📊 資料結構定義

### 1. Template (範本) - 擴展

```typescript
interface Template {
  // === 現有欄位 (保持不變) ===
  id: string
  name: string
  type: 'SAQ' | 'CONFLICT'
  latestVersion: string
  createdAt: string
  updatedAt: string
  
  // === 新增欄位 ===
  structure: TemplateStructure  // 階層式結構
  scoring?: ScoringConfig       // 計分設定 (optional)
  
  // === 保留但標記為 deprecated ===
  questions?: Question[]        // 向下相容，建議使用 structure
}
```

### 2. TemplateStructure (範本結構)

```typescript
interface TemplateStructure {
  includeBasicInfo: boolean           // 是否包含公司基本資料（第一步）
  sections: TemplateSection[]         // 評估面向陣列
  totalSteps?: number                 // 總步驟數（自動計算）
}
```

### 3. TemplateSection (評估面向)

```typescript
interface TemplateSection {
  id: string                    // 面向 ID (A, B, C, D, E)
  order: number                 // 排序 (1-5)
  title: string                 // 標題 "A. 勞工 (Labor)"
  description?: string          // 描述
  subsections: TemplateSubsection[]  // 小節陣列
}
```

### 4. TemplateSubsection (小節)

```typescript
interface TemplateSubsection {
  id: string                    // 小節 ID (A.1, A.2, B.1, ...)
  order: number                 // 排序
  title: string                 // 標題 "A.1 勞動管理"
  description?: string          // 描述
  questions: TemplateQuestion[] // 題目陣列
}
```

### 5. TemplateQuestion (題目) - 擴展

```typescript
interface TemplateQuestion {
  // === 現有欄位 ===
  id: string
  text: string
  type: QuestionType
  required: boolean
  options?: string[]
  config?: QuestionConfig
  
  // === 新增欄位 ===
  order: number                      // 題目順序
  conditionalLogic?: ConditionalLogic  // 條件邏輯
  tableConfig?: TableConfig          // 表格設定 (當 type 為 TABLE 時)
}
```

### 6. ConditionalLogic (條件邏輯)

```typescript
interface ConditionalLogic {
  showWhen?: Condition                     // 此題顯示的條件
  followUpQuestions?: FollowUpRule[]       // 後續問題規則
}

interface Condition {
  questionId?: string           // 依賴的題目 ID
  operator: 'equals' | 'notEquals' | 'contains' | 'greaterThan' | 'lessThan'
  value: any                    // 比對值
}

interface FollowUpRule {
  condition: Condition          // 觸發條件
  questions: TemplateQuestion[] // 要顯示的後續問題
}
```

### 7. TableConfig (表格設定)

```typescript
interface TableConfig {
  columns: TableColumn[]        // 欄位定義
  rows: TableRow[]              // 列定義
  allowAddRow?: boolean         // 是否允許新增列
  minRows?: number              // 最少列數
  maxRows?: number              // 最多列數
}

interface TableColumn {
  id: string                    // 欄位 ID
  label: string                 // 欄位標題
  type: 'text' | 'number' | 'date' | 'select'
  width?: string                // 欄位寬度 (如 "200px", "20%")
  required?: boolean
  options?: string[]            // 當 type 為 select 時的選項
}

interface TableRow {
  id: string                    // 列 ID
  label: string                 // 列標題 (顯示在第一欄)
  fixed?: boolean               // 是否固定列（不可刪除）
}
```

### 8. QuestionType (題型) - 擴展

```typescript
type QuestionType = 
  | 'TEXT'           // 文字題
  | 'NUMBER'         // 數字題
  | 'DATE'           // 日期題
  | 'BOOLEAN'        // 是非題
  | 'SINGLE_CHOICE'  // 單選題
  | 'MULTI_CHOICE'   // 複選題
  | 'FILE'           // 檔案上傳
  | 'RATING'         // 評分題
  | 'TABLE'          // 🆕 表格題
```

### 9. ScoringConfig (計分設定) - Optional

```typescript
interface ScoringConfig {
  method: 'WEIGHTED' | 'BOOLEAN_COUNT' | 'RATING_AVERAGE' | 'CUSTOM'
  sections: {
    [sectionId: string]: SectionScoring
  }
}

interface SectionScoring {
  weight: number                  // 權重 (0-1)
  scoringRules: ScoringRule[]     // 計分規則
}

interface ScoringRule {
  questionIds: string[]           // 參與計分的題目 IDs
  method: 'BOOLEAN' | 'RATING' | 'CHOICE_MAPPING'
  choiceScores?: { [choice: string]: number }  // 選項對應分數
}
```

### 10. CompanyBasicInfo (公司基本資料) - 固定結構

```typescript
interface CompanyBasicInfo {
  company: {
    fullName: string              // 公司全名
    address: string               // 公司地址
    totalRevenue: number          // 總營收 (USD)
  }
  facilities: Facility[]          // 廠區資訊（可多個）
  contacts: Contact[]             // 聯絡人（可多位）
}

interface Facility {
  name: string                    // 廠區名稱
  address: string                 // 廠區地址
  employees: {
    localMale: number             // 本國籍員工-男
    localFemale: number           // 本國籍員工-女
    foreignMale: number           // 外國籍員工-男
    foreignFemale: number         // 外國籍員工-女
  }
  servicesProducts: string        // 提供的服務/產品
  certifications: string[]        // 管理系統認證
  rbaOnline: 'registered' | 'not_registered' | 'planning'  // RBA-Online 狀態
}

interface Contact {
  name: string                    // 聯絡人姓名
  title: string                   // 職稱
  email: string                   // Email
}
```

---

## 🔧 API 端點變更

### 7.1 取得範本列表
**現有**: `GET /api/v1/templates`  
**變更**: 無需變更，回傳格式擴展

**Response 擴展**:
```json
{
  "success": true,
  "data": [
    {
      "id": "tmpl_001",
      "name": "SAQ 標準範本",
      "type": "SAQ",
      "latestVersion": "2.0.0",
      "structureVersion": "2.0",        // 🆕 結構版本
      "sectionCount": 5,                // 🆕 面向數量
      "totalQuestions": 85,             // 🆕 總題數
      "createdAt": "2025-01-01T00:00:00.000Z",
      "updatedAt": "2025-12-09T00:00:00.000Z"
    }
  ]
}
```

---

### 7.2 取得範本詳情
**現有**: `GET /api/v1/templates/{templateId}`  
**變更**: Response 結構大幅擴展

**Response v2.0**:
```json
{
  "success": true,
  "data": {
    "id": "tmpl_001",
    "name": "2025 SAQ 供應商評估範本",
    "type": "SAQ",
    "latestVersion": "2.0.0",
    "structure": {
      "includeBasicInfo": true,
      "sections": [
        {
          "id": "A",
          "order": 1,
          "title": "A. 勞工 (Labor)",
          "description": "評估勞工權益、工作條件與管理制度",
          "subsections": [
            {
              "id": "A.1",
              "order": 1,
              "title": "A.1 勞動管理",
              "questions": [
                {
                  "id": "q_a01_001",
                  "order": 1,
                  "text": "貴公司是否有制定並執行勞動政策？",
                  "type": "BOOLEAN",
                  "required": true,
                  "conditionalLogic": {
                    "followUpQuestions": [
                      {
                        "condition": {
                          "operator": "equals",
                          "value": true
                        },
                        "questions": [
                          {
                            "id": "q_a01_001_1",
                            "order": 1,
                            "text": "請描述貴公司的勞動政策內容",
                            "type": "TEXT",
                            "required": false,
                            "config": {
                              "maxLength": 500
                            }
                          }
                        ]
                      }
                    ]
                  }
                },
                {
                  "id": "q_a01_002",
                  "order": 2,
                  "text": "過去三年是否有違反勞動法規的紀錄？",
                  "type": "BOOLEAN",
                  "required": true,
                  "conditionalLogic": {
                    "followUpQuestions": [
                      {
                        "condition": {
                          "operator": "equals",
                          "value": true
                        },
                        "questions": [
                          {
                            "id": "q_a01_002_1",
                            "order": 1,
                            "text": "違規詳情",
                            "type": "TABLE",
                            "required": true,
                            "tableConfig": {
                              "columns": [
                                {
                                  "id": "col_year",
                                  "label": "年度",
                                  "type": "text",
                                  "width": "100px",
                                  "required": true
                                },
                                {
                                  "id": "col_count",
                                  "label": "違犯件數",
                                  "type": "number",
                                  "width": "120px",
                                  "required": true
                                },
                                {
                                  "id": "col_amount",
                                  "label": "金額(USD)",
                                  "type": "number",
                                  "width": "150px",
                                  "required": true
                                },
                                {
                                  "id": "col_issue",
                                  "label": "違犯事項",
                                  "type": "text",
                                  "width": "200px",
                                  "required": true
                                },
                                {
                                  "id": "col_action",
                                  "label": "改善措施",
                                  "type": "text",
                                  "width": "200px",
                                  "required": true
                                }
                              ],
                              "rows": [
                                {
                                  "id": "row_2023",
                                  "label": "2023",
                                  "fixed": true
                                },
                                {
                                  "id": "row_2024",
                                  "label": "2024",
                                  "fixed": true
                                },
                                {
                                  "id": "row_2025",
                                  "label": "2025",
                                  "fixed": true
                                }
                              ],
                              "allowAddRow": false,
                              "minRows": 3,
                              "maxRows": 3
                            }
                          }
                        ]
                      }
                    ]
                  }
                }
              ]
            },
            {
              "id": "A.2",
              "order": 2,
              "title": "A.2 工作時間",
              "questions": [
                {
                  "id": "q_a02_001",
                  "order": 1,
                  "text": "每週工作時數是否符合當地法規？",
                  "type": "BOOLEAN",
                  "required": true
                }
              ]
            }
          ]
        },
        {
          "id": "B",
          "order": 2,
          "title": "B. 健康與安全 (Health & Safety)",
          "subsections": [...]
        },
        {
          "id": "C",
          "order": 3,
          "title": "C. 環境 (Environment)",
          "subsections": [...]
        },
        {
          "id": "D",
          "order": 4,
          "title": "D. 道德規範 (Ethics)",
          "subsections": [...]
        },
        {
          "id": "E",
          "order": 5,
          "title": "E. 管理系統 (Management System)",
          "subsections": [...]
        }
      ],
      "totalSteps": 7
    },
    "scoring": {
      "method": "BOOLEAN_COUNT",
      "sections": {
        "A": {
          "weight": 0.2,
          "scoringRules": [
            {
              "questionIds": ["q_a01_001", "q_a01_002", "q_a02_001"],
              "method": "BOOLEAN"
            }
          ]
        },
        "B": { "weight": 0.2, "scoringRules": [...] },
        "C": { "weight": 0.2, "scoringRules": [...] },
        "D": { "weight": 0.2, "scoringRules": [...] },
        "E": { "weight": 0.2, "scoringRules": [...] }
      }
    },
    "createdAt": "2025-01-01T00:00:00.000Z",
    "updatedAt": "2025-12-09T00:00:00.000Z"
  }
}
```

---

### 7.3 建立範本
**現有**: `POST /api/v1/templates`  
**變更**: Request Body 支援新結構

**Request Body v2.0**:
```json
{
  "name": "2025 SAQ 供應商評估範本",
  "type": "SAQ",
  "structure": {
    "includeBasicInfo": true,
    "sections": [
      {
        "id": "A",
        "order": 1,
        "title": "A. 勞工 (Labor)",
        "description": "評估勞工權益與工作條件",
        "subsections": [
          {
            "id": "A.1",
            "order": 1,
            "title": "A.1 勞動管理",
            "questions": [...]
          }
        ]
      }
    ]
  },
  "scoring": {
    "method": "BOOLEAN_COUNT",
    "sections": {...}
  }
}
```

**向下相容**:
如果 Request Body 仍使用舊的 `questions` 陣列格式，後端應：
1. 接受並正常處理
2. 自動轉換為 `structure` 格式儲存
3. 標記為 `structureVersion: "1.0"`

---

### 7.4 更新範本
**現有**: `PUT /api/v1/templates/{templateId}`  
**變更**: 支援更新 `structure` 和 `scoring`

**Request Body**: 同 7.3

---

### 🆕 7.8 取得公司基本資料結構
**新增**: `GET /api/v1/templates/basic-info-structure`  
**權限**: 需要認證 (HOST, SUPPLIER)  
**用途**: 取得 SAQ 問卷第一步的固定表單結構

**Response**:
```json
{
  "success": true,
  "data": {
    "version": "1.0.0",
    "structure": {
      "sections": [
        {
          "id": "company_info",
          "title": "公司資訊",
          "fields": [
            {
              "id": "company_full_name",
              "label": "公司全名",
              "type": "text",
              "required": true,
              "validation": {
                "maxLength": 200
              }
            },
            {
              "id": "company_address",
              "label": "公司地址",
              "type": "textarea",
              "required": true,
              "validation": {
                "maxLength": 500
              }
            },
            {
              "id": "total_revenue",
              "label": "公司總營收 (USD)",
              "type": "number",
              "required": true,
              "validation": {
                "min": 0
              }
            }
          ]
        },
        {
          "id": "facility_info",
          "title": "廠區資訊",
          "repeatable": true,
          "fields": [
            {
              "id": "facility_name",
              "label": "製造廠區全名",
              "type": "text",
              "required": true
            },
            {
              "id": "facility_address",
              "label": "製造廠區地址",
              "type": "textarea",
              "required": true
            },
            {
              "id": "employees",
              "label": "廠區員工人數（全職員工）",
              "type": "group",
              "fields": [
                {
                  "id": "local_male",
                  "label": "本國籍員工-男",
                  "type": "number",
                  "required": true,
                  "validation": { "min": 0 }
                },
                {
                  "id": "local_female",
                  "label": "本國籍員工-女",
                  "type": "number",
                  "required": true,
                  "validation": { "min": 0 }
                },
                {
                  "id": "foreign_male",
                  "label": "外國籍員工-男",
                  "type": "number",
                  "required": true,
                  "validation": { "min": 0 }
                },
                {
                  "id": "foreign_female",
                  "label": "外國籍員工-女",
                  "type": "number",
                  "required": true,
                  "validation": { "min": 0 }
                }
              ]
            },
            {
              "id": "services_products",
              "label": "提供的服務/產品項目",
              "type": "textarea",
              "required": false
            },
            {
              "id": "certifications",
              "label": "管理系統認證",
              "type": "checkbox_multiple",
              "required": false,
              "options": [
                "ISO 9001 (品質管理)",
                "ISO 14001 (環境管理)",
                "ISO 45001 (職業安全衛生)",
                "IATF 16949 (汽車產業)"
              ]
            },
            {
              "id": "rba_online",
              "label": "RBA-Online System",
              "type": "radio",
              "required": false,
              "options": [
                { "value": "registered", "label": "已註冊" },
                { "value": "not_registered", "label": "未註冊" },
                { "value": "planning", "label": "規劃中" }
              ]
            }
          ]
        },
        {
          "id": "contact_info",
          "title": "聯絡信息",
          "repeatable": true,
          "fields": [
            {
              "id": "contact_name",
              "label": "聯絡人員",
              "type": "text",
              "required": true
            },
            {
              "id": "contact_title",
              "label": "職稱",
              "type": "text",
              "required": true
            },
            {
              "id": "contact_email",
              "label": "Email",
              "type": "email",
              "required": true,
              "validation": {
                "pattern": "^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$"
              }
            }
          ]
        }
      ]
    }
  }
}
```

---

## 📝 答案儲存格式變更

### 6.2 暫存答案
**現有**: `PUT /api/v1/project-suppliers/{projectSupplierId}/answers`  
**變更**: 支援新的答案格式

**Request Body v2.0**:
```json
{
  "basicInfo": {
    "company": {
      "fullName": "ABC 電子股份有限公司",
      "address": "台北市信義區信義路五段7號",
      "totalRevenue": 50000000
    },
    "facilities": [
      {
        "name": "台北廠",
        "address": "新北市土城區工業路100號",
        "employees": {
          "localMale": 150,
          "localFemale": 80,
          "foreignMale": 20,
          "foreignFemale": 10
        },
        "servicesProducts": "電子零件製造",
        "certifications": ["ISO 9001 (品質管理)", "ISO 14001 (環境管理)"],
        "rbaOnline": "registered"
      }
    ],
    "contacts": [
      {
        "name": "王小明",
        "title": "品質經理",
        "email": "wang@abc-electronics.com"
      }
    ]
  },
  "answers": {
    "q_a01_001": {
      "questionId": "q_a01_001",
      "value": true
    },
    "q_a01_001_1": {
      "questionId": "q_a01_001_1",
      "value": "本公司已制定完整的勞動政策，包含工時管理、薪資福利、安全衛生等面向..."
    },
    "q_a01_002": {
      "questionId": "q_a01_002",
      "value": true
    },
    "q_a01_002_1": {
      "questionId": "q_a01_002_1",
      "value": {
        "row_2023": {
          "col_year": "2023",
          "col_count": "1",
          "col_amount": "5000",
          "col_issue": "加班時數超時",
          "col_action": "已修正工時管理系統並加強監控"
        },
        "row_2024": {
          "col_year": "2024",
          "col_count": "0",
          "col_amount": "0",
          "col_issue": "",
          "col_action": ""
        },
        "row_2025": {
          "col_year": "2025",
          "col_count": "0",
          "col_amount": "0",
          "col_issue": "",
          "col_action": ""
        }
      }
    }
  }
}
```

**欄位說明**:
- `basicInfo`: 🆕 公司基本資料（對應第一步）
- `answers`: 問卷答案（第 2-6 步）
  - 一般題目：`value` 為簡單值
  - 表格題：`value` 為物件，key 為 `rowId`，value 為 `{ [columnId]: cellValue }`

---

### 6.1 取得專案答案
**現有**: `GET /api/v1/project-suppliers/{projectSupplierId}/answers`  
**變更**: Response 包含 `basicInfo`

**Response v2.0**:
```json
{
  "success": true,
  "data": {
    "projectSupplierId": 101,
    "basicInfo": {
      "company": {...},
      "facilities": [...],
      "contacts": [...]
    },
    "answers": {
      "q_a01_001": {
        "questionId": "q_a01_001",
        "value": true
      },
      "q_a01_002_1": {
        "questionId": "q_a01_002_1",
        "value": {
          "row_2023": {...},
          "row_2024": {...},
          "row_2025": {...}
        }
      }
    },
    "lastSavedAt": "2025-12-09T10:30:00.000Z"
  }
}
```

---

## 🎯 實作檢查清單

### Phase 1: 資料庫結構調整 (優先)
- [ ] **1.1 新增範本結構表**
  - [ ] 建立 `template_sections` 表
    - 欄位: id, template_id, section_id, order, title, description
  - [ ] 建立 `template_subsections` 表
    - 欄位: id, section_id, subsection_id, order, title, description
  - [ ] 修改 `template_questions` 表
    - 新增: subsection_id, order, conditional_logic (JSON), table_config (JSON)

- [ ] **1.2 新增答案表結構**
  - [ ] 建立 `project_basic_info` 表
    - 欄位: project_supplier_id, company_data (JSON), facilities (JSON), contacts (JSON)
  - [ ] 修改 `project_answers` 表
    - 調整以支援複雜 value（如表格資料的 JSON）

- [ ] **1.3 資料遷移腳本**
  - [ ] 撰寫 migration 將現有 v1.0 範本轉換為 v2.0 格式
  - [ ] 測試向下相容性

### Phase 2: API 端點實作
- [ ] **2.1 範本管理 API**
  - [ ] `GET /api/v1/templates` - 更新 Response 包含結構資訊
  - [ ] `GET /api/v1/templates/{id}` - 回傳完整 v2.0 結構
  - [ ] `POST /api/v1/templates` - 支援建立 v2.0 範本
  - [ ] `PUT /api/v1/templates/{id}` - 支援更新 v2.0 範本
  - [ ] `GET /api/v1/templates/basic-info-structure` - 新增端點

- [ ] **2.2 答案 API**
  - [ ] `GET /api/v1/project-suppliers/{id}/answers` - 包含 basicInfo
  - [ ] `PUT /api/v1/project-suppliers/{id}/answers` - 支援儲存 basicInfo 和表格答案
  - [ ] 實作條件邏輯的答案驗證

- [ ] **2.3 驗證邏輯**
  - [ ] 實作表格資料驗證
  - [ ] 實作條件邏輯的顯示/隱藏驗證
  - [ ] 實作公司基本資料驗證

### Phase 3: 業務邏輯實作
- [ ] **3.1 範本版本管理**
  - [ ] 建立範本時自動建立 v2.0.0 版本
  - [ ] 支援範本版本升級
  - [ ] 保持向下相容 v1.0 範本

- [ ] **3.2 評分計算**
  - [ ] 實作 `ScoringConfig` 的計分邏輯
  - [ ] API 端點：`POST /api/v1/project-suppliers/{id}/calculate-score`
  - [ ] 支援多種計分方法（BOOLEAN_COUNT, RATING_AVERAGE 等）

- [ ] **3.3 條件邏輯處理**
  - [ ] 實作條件判斷引擎
  - [ ] 動態計算應顯示的題目
  - [ ] 驗證必填欄位時考慮條件邏輯

### Phase 4: 測試與文件
- [ ] **4.1 單元測試**
  - [ ] 範本 CRUD 測試
  - [ ] 答案儲存與讀取測試
  - [ ] 條件邏輯測試
  - [ ] 表格資料驗證測試
  - [ ] 評分計算測試

- [ ] **4.2 整合測試**
  - [ ] 完整問卷流程測試（建立範本 → 指派專案 → 填寫 → 提交 → 審核）
  - [ ] v1.0 與 v2.0 相容性測試
  - [ ] 效能測試（大量題目、複雜條件邏輯）

- [ ] **4.3 文件更新**
  - [ ] 更新 `backend/docs/API-SPECIFICATION.md`
  - [ ] 撰寫資料庫 Schema 文件
  - [ ] 提供範例資料與測試腳本

### Phase 5: 前端整合準備
- [ ] **5.1 範例資料**
  - [ ] 建立完整的 SAQ 範本範例（包含 A-E 五個面向）
  - [ ] 建立測試用的問卷與答案資料
  - [ ] 提供 Postman/Thunder Client Collection

- [ ] **5.2 前端協作**
  - [ ] 提供前端 TypeScript 型別定義
  - [ ] 協助前端測試 API 整合
  - [ ] 處理前端回饋的問題與調整

---

## 📐 資料庫 Schema 範例

### template_sections
```sql
CREATE TABLE template_sections (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  template_id BIGINT UNSIGNED NOT NULL,
  section_id VARCHAR(10) NOT NULL,  -- A, B, C, D, E
  order INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (template_id) REFERENCES templates(id) ON DELETE CASCADE,
  UNIQUE KEY uk_template_section (template_id, section_id)
);
```

### template_subsections
```sql
CREATE TABLE template_subsections (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  section_id BIGINT UNSIGNED NOT NULL,
  subsection_id VARCHAR(20) NOT NULL,  -- A.1, A.2, B.1, ...
  order INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (section_id) REFERENCES template_sections(id) ON DELETE CASCADE,
  UNIQUE KEY uk_section_subsection (section_id, subsection_id)
);
```

### template_questions (擴展)
```sql
ALTER TABLE template_questions
ADD COLUMN subsection_id BIGINT UNSIGNED AFTER template_id,
ADD COLUMN order INT NOT NULL DEFAULT 0 AFTER id,
ADD COLUMN conditional_logic JSON AFTER config,
ADD COLUMN table_config JSON AFTER conditional_logic,
ADD FOREIGN KEY (subsection_id) REFERENCES template_subsections(id) ON DELETE CASCADE;
```

### project_basic_info
```sql
CREATE TABLE project_basic_info (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_supplier_id BIGINT UNSIGNED NOT NULL,
  company_data JSON NOT NULL,      -- { fullName, address, totalRevenue }
  facilities JSON NOT NULL,         -- [{ name, address, employees, ... }]
  contacts JSON NOT NULL,           -- [{ name, title, email }]
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (project_supplier_id) REFERENCES project_suppliers(id) ON DELETE CASCADE,
  UNIQUE KEY uk_project_supplier (project_supplier_id)
);
```

---

## 🔍 向下相容性考量

### 1. 舊範本的處理
- 所有 v1.0 範本（只有 `questions` 陣列）應繼續正常運作
- API 自動偵測範本版本：
  - 若有 `structure` 欄位 → v2.0
  - 若只有 `questions` → v1.0
- 前端可根據版本選擇渲染方式

### 2. API 響應相容
```json
{
  "id": "tmpl_old_001",
  "name": "舊版 SAQ 範本",
  "type": "SAQ",
  "latestVersion": "1.0.0",
  "structureVersion": "1.0",     // 標記為舊版
  "questions": [...],             // v1.0 格式
  "structure": null               // v2.0 欄位為 null
}
```

### 3. 漸進式升級
- 提供 API 端點升級舊範本：
  ```
  POST /api/v1/templates/{id}/upgrade-to-v2
  ```
- 由 HOST 手動觸發升級（避免自動轉換造成問題）

---

## 📊 效能考量

### 1. 資料庫查詢優化
- 使用 JOIN 一次載入範本完整結構
- 建立適當的索引（template_id, section_id, subsection_id）
- 考慮使用 Redis 快取常用範本

### 2. JSON 欄位大小限制
- `conditional_logic`: 建議 < 10KB
- `table_config`: 建議 < 50KB
- `company_data`, `facilities`, `contacts`: 建議 < 100KB

### 3. API 回應大小
- 完整範本可能 > 500KB
- 考慮分頁載入或按需載入小節

---

## 🚀 上線計畫

### 階段 1: 開發環境（Week 1-2）
- 完成 Phase 1-2 實作
- 內部測試

### 階段 2: 測試環境（Week 3）
- 完成 Phase 3-4 實作
- 前後端整合測試
- 效能測試

### 階段 3: 預發布環境（Week 4）
- 完整功能測試
- 資料遷移測試
- 向下相容性驗證

### 階段 4: 生產環境（Week 5）
- 資料庫遷移
- API 部署
- 監控與回饋

---

## 📞 聯絡與支援

**API 開發負責人**: Backend Team  
**前端整合負責人**: Frontend Team  
**問題回報**: GitHub Issues  
**緊急聯絡**: [待填寫]

---

**文件版本**: 2.0.0  
**最後更新**: 2025-12-09  
**下次審查**: 2025-12-16
