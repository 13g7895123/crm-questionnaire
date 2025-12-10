# 範本預覽功能使用指南

## 📍 訪問路徑

### 1. 範本列表頁
**URL**: `http://localhost:3000/templates`

**功能**:
- 顯示所有可用的問卷範本
- 每個範本顯示名稱、類型、描述、版本
- v2.0 新架構範本會特別標註「v2.0 新架構」

**操作**:
- 點擊任何範本卡片 → 進入預覽頁面
- 點擊右上角「建立新範本」→ 建立新範本（功能待開發）

### 2. 範本預覽頁
**URL**: `http://localhost:3000/templates/{id}/preview`

**範例**:
- `http://localhost:3000/templates/1/preview` - 舊版 SAQ 範本
- `http://localhost:3000/templates/4/preview` - 新版 SAQ 完整範本 v2.0

**功能**:
- 顯示完整的問卷流程
- 步驟式導航（含進度條）
- 互動式問題預覽
- 支援條件邏輯（v2.0）
- 支援表格問題（v2.0）

---

## 🎯 快速開始

### 方式 1: 從範本列表進入（推薦）

1. 啟動前端服務：
   ```bash
   cd frontend
   npm run dev
   ```

2. 打開瀏覽器訪問：
   ```
   http://localhost:3000/templates
   ```

3. 點擊「SAQ 完整範本 v2.0」卡片

4. 自動進入預覽頁面

### 方式 2: 直接訪問預覽頁

直接在瀏覽器輸入：
```
http://localhost:3000/templates/4/preview
```

---

## 🔧 整合狀態

### ✅ 已整合功能

1. **範本列表頁** (`/templates`)
   - ✅ 從 API 載入範本列表
   - ✅ 顯示 v2.0 新架構標籤
   - ✅ 點擊卡片跳轉到預覽頁
   - ✅ 錯誤處理（API 失敗時使用後備資料）

2. **範本預覽頁** (`/templates/[id]/preview`)
   - ✅ 從 API 載入範本結構
   - ✅ 動態生成步驟列表
   - ✅ 支援基本資訊步驟（可選）
   - ✅ 支援多區段動態顯示
   - ✅ 載入狀態與錯誤處理

### 🚧 待完成功能

1. **預覽頁內容渲染**
   - ⏳ 需要更新 `QuestionnaireStepOneBasicInfo` 元件以支援 v2.0 基本資訊格式
   - ⏳ 需要更新 `QuestionnaireStepDynamicQuestions` 元件以渲染 v2.0 問題結構
   - ⏳ 需要建立新的問題元件以支援條件邏輯和表格問題

2. **範本編輯功能**
   - ⏳ `/templates/create` - 建立新範本
   - ⏳ `/templates/[id]/edit` - 編輯現有範本

---

## 📊 API 整合說明

### 範本列表 API
```
GET /api/v1/templates
```

**回應格式**:
```json
{
  "success": true,
  "data": [
    {
      "id": 4,
      "name": "SAQ 完整範本 v2.0",
      "type": "SAQ",
      "latestVersion": "2.0.0",
      "hasV2Structure": true,
      "createdAt": "2025-12-04T10:00:00.000Z"
    }
  ]
}
```

### 範本結構 API
```
GET /api/v1/templates/{id}/structure
```

**回應格式**:
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

---

## 🎨 元件結構

### 範本列表頁元件層級
```
templates/index.vue
  └─ 範本卡片 (點擊跳轉)
```

### 範本預覽頁元件層級
```
templates/[id]/preview.vue
  ├─ 步驟導航條
  ├─ QuestionnaireStepOneBasicInfo (步驟 1)
  ├─ QuestionnaireStepDynamicQuestions (步驟 2-N)
  └─ QuestionnaireStepResults (最後一步)
```

---

## 🔍 除錯建議

### 查看 API 回應

在預覽頁面，打開瀏覽器開發者工具（F12）:

1. **Network 標籤**
   - 查看 `/api/v1/templates/{id}/structure` 請求
   - 確認回應狀態碼是否為 200
   - 檢查回應資料格式

2. **Console 標籤**
   - 查看是否有 JavaScript 錯誤
   - 檢查 `templateStructure` 物件內容

### 常見問題

**Q1: 預覽頁面顯示「載入中...」不消失**
- 檢查後端服務是否啟動
- 確認範本 ID 是否存在
- 查看 Network 標籤是否有 404 錯誤

**Q2: 範本列表是空的**
- 確認已執行 `CompleteSAQTemplateSeeder`
- 檢查 `templates` 表是否有資料
- 查看 API 回應是否正確

**Q3: 點擊範本卡片沒反應**
- 檢查瀏覽器 Console 是否有錯誤
- 確認路由配置正確
- 嘗試手動輸入 URL

---

## 🚀 下一步開發建議

### 優先度 1: 完成預覽頁渲染
1. 更新 `QuestionnaireStepDynamicQuestions.vue` 以支援 v2.0 結構
2. 建立條件邏輯問題元件
3. 建立表格問題元件
4. 整合可見問題 API (`/visible-questions`)

### 優先度 2: 範本編輯功能
1. 建立 `/templates/create` 頁面
2. 建立視覺化範本編輯器
3. 實作條件邏輯編輯介面
4. 實作表格欄位配置介面

### 優先度 3: 範本管理功能
1. 範本複製功能
2. 範本版本管理
3. 範本刪除功能
4. 範本匯入/匯出

---

## 📞 技術支援

**相關文件**:
- `frontend/docs/template-v2-integration.md` - 前端整合指南
- `backend/docs/API-SPECIFICATION.md` - API 文件
- `docs/TEMPLATE-V2-TESTING-PLAN.md` - 測試計劃

**最後更新**: 2025-12-10
