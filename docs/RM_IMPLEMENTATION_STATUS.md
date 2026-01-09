# 責任礦產問卷系統 - 實作完成狀況

> **更新時間**: 2026-01-09  
> **基準文件**: `RESPONSIBLE_MINERALS_IMPLEMENTATION_PLAN.md`

## 📋 目錄

- [整體進度總覽](#整體進度總覽)
- [功能模組完成狀況](#功能模組完成狀況)
- [前端實作狀況](#前端實作狀況)
- [後端實作狀況](#後端實作狀況)
- [架構調整說明](#架構調整說明)
- [下一步工作](#下一步工作)

---

## 整體進度總覽

| 項目 | 預計 | 已完成 | 完成度 |
|------|------|--------|--------|
| **前端頁面** | 20 | 5 | 25% |
| **API 端點** | 31 | 0 | 0% |
| **資料庫設計** | 已規劃 | 未實作 | 0% |
| **核心功能** | - | 範本組管理、供應商指派 | 30% |

### 開發階段進度

| 階段 | 內容 | 狀態 | 完成度 |
|------|------|------|--------|
| Phase 1 | 專案與範本基礎 | 🟡 進行中 | 40% |
| Phase 2 | 供應商與指派 | 🟡 進行中 | 60% |
| Phase 3 | 問卷填寫 | ⚪ 未開始 | 0% |
| Phase 4 | 審核與流程 | ⚪ 未開始 | 0% |
| Phase 5 | 報表與匯出 | ⚪ 未開始 | 0% |

---

## 功能模組完成狀況

### 模組 1: 範本組管理 ✅ (前端完成 80%)

#### 規劃功能
1. ✅ 範本組 CRUD（新增、編輯、刪除）
2. ✅ CMRT/EMRT/AMRT 範本組合設定
3. ✅ 版本號管理
4. ✅ AMRT 礦產選擇（1-10 種）
5. ⚪ 範本內容編輯器（未開始）

#### 已完成
- ✅ `/conflict/templates/index.vue` - 範本組管理頁面
  - 範本組列表顯示（卡片式）
  - 新增/編輯範本組 Modal
  - 三種範本類型選擇（CMRT/EMRT/AMRT）
  - 版本號設定與建議
  - AMRT 礦產多選（最多 10 種）
  - 搜尋與篩選功能
  - 批量刪除

- ✅ `/composables/useTemplateSets.ts` - 範本組 Composable
  - CRUD API 呼叫方法
  - 範本組摘要生成
  - 已啟用範本類型取得

#### 待完成
- ⚪ 範本內容編輯（Excel 欄位對應）
- ⚪ 範本預覽功能
- ⚪ 範本版本歷程

---

### 模組 2: 專案管理 🟡 (前端完成 50%)

#### 規劃功能
1. ✅ 專案 CRUD
2. ⚪ 專案建立時選擇範本組
3. ⚪ 供應商清單匯入（Excel/CSV）
4. ✅ 專案詳情頁籤導航
5. ✅ 專案總覽儀表板
6. ⚪ 審核流程設定

#### 已完成
- ✅ `/conflict/projects/[id].vue` - 專案詳情主頁
  - 頁籤導航（總覽/供應商/進度/審核）
  - URL 參數控制頁籤
  - 動態載入子頁面
  - 響應式設計

- ✅ `/components/conflict/ProjectOverview.vue` - 專案總覽
  - 基本資訊卡片（使用 UCard）
  - 快速統計（供應商數、完成度等）
  - 審核流程顯示
  - 快速操作按鈕

- ✅ `/conflict/projects/index.vue` - 專案列表（既有）

#### 待完成
- ⚪ 專案建立表單
- ⚪ 範本組選擇
- ⚪ 供應商清單匯入
- ⚪ 審核流程設定

---

### 模組 3: 供應商範本指派 ✅ (前端完成 90%)

#### 規劃功能
1. ✅ 供應商列表顯示
2. ✅ 單一供應商範本設定
3. ✅ 批量範本設定
4. ✅ Excel 匯入範本指派
5. ✅ Excel 範本下載
6. ✅ 供應商通知（單一/批量）
7. ✅ AMRT 礦產選擇

#### 已完成
- ✅ `/conflict/projects/[id]/suppliers.vue` - 供應商管理
  - 供應商列表表格
  - CMRT/EMRT/AMRT 狀態顯示
  - 單一編輯 Modal
    - 三種範本類型勾選
    - AMRT 礦產多選（最多 10 種）
  - 批量設定 Modal
  - Excel 匯入功能
  - 範本下載
  - 通知功能（單一/批量）
  - 搜尋與篩選

- ✅ `/composables/useResponsibleMinerals.ts` - RM Composable
  - 供應商範本管理 API
  - 批量指派 API
  - Excel 匯入/匯出 API
  - 通知 API

#### 待完成
- ⚪ 供應商填寫進度顯示（與專案範本組關聯）

---

### 模組 4: 進度追蹤與報表 ✅ (前端完成 80%)

#### 規劃功能
1. ✅ 統計卡片（6 個指標）
2. ✅ 範本類型進度條
3. ✅ 供應商明細表格
4. ✅ 篩選與排序
5. ✅ Excel 匯出

#### 已完成
- ✅ `/conflict/projects/[id]/progress.vue` - 進度追蹤
  - 6 個統計卡片
    - 總供應商數
    - 已指派/未指派範本
    - 已完成/進行中/未開始
  - 3 個範本類型進度條（CMRT/EMRT/AMRT）
  - 供應商明細表格
    - 範本type 標籤
    - 狀態徽章
    - 完成度進度條
    - 最後更新時間
  - 多維度篩選（範本類型、狀態）
  - Excel 匯出

#### 待完成
- ⚪ 即時進度推送
- ⚪ 歷史進度趨勢圖

---

### 模組 5: 問卷填寫 ⚪ (未開始 0%)

#### 規劃功能
1. ⚪ 供應商登入介面
2. ⚪ CMRT 問卷填寫頁面
3. ⚪ EMRT 問卷填寫頁面
4. ⚪ AMRT 問卷填寫頁面
5. ⚪ 問卷暫存
6. ⚪ 問卷提交
7. ⚪ 冶煉廠資料查詢與填寫
8. ⚪ 附件上傳

#### 已完成
- ⚪ 無

---

### 模組 6: 審核管理 ⚪ (未開始 5%)

#### 規劃功能
1. ⚪ 審核流程執行
2. ⚪ 問卷審核介面
3. ⚪ 核准/退回功能
4. ⚪ 審核意見填寫
5. ⚪ 審核歷程記錄

#### 已完成
- ✅ `/components/conflict/ReviewManagement.vue` - 佔位組件

---

## 前端實作狀況

### 已完成頁面 (5/20)

| 頁面 | 路徑 | 功能 | 狀態 |
|------|------|------|------|
| 專案列表 | `/conflict/projects/index.vue` | 顯示所有專案 | ✅ 既有 |
| 專案詳情 | `/conflict/projects/[id].vue` | 頁籤導航主頁 | ✅ 新版 |
| 專案總覽 | `ProjectOverview.vue` | 基本資訊與統計 | ✅ 新建 |
| 範本組管理 | `/conflict/templates/index.vue` | CRUD  範本組 | ✅ 重構 |
| 供應商管理 | `/conflict/projects/[id]/suppliers.vue` | 指派範本 | ✅ 新建 |
| 進度追蹤 | `/conflict/projects/[id]/progress.vue` | 追蹤進度 | ✅ 新建 |
| 審核管理 | `ReviewManagement.vue` | 佔位 | 🟡 佔位 |

### 待完成頁面 (15/20)

- ⚪ 專案建立頁面
- ⚪ 範本內容編輯
- ⚪ 供應商登入頁面
- ⚪ CMRT 問卷填寫
- ⚪ EMRT 問卷填寫
- ⚪ AMRT 問卷填寫
- ⚪ 問卷審核頁面
- ⚪ 審核歷程頁面
- ⚪ 報表匯出頁面
- ⚪ 等...

### 已完成 Composables (2/2)

| Composable | 檔案 | 功能 | 狀態 |
|-----------|------|------|------|
| 範本組管理 | `useTemplateSets.ts` | CRUD、工具函數 | ✅ |
| RM 管理 | `useResponsibleMinerals.ts` | 供應商、通知、進度 | ✅ |

---

## 後端實作狀況

### API 端點完成度 (0/31)

#### 已規劃但未實作 (31個)

**範本組管理 (5個)**
- ⚪ `GET /api/v1/rm/template-sets` - 取得範本組列表
- ⚪ `POST /api/v1/rm/template-sets` - 建立範本組
- ⚪ `GET /api/v1/rm/template-sets/{id}` - 取得範本組詳情
- ⚪ `PUT /api/v1/rm/template-sets/{id}` - 更新範本組
- ⚪ `DELETE /api/v1/rm/template-sets/{id}` - 刪除範本組

**專案管理 (8個)**
- ⚪ `GET /api/v1/rm/projects` - 取得專案列表
- ⚪ `POST /api/v1/rm/projects` - 建立專案
- ⚪ `GET /api/v1/rm/projects/{id}` - 取得專案詳情
- ⚪ `PUT /api/v1/rm/projects/{id}` - 更新專案
- ⚪ `DELETE /api/v1/rm/projects/{id}` - 刪除專案
- ⚪ `POST /api/v1/rm/projects/{id}/suppliers/import` - 匯入供應商清單
- ⚪ `GET /api/v1/rm/projects/{id}/suppliers` - 取得專案供應商列表
- ⚪ `GET /api/v1/rm/projects/{id}/progress` - 取得專案進度

**供應商範本指派 (6個)**
- ⚪ `PUT /api/v1/rm/projects/{id}/suppliers/{supplierId}/templates` - 設定供應商範本
- ⚪ `POST /api/v1/rm/projects/{id}/suppliers/batch-assign-templates` - 批量設定範本
- ⚪ `POST /api/v1/rm/projects/{id}/suppliers/import-template-assignments` - Excel 匯入指派
- ⚪ `GET /api/v1/rm/projects/{id}/suppliers/template-assignment-template` - 下載 Excel 範本
- ⚪ `POST /api/v1/rm/projects/{id}/suppliers/{supplierId}/notify` - 通知單一供應商
- ⚪ `POST /api/v1/rm/projects/{id}/suppliers/notify-all` - 批量通知

**問卷填寫 (4個)**
- ⚪ `GET /api/v1/rm/questionnaires/{id}` - 取得問卷
- ⚪ `PUT /api/v1/rm/questionnaires/{id}` - 更新問卷（暫存）
- ⚪ `POST /api/v1/rm/questionnaires/{id}/submit` - 提交問卷
- ⚪ `GET /api/v1/rm/smelters/search` - 搜尋冶煉廠

**審核管理 (4個)**
- ⚪ `GET /api/v1/rm/reviews/{projectId}` - 取得待審核列表
- ⚪ `POST /api/v1/rm/reviews/{reviewId}/approve` - 核准
- ⚪ `POST /api/v1/rm/reviews/{reviewId}/return` - 退回
- ⚪ `GET /api/v1/rm/reviews/{supplierId}/history` - 審核歷程

**報表匯出 (4個)**
- ⚪ `GET /api/v1/rm/projects/{id}/export/progress` - 匯出進度報表
- ⚪ `GET /api/v1/rm/projects/{id}/export/cmrt` - 匯出 CMRT
- ⚪ `GET /api/v1/rm/projects/{id}/export/emrt` - 匯出 EMRT
- ⚪ `GET /api/v1/rm/projects/{id}/export/amrt` - 匯出 AMRT

### 資料庫設計 (0%)

所有資料表皆未實作，需根據 `RESPONSIBLE_MINERALS_IMPLEMENTATION_PLAN.md` 的設計建立：

- ⚪ `rm_template_sets` - 範本組
- ⚪ `rm_projects` - 專案
- ⚪ `rm_supplier_assignments` - 供應商指派
- ⚪ `rm_questionnaire_responses` - 問卷回答
- ⚪ `rm_smelter_list` - 冶煉廠清單
- ⚪ `rm_review_records` - 審核記錄

---

## 架構調整說明

### 重大架構變更

#### 1. 範本管理 → 範本組管理 ✅ 已調整

**原設計**：
```
單獨的 CMRT、EMRT、AMRT 範本
問題：專案建立時不知道要用哪三個範本
```

**新設計**：
```
範本組（Template Set）
├── 包含 CMRT 6.5
├── 包含 EMRT 2.1
└── 包含 AMRT 1.21 + 選定的礦產

優點：
✅ 專案建立時選擇範本組
✅ 保證範本版本一致性
✅ 供應商指派基於範本組
```

**實作狀況**：
- ✅ Composable: `useTemplateSets.ts`
- ✅ 頁面: `/conflict/templates/index.vue`
- ⚪ 後端 API: 待實作

#### 2. 專案建立流程調整 ⚪ 待實作

**調整內容**：
```
階段 1: 專案基本資訊 + 選擇範本組
階段 2: 匯入供應商清單
階段 3: 設定審核流程
階段 4: 完成建立
```

**變更點**：
- 新增「選擇範本組」步驟
- 專案與範本組關聯
- 供應商指派限定在範本組的範本類型內

#### 3. UI 風格統一 ✅ 已完成

- 移除所有 emoji，改用 Heroicons
- 使用 Nuxt UI 組件（UCard, UButton, UBadge）
- 減少左右 padding，移除 max-width 限制
- 響應式設計

---

## 下一步工作

### 短期優先 (1-2 週)

#### 後端開發
1. **範本組 API** (優先度: 🔴 高)
   - 實作 5 個範本組 CRUD API
   - 建立 `rm_template_sets` 資料表

2. **專案 API** (優先度: 🔴 高)
   - 實作 8 個專案管理 API
   - 建立 `rm_projects` 資料表
   - 實作範本組關聯

3. **供應商指派 API** (優先度: 🔴 高)
   - 實作 6 個供應商指派 API
   - 建立 `rm_supplier_assignments` 資料表
   - Excel 匯入/匯出功能

#### 前端開發
1. **專案建立頁面** (優先度: 🔴 高)
   - 步驟式表單
   - 範本組選擇
   - 供應商清單匯入
   - 審核流程設定

2. **API 對接 Testing** (優先度: 🔴 高)
   - 測試所有已完成前端頁面
   - 修正 API 呼叫問題
   - 錯誤處理優化

### 中期規劃 (2-4 週)

1. **問卷填寫功能**
   - CMRT/EMRT/AMRT 問卷頁面
   - 冶煉廠資料查詢
   - 問卷暫存與提交

2. **審核流程**
   - 審核介面
   - 核准/退回功能
   - 審核歷程

3. **報表匯出**
   - Excel 匯出功能
   - PDF 報表生成

### 長期目標 (1-2 個月)

1. **系統優化**
   - 效能優化
   - 使用者體驗改善
   - 安全性強化

2. **進階功能**
   - 即時通知
   - 郵件提醒
   - 資料分析儀表板

---

## 📊 總結

### 已完成
- ✅ 範本組管理前端（完整 UI/UX）
- ✅ 供應商範本指派前端（完整功能）
- ✅ 進度追蹤前端（統計與明細）
- ✅ 專案詳情頁籤架構
- ✅ 2 個核心 Composables
- ✅ UI 風格統一與優化

### 進行中
- 🟡 架構調整（範本組概念）
- 🟡 前端頁面開發（5/20 完成）

### 待開始
- ⚪ 所有後端 API（0/31）
- ⚪ 資料庫建立（0%）
- ⚪ 問卷填寫功能（0%）
- ⚪ 審核管理功能（0%）

### 預估完成時間
- **前端完整**: 2-3 週
- **後端完整**: 4-6 週
- **整體系統**: 6-8 週

---

*本文件將持續更新，反映最新的實作進度。*
