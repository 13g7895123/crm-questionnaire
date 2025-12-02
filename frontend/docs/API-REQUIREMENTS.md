# CRM 問卷系統 - 前端 API 需求文件

> **版本**: 1.0.0  
> **最後更新**: 2025-12-02  
> **專案**: CRM Questionnaire System (Frontend)

## 📋 文件概述

本文件為 CRM 問卷系統前端應用程式的 API 需求規格說明，涵蓋所有前端與後端 API 介面的互動需求。

## 🎯 文件目的

- 定義前端應用程式與後端 API 的介面規格
- 提供開發團隊明確的 API 使用指引
- 確保前後端開發的一致性與協同作業
- 作為 API 實作與測試的參考依據

## 🏗️ 系統架構概述

### 技術棧

- **前端框架**: Nuxt 3 (Vue 3)
- **狀態管理**: Pinia
- **HTTP 客戶端**: Native Fetch API (wrapped in composables)
- **語系支援**: Vue I18n (繁體中文 zh-TW、英文 en)
- **UI 框架**: @nuxt/ui (Tailwind CSS)

### API 設計原則

1. **RESTful 風格**: 遵循 REST 架構風格
2. **統一認證**: 使用 JWT Bearer Token 進行身份驗證
3. **錯誤處理**: 統一的錯誤回應格式
4. **資料格式**: JSON 格式的請求與回應
5. **版本控制**: API 版本控制策略（未來擴充）

### Base URL

```
開發環境: http://localhost:3000/api
生產環境: https://api.crm-questionnaire.example.com/api
```

## 📚 API 模組分類

系統 API 依據功能劃分為以下模組：

### 1. [認證與授權 (Authentication & Authorization)](./api/auth.md)
- 使用者登入
- 使用者登出
- Token 驗證
- 權限檢查

### 2. [會員中心與用戶管理 (User Management)](./api/users.md)
- 取得目前使用者資訊
- 更新個人資料
- 修改密碼
- 使用者列表查詢

### 3. [部門管理 (Department Management)](./api/departments.md)
- 部門列表查詢
- 建立部門
- 更新部門
- 刪除部門

### 4. [SAQ 專案管理 (SAQ Project Management)](./api/saq-projects.md)
- SAQ 專案列表查詢
- 建立 SAQ 專案
- 更新 SAQ 專案
- 刪除 SAQ 專案
- 取得專案詳情

### 5. [衝突資產專案管理 (Conflict Minerals Project Management)](./api/conflict-projects.md)
- 衝突資產專案列表查詢
- 建立衝突資產專案
- 更新衝突資產專案
- 刪除衝突資產專案
- 取得專案詳情

### 6. [範本管理 (Template Management)](./api/templates.md)
- 範本列表查詢
- 建立範本
- 更新範本
- 刪除範本
- 範本版本管理
- 題目管理

### 7. [問卷填寫與答案 (Questionnaire Answering)](./api/answers.md)
- 取得專案答案
- 儲存答案（草稿）
- 提交答案
- 答案驗證

### 8. [多階段審核 (Multi-stage Review)](./api/reviews.md)
- 待審核專案列表
- 取得審核歷程
- 核准專案
- 退回專案
- 審核流程設定

### 9. [供應商管理 (Supplier Management)](./api/suppliers.md)
- 供應商列表查詢
- 供應商資訊

## 🔐 認證機制

所有需要認證的 API 請求必須在 HTTP Header 中包含 JWT Token：

```http
Authorization: Bearer <jwt_token>
```

Token 由登入 API 取得，並儲存於前端（LocalStorage/Cookie）。

## 📊 統一回應格式

### 成功回應

```json
{
  "data": { ... },
  "message": "Success message (optional)"
}
```

### 錯誤回應

```json
{
  "error": "Error code or message",
  "message": "Human-readable error description",
  "details": { ... } // Optional detailed error info
}
```

### HTTP 狀態碼

- `200 OK`: 請求成功
- `201 Created`: 資源建立成功
- `400 Bad Request`: 請求參數錯誤
- `401 Unauthorized`: 未認證或 Token 無效
- `403 Forbidden`: 無權限存取
- `404 Not Found`: 資源不存在
- `409 Conflict`: 資源衝突
- `500 Internal Server Error`: 伺服器錯誤

## 🧪 API 測試策略

1. **單元測試**: 使用 Vitest 測試 API composables
2. **整合測試**: 測試 API 與元件的整合
3. **端對端測試**: 測試完整的使用者流程

## 📖 使用範例

### 在 Composable 中使用

```typescript
// 使用 useProjects composable
const { fetchProjects, createProject } = useProjects()

// 取得專案列表
const projects = await fetchProjects('SAQ')

// 建立新專案
const newProject = await createProject({
  name: '2025 SAQ 問卷',
  year: 2025,
  templateId: 'template-123',
  supplierId: 'supplier-456'
})
```

### 在 Vue 元件中使用

```vue
<script setup lang="ts">
const { fetchProjects, projects } = useProjects()

onMounted(async () => {
  await fetchProjects('SAQ')
})
</script>
```

## 🔄 資料模型

詳細的資料模型定義請參考：[Data Models](./api/data-models.md)

## ⚠️ 錯誤處理

詳細的錯誤處理規範請參考：[Error Handling](./api/error-handling.md)

## 📝 變更記錄

| 版本 | 日期 | 變更內容 | 作者 |
|------|------|---------|------|
| 1.0.0 | 2025-12-02 | 初始版本 | Development Team |

## 📞 聯絡資訊

如有 API 相關問題或建議，請聯繫：
- 前端開發團隊
- 後端開發團隊

---

**注意**: 本文件會隨專案開發持續更新，請定期查閱最新版本。
