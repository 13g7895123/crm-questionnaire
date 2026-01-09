# 責任礦產問卷系統 - 功能實作規劃

> **專案代號**: Responsible Minerals Questionnaire System (RMQS)  
> **建立日期**: 2026-01-09  
> **版本**: 1.0.0  
> **預計開發週期**: 8-10 週

## 目錄

1. [功能總覽](#功能總覽)
2. [開發階段規劃](#開發階段規劃)
3. [功能模組拆解](#功能模組拆解)
4. [資料庫設計](#資料庫設計)
5. [API 端點清單](#api-端點清單)
6. [前端頁面清單](#前端頁面清單)
7. [開發任務清單](#開發任務清單)
8. [測試計畫](#測試計畫)
9. [部署計畫](#部署計畫)

---

## 功能總覽

### 核心功能模組

```
責任礦產問卷系統
├─ 📋 範本管理 (Template Management)
│  ├─ CMRT 6.5 支援
│  ├─ EMRT 2.1 支援
│  ├─ AMRT 1.21 支援
│  └─ Excel 匯入/匯出
│
├─ 📊 專案管理 (Project Management)
│  ├─ 建立專案 + 匯入供應商清單 ✅ 已完成
│  ├─ 專案資訊編輯
│  ├─ 多範本指派設定
│  ├─ 專案狀態追蹤
│  └─ 供應商填寫進度追蹤
│
├─ 📝 問卷填寫 (Questionnaire Filling)
│  ├─ 供應商 Excel 上傳
│  ├─ 自動解析與驗證
│  ├─ RMI 主檔比對
│  └─ 暫存/提交功能
│
└─ ✅ 審核流程 (Review Process)
   ├─ 多階段審核
   ├─ 審核歷程記錄
   └─ 核准/退回功能

📌 供應商管理 (Supplier Management) - 已在會員中心實作 ✅
```

### 專案概念說明

**專案 = 單次衝突礦產調查活動**

- 一個專案代表一次完整的衝突礦產調查活動（例如：2025 Q1 供應商調查）
- 專案建立時**必須**匯入參與的供應商清單
- 範本指派可以**延後設定**（專案建立後在專案內設定）
- 每個專案有獨立的審核流程與時間軸
- 專案完成後進入封存狀態，供查詢與報表使用

### 專案建立流程

```
階段 1: 建立專案（必要）
├─ 填寫專案基本資訊（名稱、年份）
├─ 匯入供應商清單 ✅ 必要
├─ 設定審核流程
└─ 儲存專案

階段 2: 範本指派（可選，專案建立後執行）
├─ 進入專案「供應商管理」頁面
├─ 為每個供應商設定範本（CMRT/EMRT/AMRT）
├─ 設定 AMRT 礦產清單（如需要）
└─ 發送填寫邀請

階段 3: 進度追蹤
├─ 查看各供應商填寫狀況
├─ 查看範本完成度
├─ 發送提醒通知
└─ 匯出進度報表
```

### 支援的 RMI 範本

| 範本 | 版本 | 礦產範圍 | 優先級 |
|------|------|---------|--------|
| CMRT | 6.5 | 3TG (錫、鉭、鎢、金) | P0 |
| EMRT | 2.1 | 鈷、雲母、銅、石墨、鋰、鎳 | P1 |
| AMRT | 1.21 | 自選 1-10 種礦產 | P2 |

---

## 開發階段規劃

### Phase 1: 基礎架構與 CMRT 支援（3 週）

**目標**: 完成核心架構與 CMRT 6.5 完整功能

**交付項目**:
- ✅ 資料庫 Schema 設計與 Migration
- ✅ RMI 範本偵測器 (RMITemplateDetector)
- ✅ CMRT 解析器 (CMRTParser)
- ✅ CMRT 驗證器 (CMRTValidator)
- ✅ RMI Smelter 主檔匯入
- ✅ CMRT 範本管理 CRUD API
- ✅ CMRT Excel 上傳/解析 API
- ✅ 前端範本管理頁面
- ✅ 前端 CMRT 上傳頁面

### Phase 2: 專案管理與供應商整合（1.5 週）

**目標**: 完成專案建立與供應商清單整合

**交付項目**:
- ✅ 專案 CRUD API（已完成）
- ✅ 專案建立時匯入供應商功能（已完成）
- ✅ 供應商範本指派設定
- ✅ 專案進度追蹤 API
- ✅ 前端專案管理頁面
- ✅ 供應商通知機制

**備註**: 供應商 CRUD 已在會員中心完成，此階段僅處理專案關聯

### Phase 3: EMRT 支援（2 週）

**目標**: 擴展支援 EMRT 2.1

**交付項目**:
- ✅ EMRT 解析器 (EMRTParser)
- ✅ EMRT 驗證器
- ✅ Mine List 資料表與解析
- ✅ RMI Refiner/Processor 主檔匯入
- ✅ EMRT 範本管理 API
- ✅ 前端 EMRT 支援頁面
- ✅ 統一範本選擇介面

### Phase 4: AMRT 支援（1.5 週）

**目標**: 擴展支援 AMRT 1.21

**交付項目**:
- ✅ AMRT 解析器 (AMRTParser)
- ✅ AMRT 驗證器
- ✅ 自選礦產欄位處理
- ✅ AMRT 範本管理 API
- ✅ 前端 AMRT 支援頁面

### Phase 5: 問卷填寫與審核（2 週）

**目標**: 完成供應商填寫與審核流程

**交付項目**:
- ✅ 供應商問卷填寫頁面（支援三種範本）
- ✅ Excel 答案匯入 API
- ✅ 答案暫存/提交功能
- ✅ 多階段審核流程
- ✅ 審核歷程記錄
- ✅ Email 通知整合

### Phase 6: 整合測試與優化（1 週）

**目標**: 完整系統測試與效能優化

**交付項目**:
- ✅ E2E 測試完成
- ✅ 效能優化
- ✅ 使用者文件
- ✅ 部署準備

**總週期**: 10-11 週（減少 1 週，因供應商管理已完成）

---

## 功能模組拆解

### 模組 1: 範本管理 (Template Management)

#### 功能點
1. **範本 CRUD**
   - 建立範本（選擇類型：CMRT/EMRT/AMRT）
   - 編輯範本基本資訊
   - 刪除範本（軟刪除）
   - 列表與搜尋

2. **Excel 匯入**
   - 測試解析 (test-excel)
   - 檔案驗證（格式、大小）
   - 範本類型自動辨識
   - 版本自動辨識
   - 結構解析與儲存

3. **版本控制**
   - 範本版本追蹤
   - 版本比較功能（選配）
   - 歷史版本查詢

#### 資料表
- `templates` (既有，type='CONFLICT')
- `rm_template_metadata` (範本元資料)
- `rm_template_sections` (CMRT 區段結構)
- `rm_template_questions` (問題定義)

#### API 端點
```
POST   /api/v1/rm/templates
GET    /api/v1/rm/templates
GET    /api/v1/rm/templates/{id}
PUT    /api/v1/rm/templates/{id}
DELETE /api/v1/rm/templates/{id}
POST   /api/v1/rm/templates/test-excel
POST   /api/v1/rm/templates/{id}/import-excel
GET    /api/v1/rm/templates/{id}/export-excel
```

#### 前端頁面
```
/conflict/templates/index.vue          # 範本列表
/conflict/templates/create.vue         # 建立範本
/conflict/templates/[id].vue           # 範本編輯
/conflict/templates/[id]/preview.vue   # 範本預覽
```

---

### 模組 2: 專案管理 (Project Management)

#### 功能點

1. **專案建立**（必要步驟）
   - 填寫專案基本資訊（名稱、年份、類型）
   - 匯入供應商清單 ✅ 已完成（CSV/Excel）
   - 設定審核流程（階段、審核人員）
   - 儲存專案（供應商範本狀態預設為「未指派」）

2. **供應商範本管理**（延後設定，專案建立後）
   - 批量設定範本（選擇供應商 + 指派範本）
   - 逐一設定範本（為單一供應商設定 CMRT/EMRT/AMRT）
   - Excel 匯入範本指派（供應商 + 範本對應表）
   - 設定 AMRT 礦產清單（當指派 AMRT 時）
   - 查看範本指派狀態（已指派/未指派）

3. **供應商通知管理**
   - 發送填寫邀請（僅已指派範本的供應商）
   - 批量通知（全部已指派供應商）
   - 個別通知（單一供應商）
   - 提醒通知（未完成填寫的供應商）

4. **填寫進度追蹤** ⭐ 重點功能
   - **總覽統計**
     - 總供應商數
     - 已指派範本數 / 未指派範本數
     - 已完成填寫數 / 進行中 / 未開始
     - 各範本類型統計（CMRT/EMRT/AMRT）
   
   - **供應商明細**
     - 供應商名稱
     - 指派範本類型（CMRT ✓ | EMRT ✓ | AMRT ✗）
     - 填寫狀態（未開始/進行中/已提交/審核中/已核准）
     - 最後更新時間
     - 完成度百分比
   
   - **篩選與排序**
     - 依範本類型篩選
     - 依填寫狀態篩選
     - 依完成度排序
     - 搜尋供應商名稱

5. **專案編輯與狀態管理**
   - 編輯專案基本資訊
   - 更新審核流程
   - 變更專案狀態（草稿/進行中/已結束/已封存）
   - 刪除專案（軟刪除）

#### 資料表
- `projects` (既有)
- `project_review_stages` (審核階段設定)
- `rm_supplier_assignments` (供應商-專案-範本關聯)
  ```sql
  -- 專案建立時產生，但 cmrt_required/emrt_required/amrt_required 預設為 FALSE
  -- 在供應商管理頁面設定後更新為 TRUE
  ```

#### API 端點
```
# 專案 CRUD
POST   /api/v1/rm/projects                           # 建立專案（含供應商匯入）
GET    /api/v1/rm/projects                           # 專案列表
GET    /api/v1/rm/projects/{id}                      # 專案詳情
PUT    /api/v1/rm/projects/{id}                      # 更新專案
DELETE /api/v1/rm/projects/{id}                      # 刪除專案

# 專案供應商管理（範本指派）
GET    /api/v1/rm/projects/{id}/suppliers            # 供應商列表與範本狀態
PUT    /api/v1/rm/projects/{id}/suppliers/{supplierId}/templates  # 設定範本
POST   /api/v1/rm/projects/{id}/suppliers/batch-assign-templates  # 批量設定範本
POST   /api/v1/rm/projects/{id}/suppliers/import-template-assignments  # Excel 匯入範本指派

# 供應商通知
POST   /api/v1/rm/projects/{id}/suppliers/{supplierId}/notify     # 個別通知
POST   /api/v1/rm/projects/{id}/suppliers/notify-all              # 批量通知（已指派）

# 進度追蹤
GET    /api/v1/rm/projects/{id}/progress             # 整體進度統計
GET    /api/v1/rm/projects/{id}/suppliers/status     # 供應商明細狀態
GET    /api/v1/rm/projects/{id}/export/progress      # 匯出進度報表
```

#### 前端頁面
```
/conflict/projects/index.vue           # 專案列表
/conflict/projects/create.vue          # 建立專案 + 匯入供應商 ✅
/conflict/projects/[id].vue            # 專案編輯
/conflict/projects/[id]/overview.vue   # 專案總覽
/conflict/projects/[id]/suppliers.vue  # 供應商範本管理 ⭐ 核心頁面
/conflict/projects/[id]/progress.vue   # 填寫進度追蹤 ⭐ 核心頁面
```

#### suppliers.vue 頁面功能設計

**頁面佈局**：
```
┌─────────────────────────────────────────────┐
│  供應商範本管理                              │
├─────────────────────────────────────────────┤
│  [批量設定] [Excel 匯入] [全部通知]         │
├─────────────────────────────────────────────┤
│  搜尋: [_______] 篩選: [已指派/未指派▼]    │
├─────────────────────────────────────────────┤
│  ☑ 供應商         | CMRT | EMRT | AMRT | 操作│
│  ──────────────────────────────────────────│
│  ☐ ABC Co.       │  ✓   │  ✓   │  ✗   │[編輯]│
│  ☐ XYZ Inc.      │  ✓   │  ✗   │  ✗   │[編輯]│
│  ☐ DEF Ltd.      │  ✗   │  ✗   │  ✗   │[編輯]│
└─────────────────────────────────────────────┘
```

**操作流程**：
1. 點擊「編輯」→ 彈出範本設定對話框
2. 勾選 CMRT / EMRT / AMRT
3. 若勾選 AMRT → 顯示礦產選擇（多選：Silver, Platinum...）
4. 儲存 → 更新範本指派
5. 點擊「通知」→ 發送填寫邀請 Email

#### progress.vue 頁面功能設計

**頁面佈局**：
```
┌─────────────────────────────────────────────┐
│  填寫進度追蹤                                │
├─────────────────────────────────────────────┤
│  總供應商: 50   已指派: 45   未指派: 5      │
│  已完成: 30     進行中: 10   未開始: 5      │
├─────────────────────────────────────────────┤
│  範本類型統計                                │
│  CMRT: █████████░░ 90% (45/50)             │
│  EMRT: ██████░░░░░ 60% (30/50)             │
│  AMRT: ████░░░░░░░ 40% (20/50)             │
├─────────────────────────────────────────────┤
│  篩選: [範本類型▼] [狀態▼] [匯出 Excel]    │
├─────────────────────────────────────────────┤
│  供應商     | 範本      | 狀態   | 完成度 | 更新時間 │
│  ─────────────────────────────────────────│
│  ABC Co.   │ C+E+A    │ 已提交 │ 100%  │ 2026-01-08│
│  XYZ Inc.  │ C        │ 進行中 │ 50%   │ 2026-01-09│
│  DEF Ltd.  │ 未指派    │ -      │ 0%    │ -        │
└─────────────────────────────────────────────┘
```

**說明**: 
- ✅ 專案建立時僅匯入供應商，範本指派狀態為「未指派」
- ⭐ suppliers.vue 為核心頁面，用於設定供應商範本
- ⭐ progress.vue 提供完整的填寫狀況追蹤
- 📧 通知功能僅對「已指派範本」的供應商有效

---

### 模組 3: 問卷填寫 (Questionnaire Filling)

#### 功能點
1. **供應商登入與導航**
   - 查看指派給自己的專案
   - 查看需填寫的範本類型
   - 下載空白範本 Excel

2. **Excel 上傳填寫**
   - 上傳填寫完成的 Excel
   - 自動辨識範本類型
   - 解析供應商資料
   - 解析礦產聲明
   - 解析冶煉廠/加工廠/礦場清單

3. **RMI 主檔比對**
   - 比對 Smelter Reference List
   - 比對 Refiner/Processor Reference List
   - 標記驗證狀態
   - 顯示警告訊息

4. **暫存與提交**
   - 暫存功能（儲存草稿）
   - 提交前驗證
   - 提交至審核流程
   - 提交後不可編輯

#### 資料表
- `rm_answers` (供應商填寫資料)
- `rm_answer_smelters` (冶煉廠/加工廠資料)
- `rm_answer_mines` (礦場資料 - EMRT 專用)
- `rm_smelters` (RMI 主檔)

#### API 端點
```
# 供應商查詢
GET    /api/v1/rm/supplier/projects
GET    /api/v1/rm/supplier/projects/{projectId}/assignments

# 問卷填寫
POST   /api/v1/rm/projects/{projectId}/answers/test-excel
POST   /api/v1/rm/projects/{projectId}/answers/import-excel
GET    /api/v1/rm/projects/{projectId}/answers
POST   /api/v1/rm/projects/{projectId}/answers
PUT    /api/v1/rm/projects/{projectId}/answers/{id}
POST   /api/v1/rm/projects/{projectId}/answers/{id}/submit

# 範本下載
GET    /api/v1/rm/templates/{templateId}/download
```

#### 前端頁面
```
/supplier/projects/index.vue                      # 供應商專案列表
/supplier/projects/[id]/answer.vue                # 問卷填寫頁面
/supplier/projects/[id]/upload.vue                # Excel 上傳頁面
/supplier/projects/[id]/preview.vue               # 預覽已填資料
```

---

### 模組 4: 審核流程 (Review Process)

#### 功能點
1. **待審核清單**
   - 查看待審核專案
   - 篩選（範本類型、供應商）
   - 審核階段標示

2. **審核介面**
   - 查看供應商填寫資料
   - 查看 RMI 驗證狀態
   - 查看警告訊息
   - 核准/退回決策
   - 填寫審核意見

3. **審核歷程**
   - 記錄審核者
   - 記錄審核時間
   - 記錄審核結果
   - 記錄審核意見
   - 記錄審核階段

#### 資料表
- `rm_review_logs` (審核歷程)

#### API 端點
```
# 審核清單
GET    /api/v1/rm/reviews/pending
GET    /api/v1/rm/reviews/history

# 審核操作
POST   /api/v1/rm/projects/{projectId}/answers/{id}/review
GET    /api/v1/rm/projects/{projectId}/answers/{id}/reviews

# 審核者查詢
GET    /api/v1/rm/reviews/my-reviews
```

#### 前端頁面
```
/conflict/reviews/pending.vue                     # 待審核列表
/conflict/reviews/history.vue                     # 審核歷史
/conflict/projects/[id]/review/[answerId].vue     # 審核頁面
```

---

## 資料庫設計

### 核心資料表

#### 1. templates（既有，沿用）
```sql
-- type='CONFLICT' 用於責任礦產
```

#### 2. rm_template_metadata（新增）
```sql
CREATE TABLE rm_template_metadata (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_id INT NOT NULL,
    template_type ENUM('CMRT', 'EMRT', 'AMRT') NOT NULL,
    template_version VARCHAR(20) NOT NULL COMMENT 'e.g., 6.5, 2.1, 1.21',
    minerals_covered JSON COMMENT '["Tin", "Tantalum", "Tungsten", "Gold"]',
    excel_file_path VARCHAR(255),
    parsed_structure JSON COMMENT '完整解析結構',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES templates(id) ON DELETE CASCADE,
    INDEX idx_type_version (template_type, template_version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 3. rm_smelters（新增 - RMI 主檔）
```sql
CREATE TABLE rm_smelters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    smelter_id VARCHAR(50) NOT NULL COMMENT 'RMI Smelter ID',
    smelter_name VARCHAR(200) NOT NULL,
    metal_type VARCHAR(50) NOT NULL,
    country VARCHAR(100),
    facility_type ENUM('Smelter', 'Refiner', 'Processor') DEFAULT 'Smelter',
    source VARCHAR(50) COMMENT 'RMI, LBMA, etc.',
    validated BOOLEAN DEFAULT TRUE,
    rmi_conformant BOOLEAN DEFAULT FALSE,
    last_updated DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_smelter (smelter_id, metal_type),
    INDEX idx_metal (metal_type),
    INDEX idx_validated (validated)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 4. rm_supplier_assignments（新增）
```sql
CREATE TABLE rm_supplier_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    supplier_id INT NULL,
    supplier_name VARCHAR(200) NOT NULL,
    supplier_code VARCHAR(50),
    supplier_email VARCHAR(100) NOT NULL,
    cmrt_required BOOLEAN DEFAULT FALSE,
    emrt_required BOOLEAN DEFAULT FALSE,
    amrt_required BOOLEAN DEFAULT FALSE,
    amrt_minerals JSON COMMENT '["Silver", "Platinum"]',
    notes TEXT,
    status ENUM('pending', 'invited', 'in_progress', 'completed') DEFAULT 'pending',
    invited_at TIMESTAMP NULL,
    submitted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    INDEX idx_project_supplier (project_id, supplier_email),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 5. rm_answers（新增）
```sql
CREATE TABLE rm_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    template_type ENUM('CMRT', 'EMRT', 'AMRT') NOT NULL,
    company_name VARCHAR(200),
    company_country VARCHAR(100),
    declaration_scope VARCHAR(100),
    mineral_declaration JSON COMMENT '{"Tin": {"used": "Yes"}, ...}',
    additional_info TEXT,
    excel_file_path VARCHAR(255),
    validation_warnings JSON COMMENT '[{"type": "unvalidated_smelter", ...}]',
    status ENUM('draft', 'submitted', 'reviewing', 'approved', 'returned') DEFAULT 'draft',
    submitted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assignment_id) REFERENCES rm_supplier_assignments(id) ON DELETE CASCADE,
    INDEX idx_assignment (assignment_id),
    INDEX idx_type (template_type),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 6. rm_answer_smelters（新增）
```sql
CREATE TABLE rm_answer_smelters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    answer_id INT NOT NULL,
    metal_type VARCHAR(50) NOT NULL,
    smelter_id VARCHAR(50),
    smelter_name VARCHAR(200) NOT NULL,
    smelter_country VARCHAR(100),
    smelter_city VARCHAR(100),
    smelter_address VARCHAR(255),
    contact_name VARCHAR(100),
    contact_email VARCHAR(100),
    source_of_smelter_id VARCHAR(50),
    validated BOOLEAN DEFAULT FALSE COMMENT '是否在 RMI 主檔中',
    rmi_smelter_id INT NULL COMMENT '關聯至 rm_smelters.id',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (answer_id) REFERENCES rm_answers(id) ON DELETE CASCADE,
    FOREIGN KEY (rmi_smelter_id) REFERENCES rm_smelters(id) ON DELETE SET NULL,
    INDEX idx_answer (answer_id),
    INDEX idx_metal_type (metal_type),
    INDEX idx_validated (validated)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 7. rm_answer_mines（新增 - EMRT 專用）
```sql
CREATE TABLE rm_answer_mines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    answer_id INT NOT NULL,
    metal_type VARCHAR(50) NOT NULL,
    mine_name VARCHAR(200) NOT NULL,
    mine_country VARCHAR(100),
    mine_province VARCHAR(100),
    mine_location VARCHAR(255),
    mine_owner VARCHAR(200),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (answer_id) REFERENCES rm_answers(id) ON DELETE CASCADE,
    INDEX idx_answer (answer_id),
    INDEX idx_metal_type (metal_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 8. rm_review_logs（新增）
```sql
CREATE TABLE rm_review_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    answer_id INT NOT NULL,
    reviewer_id INT NOT NULL,
    reviewer_name VARCHAR(100),
    stage INT NOT NULL COMMENT '審核階段',
    action ENUM('APPROVE', 'RETURN') NOT NULL,
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (answer_id) REFERENCES rm_answers(id) ON DELETE CASCADE,
    INDEX idx_answer (answer_id),
    INDEX idx_reviewer (reviewer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## API 端點清單

### 完整 API 列表（31 個端點）

#### 範本管理 (8 個)
```
POST   /api/v1/rm/templates                          # 建立範本
GET    /api/v1/rm/templates                          # 範本列表
GET    /api/v1/rm/templates/{id}                     # 範本詳情
PUT    /api/v1/rm/templates/{id}                     # 更新範本
DELETE /api/v1/rm/templates/{id}                     # 刪除範本
POST   /api/v1/rm/templates/test-excel               # 測試解析範本 Excel
POST   /api/v1/rm/templates/{id}/import-excel        # 匯入範本 Excel
GET    /api/v1/rm/templates/{id}/download            # 下載空白範本
```

#### 專案管理 (12 個) - 包含供應商範本管理
```
# 專案 CRUD
POST   /api/v1/rm/projects                           # 建立專案（含供應商匯入）
GET    /api/v1/rm/projects                           # 專案列表
GET    /api/v1/rm/projects/{id}                      # 專案詳情
PUT    /api/v1/rm/projects/{id}                      # 更新專案
DELETE /api/v1/rm/projects/{id}                      # 刪除專案

# 供應商範本管理（專案建立後設定）
GET    /api/v1/rm/projects/{id}/suppliers            # 供應商列表與範本狀態
PUT    /api/v1/rm/projects/{id}/suppliers/{supplierId}/templates  # 設定單一供應商範本
POST   /api/v1/rm/projects/{id}/suppliers/batch-assign-templates  # 批量設定範本
POST   /api/v1/rm/projects/{id}/suppliers/import-template-assignments  # Excel 匯入範本指派
POST   /api/v1/rm/projects/{id}/suppliers/{supplierId}/notify     # 通知單一供應商
POST   /api/v1/rm/projects/{id}/suppliers/notify-all              # 批量通知（已指派）

# 進度追蹤
GET    /api/v1/rm/projects/{id}/progress             # 整體進度統計
GET    /api/v1/rm/projects/{id}/suppliers/status     # 供應商明細狀態
GET    /api/v1/rm/projects/{id}/export/progress      # 匯出進度報表
```

#### 問卷填寫 (7 個)
```
GET    /api/v1/rm/supplier/projects                           # 供應商專案列表
GET    /api/v1/rm/supplier/projects/{id}/assignments          # 供應商指派詳情
POST   /api/v1/rm/projects/{id}/answers/test-excel            # 測試解析答案 Excel
POST   /api/v1/rm/projects/{id}/answers/import-excel          # 匯入答案 Excel
GET    /api/v1/rm/projects/{id}/answers/{answerId}            # 答案詳情
PUT    /api/v1/rm/projects/{id}/answers/{answerId}            # 更新答案（暫存）
POST   /api/v1/rm/projects/{id}/answers/{answerId}/submit     # 提交答案
```

#### 審核流程 (4 個)
```
GET    /api/v1/rm/reviews/pending                             # 待審核列表
GET    /api/v1/rm/reviews/my-reviews                          # 我的審核任務
POST   /api/v1/rm/projects/{id}/answers/{answerId}/review     # 提交審核
GET    /api/v1/rm/projects/{id}/answers/{answerId}/reviews    # 審核歷程
```

**說明**: 
- ❌ 移除獨立的供應商指派 API（8 個），因已整合至專案建立流程
- ✅ 新增專案供應商管理 API（3 個），用於管理專案內的供應商範本設定
- 📌 供應商 CRUD 在會員中心的 `/api/v1/suppliers` 下完成

---

## 前端頁面清單

### 完整頁面結構（20 個頁面）

```
conflict/ (製造商端)
├── index.vue                                        # 衝突資產首頁
├── templates/
│   ├── index.vue                                    # 範本列表
│   ├── create.vue                                   # 建立範本（選擇類型）
│   ├── [id].vue                                     # 範本編輯
│   └── [id]/
│       ├── preview.vue                              # 範本預覽
│       └── import.vue                               # Excel 匯入頁面
├── projects/
│   ├── index.vue                                    # 專案列表
│   ├── create.vue                                   # 建立專案 + 匯入供應商 ✅
│   ├── [id].vue                                     # 專案編輯
│   └── [id]/
│       ├── overview.vue                             # 專案總覽
│       ├── suppliers.vue                            # 供應商列表與範本設定
│       ├── progress.vue                             # 進度追蹤
│       └── review/
│           └── [answerId].vue                       # 審核頁面
└── reviews/
    ├── pending.vue                                  # 待審核列表
    └── history.vue                                  # 審核歷史

supplier/ (供應商端)
├── index.vue                                        # 供應商首頁
└── projects/
    ├── index.vue                                    # 專案列表
    └── [id]/
        ├── answer.vue                               # 問卷填寫主頁
        ├── upload.vue                               # Excel 上傳
        └── preview.vue                              # 預覽已填資料

account/ (會員中心 - 既有)
└── suppliers/
    ├── index.vue                                    # 供應商主檔管理 ✅
    └── [id].vue                                     # 供應商編輯 ✅
```

**說明**:
- ✅ 供應商 CRUD 在會員中心 `/account/suppliers` 完成
- ✅ 專案建立時匯入供應商清單 `/conflict/projects/create.vue` 完成
- 📌 專案內供應商範本設定在 `/conflict/projects/[id]/suppliers.vue`
- ❌ 移除獨立的 `assignments` 相關頁面（已整合）

---

## 開發任務清單

### Phase 1: 基礎架構與 CMRT（Week 1-3）

#### Week 1: 資料庫與核心架構

**後端任務**
- [ ] T1.1 建立所有資料庫 Migration 檔案
- [ ] T1.2 建立 Seeder（測試資料）
- [ ] T1.3 建立 Model 類別（8 個）
  - [ ] RMTemplateMetadataModel
  - [ ] RMSmelterModel
  - [ ] RMSupplierAssignmentModel
  - [ ] RMAnswerModel
  - [ ] RMAnswerSmelterModel
  - [ ] RMAnswerMineModel
  - [ ] RMReviewLogModel
  - [ ] 更新 TemplateModel（新增 CONFLICT 類型）
- [ ] T1.4 建立 RMITemplateDetector 類別（範本類型偵測器）
- [ ] T1.5 建立 CMRTParser 類別（CMRT 解析器）
- [ ] T1.6 建立 CMRTValidator 類別（CMRT 驗證器）
- [ ] T1.7 匯入 RMI Smelter 主檔資料（CMRT 部分）

**前端任務**
- [ ] T1.8 建立 `composables/useResponsibleMinerals.ts`
- [ ] T1.9 建立共用元件 ExcelUploader.vue
- [ ] T1.10 建立共用元件 TemplateTypeSelector.vue

#### Week 2: CMRT 範本管理

**後端任務**
- [ ] T2.1 建立 ResponsibleMineralsTemplateController
- [ ] T2.2 實作範本 CRUD API（5 個端點）
- [ ] T2.3 實作 test-excel API（CMRT）
- [ ] T2.4 實作 import-excel API（CMRT）
- [ ] T2.5 實作 download API（下載空白 CMRT）
- [ ] T2.6 單元測試（CMRTParser, CMRTValidator）

**前端任務**
- [ ] T2.7 實作 /conflict/templates/index.vue
- [ ] T2.8 實作 /conflict/templates/create.vue
- [ ] T2.9 實作 /conflict/templates/[id].vue
- [ ] T2.10 實作 /conflict/templates/[id]/import.vue
- [ ] T2.11 實作 Excel 上傳與預覽功能

#### Week 3: CMRT 專案管理

**後端任務**
- [ ] T3.1 建立 ResponsibleMineralsProjectController
- [ ] T3.2 實作專案 CRUD API（6 個端點）
- [ ] T3.3 實作專案狀態管理
- [ ] T3.4 實作審核流程設定

**前端任務**
- [ ] T3.5 實作 /conflict/projects/index.vue
- [ ] T3.6 實作 /conflict/projects/create.vue
- [ ] T3.7 實作 /conflict/projects/[id].vue
- [ ] T3.8 實作 /conflict/projects/[id]/overview.vue
- [ ] T3.9 整合測試（範本與專案流程）

---

### Phase 2: 供應商指派（Week 4-5）

#### Week 4: Excel 批量匯入

**後端任務**
- [ ] T4.1 建立 SupplierAssignmentExcelParser 類別
- [ ] T4.2 實作 test-excel API（供應商指派）
- [ ] T4.3 實作 import-excel API（供應商指派）
- [ ] T4.4 實作欄位驗證邏輯
- [ ] T4.5 單元測試（SupplierAssignmentExcelParser）

**前端任務**
- [ ] T4.6 實作 /conflict/projects/[id]/assignments.vue
- [ ] T4.7 實作 /conflict/projects/[id]/assignments/import.vue
- [ ] T4.8 實作 Excel 解析預覽表格
- [ ] T4.9 實作錯誤標示與統計顯示
- [ ] T4.10 實作「下載範本」功能

#### Week 5: 手動指派與通知

**後端任務**
- [ ] T5.1 實作供應商指派 CRUD API（5 個端點）
- [ ] T5.2 實作邀請通知 API（2 個端點）
- [ ] T5.3 建立 Email 通知範本
- [ ] T5.4 整合 Email 發送服務

**前端任務**
- [ ] T5.5 實作 /conflict/projects/[id]/assignments/manual.vue
- [ ] T5.6 實作 /conflict/projects/[id]/progress.vue
- [ ] T5.7 實作供應商列表與狀態顯示
- [ ] T5.8 實作邀請通知功能
- [ ] T5.9 新增「依據規則指派」按鈕（disabled）

---

### Phase 3: EMRT 支援（Week 6-7）

#### Week 6: EMRT 解析器

**後端任務**
- [ ] T6.1 建立 EMRTParser 類別
- [ ] T6.2 建立 EMRTValidator 類別
- [ ] T6.3 實作 Mine List 解析邏輯
- [ ] T6.4 匯入 RMI Refiner/Processor 主檔（EMRT 部分）
- [ ] T6.5 更新 test-excel API（支援 EMRT）
- [ ] T6.6 更新 import-excel API（支援 EMRT）
- [ ] T6.7 單元測試（EMRTParser, EMRTValidator）

**前端任務**
- [ ] T6.8 更新範本建立頁面（加入 EMRT 選項）
- [ ] T6.9 建立 EMRT 專用元件
- [ ] T6.10 更新 Excel 預覽元件（支援 Mine List）

#### Week 7: EMRT 整合

**前端任務**
- [ ] T7.1 更新供應商指派頁面（支援 EMRT）
- [ ] T7.2 更新指派 Excel 格式（加入 EMRT 欄位）
- [ ] T7.3 測試 CMRT + EMRT 混合指派
- [ ] T7.4 整合測試（範本上傳、專案指派）

**文件任務**
- [ ] T7.5 更新 API 文件（EMRT 部分）
- [ ] T7.6 更新使用者手冊（EMRT 填寫說明）

---

### Phase 4: AMRT 支援（Week 8-9）

#### Week 8: AMRT 解析器

**後端任務**
- [ ] T8.1 建立 AMRTParser 類別
- [ ] T8.2 建立 AMRTValidator 類別
- [ ] T8.3 實作自選礦產欄位解析
- [ ] T8.4 更新 test-excel API（支援 AMRT）
- [ ] T8.5 更新 import-excel API（支援 AMRT）
- [ ] T8.6 單元測試（AMRTParser, AMRTValidator）

**前端任務**
- [ ] T8.7 更新範本建立頁面（加入 AMRT 選項）
- [ ] T8.8 建立 AMRT 礦產選擇元件
- [ ] T8.9 更新供應商指派頁面（支援 AMRT Minerals）

#### Week 9: 三範本整合

**後端任務**
- [ ] T9.1 整合測試（三種範本混合場景）
- [ ] T9.2 效能優化（大量資料解析）

**前端任務**
- [ ] T9.3 統一範本選擇介面
- [ ] T9.4 實作範本類型切換功能
- [ ] T9.5 整合測試（前後端聯調）

---

### Phase 5: 問卷填寫與審核（Week 10-11）

#### Week 10: 供應商填寫

**後端任務**
- [ ] T10.1 建立 ResponsibleMineralsAnswerController
- [ ] T10.2 實作答案 test-excel API
- [ ] T10.3 實作答案 import-excel API
- [ ] T10.4 實作 RMI 主檔比對邏輯
- [ ] T10.5 實作答案暫存/提交 API
- [ ] T10.6 單元測試（答案解析）

**前端任務**
- [ ] T10.7 實作 /supplier/projects/index.vue
- [ ] T10.8 實作 /supplier/projects/[id]/answer.vue
- [ ] T10.9 實作 /supplier/projects/[id]/upload.vue
- [ ] T10.10 實作 Excel 上傳與驗證流程
- [ ] T10.11 實作 RMI 驗證結果顯示

#### Week 11: 審核流程

**後端任務**
- [ ] T11.1 建立 ResponsibleMineralsReviewController
- [ ] T11.2 實作待審核列表 API
- [ ] T11.3 實作審核提交 API
- [ ] T11.4 實作審核歷程 API
- [ ] T11.5 實作多階段審核邏輯
- [ ] T11.6 整合 Email 通知（審核結果）

**前端任務**
- [ ] T11.7 實作 /conflict/reviews/pending.vue
- [ ] T11.8 實作 /conflict/reviews/history.vue
- [ ] T11.9 實作 /conflict/projects/[id]/review/[answerId].vue
- [ ] T11.10 實作審核決策介面（核准/退回）
- [ ] T11.11 實作審核意見表單

---

### Phase 6: 測試與部署（Week 12）

#### E2E 測試
- [ ] T12.1 完整流程測試（CMRT）
- [ ] T12.2 完整流程測試（EMRT）
- [ ] T12.3 完整流程測試（AMRT）
- [ ] T12.4 混合範本場景測試
- [ ] T12.5 錯誤處理測試
- [ ] T12.6 效能測試（100 筆供應商）

#### 文件與部署
- [ ] T12.7 API 文件完整性檢查
- [ ] T12.8 使用者手冊撰寫
- [ ] T12.9 部署腳本準備
- [ ] T12.10 Production 環境測試
- [ ] T12.11 上線前檢查清單

---

## 測試計畫

### 單元測試（目標覆蓋率 > 80%）

#### 後端
```
tests/unit/Libraries/
├── RMITemplateDetectorTest.php
├── CMRTParserTest.php
├── CMRTValidatorTest.php
├── EMRTParserTest.php
├── EMRTValidatorTest.php
├── AMRTParserTest.php
├── AMRTValidatorTest.php
└── SupplierAssignmentExcelParserTest.php
```

### 整合測試

```typescript
// tests/integration/responsible-minerals.spec.ts

describe('Responsible Minerals - Complete Flow', () => {
  it('should complete CMRT template creation and assignment', async () => {
    // 1. Upload CMRT template
    // 2. Create project with CMRT template
    // 3. Import supplier assignments
    // 4. Supplier uploads CMRT answers
    // 5. Submit to review
    // 6. Approve review
  })
  
  it('should handle mixed templates (CMRT + EMRT)', async () => {
    // Test scenario with multiple templates
  })
})
```

### E2E 測試場景

1. **製造商建立 CMRT 專案並指派供應商**
2. **供應商填寫 CMRT 並提交**
3. **審核人員核准 CMRT**
4. **製造商建立 EMRT 專案**
5. **供應商填寫 EMRT（含 Mine List）**
6. **混合範本場景（CMRT + EMRT + AMRT）**
7. **Excel 匯入錯誤處理**
8. **RMI 主檔比對警告**

---

## 部署計畫

### 環境準備

#### 資料庫
```bash
# 執行 Migration
php spark migrate --all

# 匯入 RMI 主檔資料
php spark db:seed RMISmelterSeeder
```

#### 環境變數
```env
# RMI 相關設定
RMI_SMELTER_LIST_URL=https://www.responsiblemineralsinitiative.org/...
RMI_AUTO_UPDATE=true
RMI_UPDATE_FREQUENCY=quarterly

# Email 通知
MAIL_FROM_ADDRESS=noreply@yourcompany.com
MAIL_FROM_NAME="Responsible Minerals System"
```

### 部署檢查清單

- [ ] 資料庫 Migration 已執行
- [ ] RMI 主檔已匯入
- [ ] Email 服務已設定
- [ ] 檔案上傳目錄權限已設定
- [ ] 前端環境變數已設定
- [ ] API 端點可訪問
- [ ] HTTPS 憑證已設定
- [ ] 備份機制已啟用

---

## 附錄

### A. 技術堆疊

**後端**
- PHP 8.1+
- CodeIgniter 4
- MySQL 8.0+
- PhpSpreadsheet (Excel 處理)
- PHPMailer (Email 通知)

**前端**
- Nuxt 3
- Vue 3
- TypeScript
- Tailwind CSS
- SheetJS (Excel 產生)

**部署**
- Docker
- Nginx
- PM2

### B. 參考連結

- [RMI 官方網站](https://www.responsiblemineralsinitiative.org/)
- [CMRT 下載](https://www.responsiblemineralsinitiative.org/reporting-templates/cmrt/)
- [EMRT 下載](https://www.responsiblemineralsinitiative.org/reporting-templates/emrt/)
- [AMRT 下載](https://www.responsiblemineralsinitiative.org/reporting-templates/)
- [完整技術文件](./CONFLICT_MINERALS_EXCEL_IMPORT.md)

---

**文件結束**

預估總開發時間：**10-12 週**  
核心團隊建議：**2 後端 + 2 前端 + 1 QA**
