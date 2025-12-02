# API 需求文件

本目錄包含 CRM 問卷系統的完整 API 需求文件。

## 📚 文件索引

### 總覽
- **[api-requirements.md](./api-requirements.md)** - API 總覽與通用規範
  - 基礎資訊 (Base URL, 認證機制)
  - 錯誤處理與狀態碼
  - 分頁、排序、搜尋參數說明

### 核心模組

#### 1. 認證與使用者
- **[api-auth.md](./api-auth.md)** - 認證相關 API
  - 登入 / 登出
  - Token 管理 (更新、驗證)
  - 安全性考量
  
- **[api-users.md](./api-users.md)** - 使用者管理 API
  - 個人資料管理
  - 密碼修改
  - 使用者 CRUD (管理員)

#### 2. 組織架構
- **[api-organizations.md](./api-organizations.md)** - 組織管理 API
  - 組織 CRUD
  - HOST (製造商) vs SUPPLIER (供應商)
  - 供應商列表查詢
  
- **[api-departments.md](./api-departments.md)** - 部門管理 API
  - 部門 CRUD
  - 部門與使用者關聯
  - 審核流程部門設定

#### 3. 專案與範本
- **[api-projects.md](./api-projects.md)** - 專案管理 API
  - 專案 CRUD
  - 專案指派給供應商
  - 審核流程設定
  - 專案狀態管理
  
- **[api-templates.md](./api-templates.md)** - 範本管理 API
  - 範本 CRUD
  - 範本版本控制
  - 題目管理 (8 種題型)
  - 題目設定與驗證規則

#### 4. 問卷填寫與審核
- **[api-answers.md](./api-answers.md)** - 問卷填寫 API
  - 取得/暫存答案
  - 提交專案
  - 檔案上傳
  - 答案驗證規則
  
- **[api-reviews.md](./api-reviews.md)** - 審核流程 API
  - 待審核專案列表
  - 核准/退回專案
  - 審核歷程查詢
  - 多階段審核流程

## 🔗 相關文件

### 規格文件
- **Spec**: `/specs/003-crm-questionnaire/spec.md` - 功能規格
- **Plan**: `/specs/003-crm-questionnaire/plan.md` - 實作計畫
- **Data Model**: `/specs/003-crm-questionnaire/data-model.md` - 資料模型
- **OpenAPI**: `/specs/003-crm-questionnaire/contracts/openapi.yaml` - OpenAPI 規格

## 📊 API 端點統計

| 模組 | 端點數量 | 文件 |
|------|---------|------|
| 認證 | 5 | api-auth.md |
| 使用者 | 6 | api-users.md |
| 組織 | 6 | api-organizations.md |
| 部門 | 5 | api-departments.md |
| 專案 | 6 | api-projects.md |
| 範本 | 7 | api-templates.md |
| 問卷填寫 | 4 | api-answers.md |
| 審核流程 | 4 | api-reviews.md |
| **總計** | **43** | - |

## 🎯 快速導航

### 我是前端開發者
建議閱讀順序:
1. [api-requirements.md](./api-requirements.md) - 了解通用規範
2. [api-auth.md](./api-auth.md) - 實作登入/登出
3. [api-projects.md](./api-projects.md) - 實作專案列表
4. [api-answers.md](./api-answers.md) - 實作問卷填寫
5. [api-reviews.md](./api-reviews.md) - 實作審核功能

### 我是後端開發者
建議閱讀順序:
1. [api-requirements.md](./api-requirements.md) - 了解 API 架構
2. `/specs/003-crm-questionnaire/data-model.md` - 了解資料模型
3. 依模組實作各 API 端點
4. 參考 [openapi.yaml](../../../specs/003-crm-questionnaire/contracts/openapi.yaml)

### 我是測試人員
建議閱讀順序:
1. [api-requirements.md](./api-requirements.md) - 了解錯誤處理
2. 各模組文件中的「使用情境說明」章節
3. 各模組文件中的「Error Responses」章節

## 🔑 重要概念

### 角色與權限
- **HOST** (製造商): 建立專案、管理範本、審核
- **SUPPLIER** (供應商): 填寫問卷、檢視被指派的專案
- **ADMIN**: 系統管理員，管理組織與部門

### 專案狀態流程
```
DRAFT → IN_PROGRESS → SUBMITTED → REVIEWING → APPROVED
                                              ↓
                                          RETURNED → IN_PROGRESS
```

### 審核流程
- 可設定 1-5 個審核階段
- 每個階段指定負責部門
- 支援核准 (進入下一階段) 或退回 (回到填寫階段)

### 範本版本控制
- 範本支援多版本 (Semantic Versioning)
- 專案建立時鎖定特定版本
- 範本修改不影響已建立的專案

## 📝 文件編寫規範

本文件遵循以下規範:

1. **結構一致**: 所有 API 文件使用相同的結構
   - 目錄
   - API 端點詳細說明
   - Request/Response 範例
   - Error Responses
   - 使用情境說明

2. **範例完整**: 每個 API 包含完整的 Request/Response JSON 範例

3. **錯誤處理**: 列出所有可能的錯誤情況與對應的 HTTP 狀態碼

4. **中文說明**: 所有文件使用繁體中文，欄位說明清晰

5. **使用情境**: 提供實際使用情境幫助理解 API 用途

## 🚀 使用方式

### 前端開發
```javascript
// 參考 api-auth.md
const response = await fetch('/api/v1/auth/login', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    username: 'user@example.com',
    password: 'password123'
  })
});
```

### Composables (建議)
```typescript
// app/composables/useAuth.ts
export const useAuth = () => {
  const login = async (username: string, password: string) => {
    // 參考 api-auth.md 實作
  };
  
  const logout = async () => {
    // 參考 api-auth.md 實作
  };
  
  return { login, logout };
};
```

## 🔄 更新紀錄

| 版本 | 日期 | 變更內容 |
|------|------|----------|
| 1.0.0 | 2025-12-02 | 初始版本，包含所有核心 API 文件 |

## 📧 回饋與建議

如有任何問題或建議，請：
1. 提出 Issue
2. 聯繫開發團隊
3. 參與文件改進討論

---

**最後更新**: 2025-12-02  
**維護者**: CRM Questionnaire Team
