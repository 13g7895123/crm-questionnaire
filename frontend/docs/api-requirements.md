# CRM 問卷系統 API 需求文件

**版本**: 1.0.0  
**日期**: 2025-12-02  
**專案**: CRM Questionnaire System (問卷系統CRM)  
**相關規格**: `/specs/003-crm-questionnaire/spec.md`

## 文件目的

本文件定義 CRM 問卷系統前端與後端之間的 API 介面規格，涵蓋會員中心、SAQ 專案管理、衝突資產管理、問卷填寫與多階段審核等功能模組。

## 文件結構

- [API 總覽](#api-總覽)
- [認證機制](#認證機制)
- [錯誤處理](#錯誤處理)
- [API 端點](#api-端點)
  - [1. 認證相關 API](#1-認證相關-api)
  - [2. 使用者管理 API](#2-使用者管理-api)
  - [3. 部門管理 API](#3-部門管理-api)
  - [4. 專案管理 API](#4-專案管理-api)
  - [5. 範本管理 API](#5-範本管理-api)
  - [6. 問卷填寫 API](#6-問卷填寫-api)
  - [7. 審核流程 API](#7-審核流程-api)
  - [8. 組織管理 API](#8-組織管理-api)

## API 總覽

### 基礎資訊

- **Base URL**: `https://api.crm-questionnaire.com/v1` (開發環境: `http://localhost:3001/api/v1`)
- **Protocol**: HTTPS (生產環境) / HTTP (開發環境)
- **Data Format**: JSON
- **Character Encoding**: UTF-8
- **Date Format**: ISO 8601 (例: `2025-12-02T06:08:38.435Z`)

### 技術規範

- **RESTful API**: 遵循 REST 設計原則
- **HTTP Methods**: GET, POST, PUT, PATCH, DELETE
- **Status Codes**: 標準 HTTP 狀態碼
- **Authentication**: JWT (JSON Web Token) Bearer Token
- **Rate Limiting**: 100 requests/minute per user
- **API Versioning**: URL 路徑版本控制 (`/v1`)

### 支援的語系

- `zh-TW`: 繁體中文 (預設)
- `en`: English

語系透過 HTTP Header `Accept-Language` 指定，API 回應的錯誤訊息與描述文字會對應使用者選擇的語系。

## 認證機制

### JWT Token 認證

所有需要認證的 API 端點必須在 HTTP Header 中包含 JWT Token:

```
Authorization: Bearer <token>
```

### Token 生命週期

- **Access Token**: 有效期 1 小時
- **Refresh Token**: 有效期 30 天
- Token 過期後需重新登入或使用 Refresh Token 更新

### 權限角色

系統定義以下角色:

- `HOST` (製造商/主辦方): 可建立專案、管理範本、指派供應商、檢視所有專案
- `SUPPLIER` (供應商): 僅可檢視被指派的專案、填寫問卷
- `ADMIN`: 系統管理員，可管理部門、組織等基礎資料

## 錯誤處理

### 標準錯誤回應格式

```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "人類可讀的錯誤訊息",
    "details": {
      "field": "欄位名稱",
      "reason": "詳細原因"
    }
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

### 常見 HTTP 狀態碼

| 狀態碼 | 說明 | 使用情境 |
|--------|------|----------|
| 200 | OK | 請求成功 |
| 201 | Created | 資源建立成功 |
| 204 | No Content | 刪除成功，無回應內容 |
| 400 | Bad Request | 請求參數錯誤 |
| 401 | Unauthorized | 未認證或 Token 無效 |
| 403 | Forbidden | 無權限存取 |
| 404 | Not Found | 資源不存在 |
| 409 | Conflict | 資源衝突 (如重複建立) |
| 422 | Unprocessable Entity | 驗證錯誤 |
| 429 | Too Many Requests | 超過請求限制 |
| 500 | Internal Server Error | 伺服器內部錯誤 |

### 錯誤代碼列表

| 錯誤代碼 | HTTP 狀態 | 說明 |
|----------|-----------|------|
| `AUTH_INVALID_CREDENTIALS` | 401 | 帳號或密碼錯誤 |
| `AUTH_TOKEN_EXPIRED` | 401 | Token 已過期 |
| `AUTH_TOKEN_INVALID` | 401 | Token 無效 |
| `AUTH_INSUFFICIENT_PERMISSION` | 403 | 權限不足 |
| `VALIDATION_ERROR` | 422 | 資料驗證失敗 |
| `RESOURCE_NOT_FOUND` | 404 | 資源不存在 |
| `RESOURCE_CONFLICT` | 409 | 資源衝突 |
| `TEMPLATE_IN_USE` | 409 | 範本正在使用中，無法刪除 |
| `PROJECT_ALREADY_SUBMITTED` | 409 | 專案已提交，無法修改 |
| `REVIEW_STAGE_INVALID` | 422 | 審核階段設定錯誤 |
| `SUPPLIER_NOT_ASSIGNED` | 403 | 供應商未被指派至此專案 |
| `DEPARTMENT_IN_USE` | 409 | 部門正在使用中，無法刪除 |

## API 端點

詳細的 API 端點規格請參考:

1. [認證相關 API](./api-auth.md) - 登入、登出、Token 管理
2. [使用者管理 API](./api-users.md) - 個人資料、密碼管理
3. [部門管理 API](./api-departments.md) - 部門 CRUD
4. [專案管理 API](./api-projects.md) - SAQ 與衝突資產專案管理
5. [範本管理 API](./api-templates.md) - 範本與題目管理
6. [問卷填寫 API](./api-answers.md) - 暫存、提交答案
7. [審核流程 API](./api-reviews.md) - 審核、退回、歷程查詢
8. [組織管理 API](./api-organizations.md) - 組織管理 (HOST/SUPPLIER)

## 資料模型參考

詳細的資料模型定義請參考: `/specs/003-crm-questionnaire/data-model.md`

## OpenAPI 規格

完整的 OpenAPI 3.0 規格文件: `/specs/003-crm-questionnaire/contracts/openapi.yaml`

## 變更歷程

| 版本 | 日期 | 變更內容 | 作者 |
|------|------|----------|------|
| 1.0.0 | 2025-12-02 | 初始版本 | System |

## 附錄

### A. 分頁參數

所有列表類 API 支援分頁參數:

```
GET /api/v1/projects?page=1&limit=20
```

**參數說明:**
- `page`: 頁碼 (從 1 開始，預設: 1)
- `limit`: 每頁筆數 (預設: 20，最大: 100)

**回應格式:**
```json
{
  "success": true,
  "data": [...],
  "pagination": {
    "page": 1,
    "limit": 20,
    "total": 150,
    "totalPages": 8
  }
}
```

### B. 排序參數

列表類 API 支援排序:

```
GET /api/v1/projects?sortBy=createdAt&order=desc
```

**參數說明:**
- `sortBy`: 排序欄位
- `order`: 排序方向 (`asc` 或 `desc`)

### C. 搜尋與篩選

列表類 API 支援搜尋與篩選:

```
GET /api/v1/projects?type=SAQ&status=IN_PROGRESS&search=2025
```

具體可用的篩選參數請參考各 API 端點文件。
