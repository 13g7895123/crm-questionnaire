# 責任礦產系統規劃 - 調整說明

> **更新日期**: 2026-01-09  
> **版本**: 1.1.0

## 主要調整內容

### 📋 架構簡化

#### 調整前
- 5 個功能模組
- 獨立的供應商管理模組
- 供應商指派作為獨立流程

#### 調整後
- ✅ **4 個功能模組**（簡化 1 個）
- ✅ **供應商管理整合至會員中心**（已完成）
- ✅ **專案建立時匯入供應商**（已完成）

---

## 核心概念重新定義

### 專案 = 單次衝突礦產調查活動

```
專案建立流程：
1. 填寫專案基本資訊（名稱、年份）
2. 匯入供應商清單 ✅ 已完成
3. 設定供應商範本指派（CMRT/EMRT/AMRT）
4. 設定 AMRT 礦產清單（如需要）
5. 設定審核流程
6. 發布專案
```

### 供應商管理

- ✅ **供應商主檔**: 在會員中心 `/account/suppliers` 管理
- ✅ **專案關聯**: 專案建立時選擇供應商並匯入
- ✅ **範本指派**: 在專案內設定每個供應商需填寫的範本

---

## 數據變更

| 項目 | 調整前 | 調整後 | 變化 |
|------|-------|-------|------|
| **功能模組** | 5 個 | 4 個 | -1 |
| **API 端點** | 33 個 | 28 個 | -5 |
| **前端頁面** | 24 個 | 20 個 | -4 |
| **開發週期** | 10-12 週 | 10-11 週 | -1 週 |

---

## 資料庫調整

### 保持不變
- `rm_template_metadata`
- `rm_smelters`
- `rm_answers`
- `rm_answer_smelters`
- `rm_answer_mines`
- `rm_review_logs`

### 調整用途
```sql
-- rm_supplier_assignments 用途調整
-- 調整前：獨立管理供應商指派
-- 調整後：專案建立時產生，記錄「專案-供應商-範本」關聯

CREATE TABLE rm_supplier_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    supplier_id INT NOT NULL COMMENT '關聯至會員中心的 suppliers 表',
    supplier_name VARCHAR(200) NOT NULL,
    
    -- 範本指派（專案建立時設定）
    cmrt_required BOOLEAN DEFAULT FALSE,
    emrt_required BOOLEAN DEFAULT FALSE,
    amrt_required BOOLEAN DEFAULT FALSE,
    amrt_minerals JSON,
    
    -- 狀態追蹤
    status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
    invited_at TIMESTAMP NULL,
    submitted_at TIMESTAMP NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE RESTRICT,
    INDEX idx_project (project_id),
    INDEX idx_supplier (supplier_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## API 端點調整

### 移除的端點（5 個）
```
❌ POST   /api/v1/rm/projects/{id}/assignments/test-excel
❌ POST   /api/v1/rm/projects/{id}/assignments/import-excel
❌ POST   /api/v1/rm/projects/{id}/assignments
❌ PUT    /api/v1/rm/projects/{id}/assignments/{id}
❌ DELETE /api/v1/rm/projects/{id}/assignments/{id}

原因：專案建立時即完成供應商匯入，不需要獨立的指派管理
```

### 新增的端點（3 個）
```
✅ GET    /api/v1/rm/projects/{id}/suppliers
   # 查看專案的供應商列表與範本設定
   
✅ PUT    /api/v1/rm/projects/{id}/suppliers/{supplierId}/templates
   # 更新供應商的範本指派設定
   
✅ POST   /api/v1/rm/projects/{id}/suppliers/{supplierId}/notify
   # 發送通知給指定供應商
```

### 保留的端點（2 個）
```
✅ POST   /api/v1/rm/projects/{id}/assignments/{id}/invite
   # 發送邀請給指定供應商（改為 suppliers API）
   
✅ POST   /api/v1/rm/projects/{id}/assignments/invite-all
   # 批量發送邀請（改為 suppliers API）
```

---

## 前端頁面調整

### 移除的頁面（4 個）
```
❌ /conflict/projects/[id]/assignments.vue
❌ /conflict/projects/[id]/assignments/import.vue
❌ /conflict/projects/[id]/assignments/manual.vue
❌ (assignments 相關頁面)

原因：功能整合至專案建立與供應商管理頁面
```

### 保留/調整的頁面
```
✅ /conflict/projects/create.vue
   # 建立專案 + 匯入供應商清單（已完成）
   
✅ /conflict/projects/[id]/suppliers.vue (新增)
   # 供應商列表與範本設定（取代 assignments.vue）
   
✅ /conflict/projects/[id]/progress.vue
   # 進度追蹤（功能不變）
```

### 會員中心頁面（既有）
```
✅ /account/suppliers/index.vue
   # 供應商主檔列表（已完成）
   
✅ /account/suppliers/[id].vue
   # 供應商編輯（已完成）
```

---

## 使用流程調整

### 調整前：兩步驟流程
```
步驟 1: 建立專案（基本資訊）
步驟 2: 進入專案，匯入供應商清單與範本指派
```

### 調整後：一步到位
```
步驟 1: 建立專案
  ├─ 填寫基本資訊
  ├─ 匯入供應商清單 ✅
  ├─ 設定範本指派
  └─ 完成建立

步驟 2: （可選）調整供應商範本設定
  └─ 在 suppliers.vue 頁面調整
```

---

## 開發任務調整

### Phase 2 調整
```
調整前：Phase 2 供應商指派功能（1.5 週）
- Excel 解析器
- 指派 API
- 手動新增頁面

調整後：Phase 2 專案管理與供應商整合（1.5 週）
- ✅ 專案 CRUD（已完成）
- ✅ 匯入供應商功能（已完成）
- 供應商範本設定 API
- 進度追蹤 API
- 通知機制
```

### 任務減少
- ❌ 移除 Excel 解析器開發（已在專案建立完成）
- ❌ 移除獨立指派頁面開發
- ✅ 簡化為供應商列表與範本設定頁面

---

## 優勢總結

### 🎯 架構更簡潔
- 功能模組從 5 個減少到 4 個
- 移除重複的供應商管理邏輯
- 專案建立流程更直觀

### ⚡ 開發效率提升
- 利用既有的會員中心供應商管理
- 利用既有的專案建立匯入功能
- 減少 1 週開發時間

### 🎨 用戶體驗改善
- 一次性完成專案建立與供應商指派
- 減少操作步驟
- 流程更符合實際業務場景

---

## 遷移檢查清單

- [x] 更新功能總覽文件
- [x] 調整開發階段規劃
- [x] 移除供應商管理模組描述
- [x] 更新 API 端點列表（28 個）
- [x] 更新前端頁面結構（20 個）
- [x] 更新資料庫設計說明
- [x] 調整開發任務清單
- [ ] 更新 Swagger API 文件
- [ ] 更新前端 TypeScript 類型定義
- [ ] 更新使用者手冊

---

**更新完成** ✅

主規劃文件：`RESPONSIBLE_MINERALS_IMPLEMENTATION_PLAN.md`  
技術文件：`CONFLICT_MINERALS_EXCEL_IMPORT.md`
