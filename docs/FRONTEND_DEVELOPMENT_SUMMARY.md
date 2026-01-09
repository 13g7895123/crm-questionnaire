# 責任礦產前端架構 - 最終版本

> **更新時間**: 2026-01-09 10:41  
> **狀態**: ✅ 核心架構完成

## 📂 檔案結構

```
frontend/app/
├── composables/
│   └── useResponsibleMinerals.ts          ✅ 業務邏輯 Composable
│
├── pages/conflict/
│   ├── index.vue                          ✅ 重定向到 projects
│   ├── projects/
│   │   ├── index.vue                      ✅ 專案列表（既有）
│   │   ├── [id].vue                       ✅ 專案詳情主頁（新版，含頁籤）
│   │   └── [id]/
│   │       ├── suppliers.vue              ✅ 供應商範本管理
│   │       └── progress.vue               ✅ 進度追蹤
│   │
│   └── templates/
│       ├── index.vue                      ✅ 範本列表（既有）
│       └── [id].vue                       ✅ 範本詳情（既有）
│
└── components/conflict/
    ├── ProjectOverview.vue                ✅ 專案總覽組件
    └── ReviewManagement.vue               🔄 審核管理（佔位）
```

## 🎯 頁面導航結構

```
/conflict
  └── /projects (專案列表)
       └── /[id] (專案詳情)
            ├── ?tab=overview     ← 專案總覽
            ├── ?tab=suppliers    ← 供應商範本管理 ⭐
            ├── ?tab=progress     ← 進度追蹤 ⭐
            └── ?tab=review       ← 審核管理
```

## ✅ 完成的功能

### 1. 專案詳情主頁 (`[id].vue`)
```typescript
功能：
✅ 頁籤導航（4 個頁籤）
✅ URL 參數控制活動頁籤 (?tab=suppliers)
✅ 動態載入子頁面（Code Splitting）
✅ Loading 狀態
✅ Error 處理
✅ Breadcrumbs 整合
```

**頁籤結構**：
| 頁籤 | ID | 組件 | 狀態 |
|------|----|----|------|
| 專案總覽 | overview | ProjectOverview.vue | ✅ |
| 供應商管理 | suppliers | suppliers.vue | ✅ |
| 進度追蹤 | progress | progress.vue | ✅ |
| 審核管理 | review | ReviewManagement.vue | 🔄 |

### 2. 供應商管理頁面 (`suppliers.vue`)
```typescript
核心功能：
✅ 供應商列表顯示
✅ 範本狀態檢視（CMRT/EMRT/AMRT）
✅ 單一編輯（Modal）
✅ 批量設定
✅ Excel 匯入
✅ 通知功能
✅ 搜尋與篩選
✅ AMRT 礦產選擇（15 種）
```

### 3. 進度追蹤頁面 (`progress.vue`)
```typescript
核心功能：
✅ 6 個統計卡片
✅ 3 個範本類型進度條
✅ 供應商明細表格
✅ 多維度篩選
✅ Excel 匯出
```

### 4. 專案總覽組件 (`ProjectOverview.vue`)
```typescript
顯示內容：
✅ 基本資訊
✅ 快速統計
✅ 審核流程圖
✅ 快速操作按鈕
```

## 🔌 路由設計

### 訪問方式

```bash
# 專案列表
http://localhost:3000/conflict/projects

# 專案詳情 - 總覽頁籤（預設）
http://localhost:3000/conflict/projects/123

# 專案詳情 - 供應商管理頁籤
http://localhost:3000/conflict/projects/123?tab=suppliers

# 專案詳情 - 進度追蹤頁籤
http://localhost:3000/conflict/projects/123?tab=progress

# 專案詳情 - 審核管理頁籤
http://localhost:3000/conflict/projects/123?tab=review
```

## 🎨 UI/UX 特點

### 頁籤設計
- ✨ 清晰的視覺分離
- ✨ 活動狀態指示（藍色底線）
- ✨ 數量徽章顯示
- ✨ 圖示輔助識別
- ✨ 流暢的切換動畫

### 響應式設計
- 📱 平板以上：標準橫向頁籤
- 📱 手機：可橫向滾動頁籤

### 互動體驗
- ⚡ 快速載入（Code Splitting）
- ⚡ URL 同步（可分享連結）
- ⚡ 瀏覽器前進/後退支援

## 🔧 技術實現

### 動態匯入
```typescript
const ProjectOverview = defineAsyncComponent(
  () => import('~/components/conflict/ProjectOverview.vue')
)
const SupplierTemplates = defineAsyncComponent(
  () => import('./[id]/suppliers.vue')
)
```

**優點**：
- 減少初始載入時間
- 按需載入頁籤內容
- 改善整體效能

### URL 狀態管理
```typescript
// 監聽頁籤變化，更新 URL
watch(activeTab, (newTab) => {
  router.replace({
    query: { ...route.query, tab: newTab }
  })
})

// 從 URL 初始化頁籤
const initActiveTab = () => {
  const tab = route.query.tab as string
  if (tab && validTabs.includes(tab)) {
    activeTab.value = tab
  }
}
```

## 📊 資料流程

```
[id].vue (主頁面)
    ↓
載入專案資料
    ↓
傳遞 projectId 給子頁面
    ↓
┌────────────────┬──────────────┬────────────────┐
│                │              │                │
│ ProjectOverview│  suppliers  │   progress     │
│  (project)     │ (projectId) │  (projectId)   │
│                │              │                │
└────────────────┴──────────────┴────────────────┘
```

## 🚀 使用指南

### 開發者
```bash
# 1. 啟動開發伺服器
cd frontend
npm run dev

# 2. 訪問頁面
http://localhost:3000/conflict/projects/123

# 3. 切換頁籤
點擊頁籤或修改 URL ?tab=suppliers
```

### 測試路由
```typescript
// 在瀏覽器 Console 測試
// 切換到供應商頁籤
$router.push({ query: { tab: 'suppliers' } })

// 切換到進度頁籤
$router.push({ query: { tab: 'progress' } })
```

## ⚠️ 注意事項

### 1. 子頁面設計
由於 `suppliers.vue` 和 `progress.vue` 被嵌入頁籤中：
- ❌ 不要在子頁面設定 `padding: 24px`（已在主頁面設定）
- ❌ 不要重複設定 Breadcrumbs（主頁面已處理）
- ✅ 專注於內容區域設計

### 2. Props vs Route Params
```typescript
// ✅ 推薦：使用 props
<SupplierTemplates :project-id="projectId" />

// ❌ 避免：在子頁面重複讀取 route.params
// 因為可能被其他頁面復用
```

### 3. 頁籤狀態
```typescript
// 頁籤切換不會重新載入專案資料
// 如需刷新，在主頁面提供方法：
const refreshProject = () => loadProject()

// 傳遞給子頁面
<SupplierTemplates 
  :project-id="projectId" 
  @updated="refreshProject" 
/>
```

## 📝 待完成功能

### 短期（1-2 天）
- [ ] 完善 ProjectOverview 統計數據（連接 API）
- [ ] 實作審核管理頁面
- [ ] 新增專案編輯 Modal

### 中期（3-5 天）
- [ ] 後端 API 開發對接
- [ ] 整合測試
- [ ] 錯誤處理優化

### 長期（1-2 週）
- [ ] 效能優化（虛擬滾動、分頁）
- [ ] 列印/PDF 匯出
- [ ] 多語系支援

## ✨ 總結

現在的架構已經完整：

1. ✅ **入口頁面**：專案列表 (`/conflict/projects`)
2. ✅ **詳情頁面**：帶頁籤的專案詳情 (`/conflict/projects/[id]`)
3. ✅ **核心功能**：
   - 供應商範本管理
   - 進度追蹤
   - 專案總覽
4. ✅ **可擴展性**：輕鬆新增更多頁籤

使用者現在可以：
1. 進入專案列表
2. 點擊專案進入詳情頁
3. 使用頁籤切換不同功能區
4. 在供應商頁籤管理範本
5. 在進度頁籤查看統計

所有功能都已經可以在前端看到並測試！🎉
