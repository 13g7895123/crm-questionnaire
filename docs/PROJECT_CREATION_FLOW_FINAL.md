# 專案建立流程 - 最終版本說明

> **更新日期**: 2026-01-09  
> **版本**: 2.0.0 (Final)

## 🎯 核心概念

### 專案建立的兩階段設計

專案建立採用**分離式設計**，提供最大彈性：

```
階段 1: 建立專案（必要）          階段 2: 範本指派（可選）
├─ 專案基本資訊                    ├─ 供應商範本管理
├─ 匯入供應商清單 ✅ 必要          ├─ 批量/逐一設定範本
├─ 設定審核流程                    ├─ Excel 匯入範本指派
└─ 儲存專案                       └─ 發送填寫邀請

                                  階段 3: 進度追蹤（隨時）
                                  ├─ 查看填寫狀況
                                  ├─ 統計分析
                                  └─ 匯出報表
```

---

## 📋 專案建立流程詳解

### 階段 1: 建立專案（必要步驟）

#### 前端頁面
`/conflict/projects/create.vue`

#### 表單欄位
```typescript
interface ProjectCreateForm {
  // 基本資訊
  name: string              // 專案名稱，如「2025 Q1 供應商調查」
  year: number              // 年份，如 2025
  description?: string      // 專案說明（可選）
  
  // 供應商匯入（必要）
  suppliersFile: File       // CSV/Excel 檔案
  
  // 審核流程設定
  reviewStages: Array<{
    stage: number           // 階段編號（1, 2, 3...）
    departmentId: number    // 審核部門 ID
    approverIds: number[]   // 審核人員 ID 列表
  }>
}
```

#### 供應商匯入檔案格式
```csv
供應商編號,供應商名稱,聯絡窗口,Email
SUP001,ABC Electronics,張三,contact@abc.com
SUP002,XYZ Metal,李四,info@xyz.com
SUP003,DEF Components,王五,sales@def.com
```

#### 資料庫操作
```sql
-- 1. 建立專案
INSERT INTO projects (name, year, type, status) 
VALUES ('2025 Q1 供應商調查', 2025, 'CONFLICT', 'DRAFT');

-- 2. 匯入供應商（建立關聯，但範本指派為空）
INSERT INTO rm_supplier_assignments 
  (project_id, supplier_id, supplier_name, supplier_email, 
   cmrt_required, emrt_required, amrt_required)
VALUES 
  (1, 101, 'ABC Electronics', 'contact@abc.com', FALSE, FALSE, FALSE),
  (1, 102, 'XYZ Metal', 'info@xyz.com', FALSE, FALSE, FALSE),
  (1, 103, 'DEF Components', 'sales@def.com', FALSE, FALSE, FALSE);
  
-- 範本欄位預設值為 FALSE，表示「未指派」
```

#### API 呼叫
```typescript
// 建立專案 API
POST /api/v1/rm/projects
Content-Type: multipart/form-data

{
  "name": "2025 Q1 供應商調查",
  "year": 2025,
  "type": "CONFLICT",
  "suppliersFile": File,
  "reviewStages": [...]
}

// 回應
{
  "success": true,
  "data": {
    "projectId": 123,
    "name": "2025 Q1 供應商調查",
    "suppliersImported": 50,
    "templatesAssigned": 0,  // 尚未指派範本
    "status": "DRAFT"
  }
}
```

---

### 階段 2: 範本指派（可選，專案建立後執行）

#### 前端頁面
`/conflict/projects/[id]/suppliers.vue`

#### 頁面功能

**1. 供應商列表顯示**
```typescript
interface SupplierAssignment {
  supplierId: number
  supplierName: string
  supplierEmail: string
  cmrt_required: boolean    // 是否需要填寫 CMRT
  emrt_required: boolean    // 是否需要填寫 EMRT
  amrt_required: boolean    // 是否需要填寫 AMRT
  amrt_minerals: string[]   // AMRT 礦產清單
  status: 'not_assigned' | 'assigned' | 'in_progress' | 'completed'
}
```

**2. 單一供應商設定範本**
```typescript
// 點擊「編輯」按鈕，彈出對話框
const assignTemplate = async (supplierId: number) => {
  const form = {
    cmrt_required: true,
    emrt_required: true,
    amrt_required: false,
    amrt_minerals: []
  }
  
  await $fetch(
    `/api/v1/rm/projects/${projectId}/suppliers/${supplierId}/templates`,
    {
      method: 'PUT',
      body: form
    }
  )
}
```

**3. 批量設定範本**
```typescript
// 勾選多個供應商，批量指派相同範本
const batchAssignTemplates = async () => {
  const supplierIds = [101, 102, 103]  // 勾選的供應商
  const templates = {
    cmrt_required: true,
    emrt_required: false,
    amrt_required: false
  }
  
  await $fetch(
    `/api/v1/rm/projects/${projectId}/suppliers/batch-assign-templates`,
    {
      method: 'POST',
      body: { supplierIds, templates }
    }
  )
}
```

**4. Excel 匯入範本指派**
```typescript
// Excel 格式
// 供應商編號, CMRT, EMRT, AMRT, AMRT礦產
// SUP001, Yes, Yes, No, 
// SUP002, Yes, No, Yes, "Silver,Platinum"

const importTemplateAssignments = async (file: File) => {
  const formData = new FormData()
  formData.append('file', file)
  
  await $fetch(
    `/api/v1/rm/projects/${projectId}/suppliers/import-template-assignments`,
    {
      method: 'POST',
      body: formData
    }
  )
}
```

**5. 發送通知**
```typescript
// 僅通知「已指派範本」的供應商
const notifySuppliers = async () => {
  await $fetch(
    `/api/v1/rm/projects/${projectId}/suppliers/notify-all`,
    { method: 'POST' }
  )
}
```

---

### 階段 3: 進度追蹤（隨時查詢）

#### 前端頁面
`/conflict/projects/[id]/progress.vue`

#### 資料結構
```typescript
interface ProgressData {
  // 總覽統計
  summary: {
    totalSuppliers: number        // 總供應商數
    assignedSuppliers: number     // 已指派範本數
    notAssignedSuppliers: number  // 未指派範本數
    completedSuppliers: number    // 已完成填寫數
    inProgressSuppliers: number   // 進行中數
    notStartedSuppliers: number   // 未開始數
  }
  
  // 範本類型統計
  templateStats: {
    cmrt: { total: number, completed: number, percentage: number }
    emrt: { total: number, completed: number, percentage: number }
    amrt: { total: number, completed: number, percentage: number }
  }
  
  // 供應商明細
  suppliers: Array<{
    supplierId: number
    supplierName: string
    assignedTemplates: string[]   // ["CMRT", "EMRT"]
    status: string                // "已提交", "進行中", "未指派"
    completionRate: number        // 完成度 0-100
    lastUpdated: string           // 最後更新時間
  }>
}
```

#### API 呼叫
```typescript
// 取得進度資料
const progressData = await $fetch(`/api/v1/rm/projects/${projectId}/progress`)

// 取得供應商明細狀態
const supplierStatus = await $fetch(
  `/api/v1/rm/projects/${projectId}/suppliers/status`
)

// 匯出進度報表
const exportProgress = async () => {
  const blob = await $fetch(
    `/api/v1/rm/projects/${projectId}/export/progress`,
    { responseType: 'blob' }
  )
  // 下載 Excel 檔案
}
```

---

## 🔄 完整使用流程範例

### 情境：建立 2025 Q1 供應商調查專案

#### 步驟 1: 建立專案
```
操作者：專案管理員
頁面：/conflict/projects/create.vue

動作：
1. 填寫專案名稱「2025 Q1 供應商調查」
2. 選擇年份 2025
3. 上傳供應商清單 Excel（50 家供應商）
4. 設定審核流程（2 階段審核）
5. 點擊「建立專案」

結果：
- 專案建立成功
- 50 家供應商匯入成功
- 所有供應商範本狀態為「未指派」
- 專案狀態：草稿
```

#### 步驟 2: 指派範本（專案建立後 1 天）
```
操作者：專案管理員
頁面：/conflict/projects/123/suppliers.vue

動作：
1. 批量選擇 30 家電子廠 → 指派 CMRT + EMRT
2. 單獨設定 10 家電池廠 → 指派 EMRT（鈷、鋰、鎳）
3. 單獨設定 5 家貴金屬廠 → 指派 AMRT（銀、鉑）
4. 保留 5 家其他供應商暫不指派
5. 點擊「全部通知」→ 發送填寫邀請給已指派的 45 家

結果：
- 45 家供應商收到填寫邀請 Email
- 5 家供應商仍為「未指派」狀態
- 可以隨時回到此頁面調整範本設定
```

#### 步驟 3: 追蹤進度（填寫期間）
```
操作者：專案管理員
頁面：/conflict/projects/123/progress.vue

查看內容：
- 總覽：50 家供應商，45 家已指派，5 家未指派
- 已完成：20 家
- 進行中：15 家
- 未開始：10 家（已指派但未填寫）

範本統計：
- CMRT: 30/30 已指派，15 已完成 (50%)
- EMRT: 40/40 已指派，25 已完成 (62%)
- AMRT: 5/5 已指派，3 已完成 (60%)

動作：
- 篩選「未開始」供應商
- 發送提醒通知
- 匯出進度報表給主管
```

---

## 📊 資料流程圖

```
┌─────────────────┐
│  1. 建立專案     │
│  ✅ 供應商匯入  │ ────┐
└─────────────────┘     │
                        ▼
               ┌─────────────────┐
               │ rm_supplier_     │
               │ assignments      │
               │ (範本=FALSE)     │
               └─────────────────┘
                        │
                        ▼
┌─────────────────┐    │
│  2. 範本指派     │    │
│  (可選，延後)    │ ───┤
└─────────────────┘    │
                        ▼
               ┌─────────────────┐
               │ 更新範本欄位     │
               │ cmrt=TRUE        │
               │ emrt=TRUE        │
               └─────────────────┘
                        │
                        ▼
┌─────────────────┐    │
│  3. 進度追蹤     │    │
│  (隨時查詢)      │ <──┘
└─────────────────┘
```

---

## ✅ 優勢總結

### 1. **彈性十足**
- ✅ 專案建立不強制設定範本
- ✅ 可以先建立專案，再逐步規劃範本需求
- ✅ 支援分批指派（例如先指派緊急的，再指派一般的）

### 2. **操作直觀**
- ✅ 階段分明：建立 → 指派 → 追蹤
- ✅ 每個階段有專屬頁面
- ✅ 進度一目了然

### 3. **符合實務**
- ✅ 專案建立時可能尚未確定所有供應商需求
- ✅ 允許動態調整範本指派
- ✅ 支援分階段通知供應商

### 4. **完整追蹤**
- ✅ 清楚顯示「未指派」vs「已指派」
- ✅ 統計各範本類型的完成度
- ✅ 可匯出完整進度報表

---

## 📁 相關文件

- `RESPONSIBLE_MINERALS_IMPLEMENTATION_PLAN.md` - 完整開發規劃
- `CONFLICT_MINERALS_EXCEL_IMPORT.md` - 技術規格文件
- `RM_PLANNING_UPDATE_LOG.md` - 調整歷程記錄

---

**文件版本**: 2.0.0 (Final)  
**最後更新**: 2026-01-09  
**狀態**: ✅ 已確定，可開始開發
