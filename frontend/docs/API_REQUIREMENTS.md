# CRM Questionnaire System - Frontend API Requirements

## 概述 (Overview)

本文件定義 CRM 問卷系統前端所需的所有 API 端點需求。系統包含會員中心、SAQ (Supplier Assessment Questionnaire) 與衝突資產管理三大模組。

**Base URL**: `/api`

**驗證方式**: Bearer Token (JWT)

## 目錄

1. [認證 APIs (Authentication)](#1-認證-apis)
2. [使用者管理 APIs (User Management)](#2-使用者管理-apis)
3. [部門管理 APIs (Department Management)](#3-部門管理-apis)
4. [供應商管理 APIs (Supplier Management)](#4-供應商管理-apis)
5. [專案管理 APIs (Project Management)](#5-專案管理-apis)
6. [範本管理 APIs (Template Management)](#6-範本管理-apis)
7. [問卷題目管理 APIs (Question Management)](#7-問卷題目管理-apis)
8. [問卷填寫 APIs (Questionnaire Answering)](#8-問卷填寫-apis)
9. [審核流程 APIs (Review Process)](#9-審核流程-apis)
10. [檔案上傳 APIs (File Upload)](#10-檔案上傳-apis)

---

## 1. 認證 APIs (Authentication)

### 1.1 使用者登入 (User Login)

**端點**: `POST /auth/login`

**描述**: 使用者使用帳號密碼登入系統

**請求標頭**:
```
Content-Type: application/json
```

**請求主體**:
```json
{
  "username": "string",
  "password": "string"
}
```

**成功回應** (200 OK):
```json
{
  "token": "string (JWT token)",
  "user": {
    "id": "string",
    "username": "string",
    "email": "string",
    "phone": "string",
    "role": "HOST | SUPPLIER",
    "departmentId": "string",
    "department": {
      "id": "string",
      "name": "string"
    },
    "organizationId": "string",
    "organization": {
      "id": "string",
      "name": "string",
      "type": "HOST | SUPPLIER"
    }
  }
}
```

**錯誤回應**:
- 401 Unauthorized: 帳號或密碼錯誤
- 400 Bad Request: 請求格式錯誤

### 1.2 登出 (Logout)

**端點**: `POST /auth/logout`

**描述**: 使用者登出系統

**請求標頭**:
```
Authorization: Bearer {token}
```

**成功回應** (200 OK):
```json
{
  "message": "Logout successful"
}
```

### 1.3 取得當前使用者資訊 (Get Current User)

**端點**: `GET /auth/me`

**描述**: 取得當前登入使用者的完整資訊

**請求標頭**:
```
Authorization: Bearer {token}
```

**成功回應** (200 OK):
```json
{
  "id": "string",
  "username": "string",
  "email": "string",
  "phone": "string",
  "role": "HOST | SUPPLIER",
  "departmentId": "string",
  "department": {
    "id": "string",
    "name": "string"
  },
  "organizationId": "string",
  "organization": {
    "id": "string",
    "name": "string",
    "type": "HOST | SUPPLIER"
  }
}
```

**錯誤回應**:
- 401 Unauthorized: Token 無效或過期

### 1.4 刷新 Token (Refresh Token)

**端點**: `POST /auth/refresh`

**描述**: 使用 refresh token 取得新的 access token

**請求標頭**:
```
Content-Type: application/json
Authorization: Bearer {refresh_token}
```

**成功回應** (200 OK):
```json
{
  "token": "string (new JWT token)"
}
```

**錯誤回應**:
- 401 Unauthorized: Refresh token 無效或過期

---

## 2. 使用者管理 APIs (User Management)

### 2.1 更新使用者資料 (Update User Profile)

**端點**: `PUT /users/{userId}`

**描述**: 更新使用者個人資料

**請求標頭**:
```
Authorization: Bearer {token}
Content-Type: application/json
```

**路徑參數**:
- `userId`: 使用者 ID

**請求主體**:
```json
{
  "email": "string",
  "phone": "string",
  "departmentId": "string"
}
```

**成功回應** (200 OK):
```json
{
  "id": "string",
  "username": "string",
  "email": "string",
  "phone": "string",
  "departmentId": "string",
  "department": {
    "id": "string",
    "name": "string"
  }
}
```

**錯誤回應**:
- 400 Bad Request: 資料格式錯誤
- 403 Forbidden: 無權限修改此使用者
- 404 Not Found: 使用者不存在

### 2.2 變更密碼 (Change Password)

**端點**: `POST /users/change-password`

**描述**: 變更當前使用者密碼

**請求標頭**:
```
Authorization: Bearer {token}
Content-Type: application/json
```

**請求主體**:
```json
{
  "currentPassword": "string",
  "newPassword": "string"
}
```

**成功回應** (200 OK):
```json
{
  "message": "Password changed successfully"
}
```

**錯誤回應**:
- 400 Bad Request: 新密碼格式不符合規定
- 401 Unauthorized: 當前密碼錯誤
- 422 Unprocessable Entity: 新密碼與舊密碼相同

---

## 3. 部門管理 APIs (Department Management)

### 3.1 取得部門列表 (Get Departments)

**端點**: `GET /departments`

**描述**: 取得組織內所有部門列表

**請求標頭**:
```
Authorization: Bearer {token}
```

**查詢參數** (可選):
- `page`: 頁碼 (預設: 1)
- `pageSize`: 每頁數量 (預設: 50)
- `search`: 搜尋關鍵字

**成功回應** (200 OK):
```json
{
  "data": [
    {
      "id": "string",
      "name": "string",
      "organizationId": "string",
      "createdAt": "string (ISO 8601)",
      "updatedAt": "string (ISO 8601)"
    }
  ],
  "total": "number",
  "page": "number",
  "pageSize": "number"
}
```

### 3.2 建立部門 (Create Department)

**端點**: `POST /departments`

**描述**: 建立新部門 (僅管理員)

**請求標頭**:
```
Authorization: Bearer {token}
Content-Type: application/json
```

**請求主體**:
```json
{
  "name": "string"
}
```

**成功回應** (201 Created):
```json
{
  "id": "string",
  "name": "string",
  "organizationId": "string",
  "createdAt": "string (ISO 8601)",
  "updatedAt": "string (ISO 8601)"
}
```

**錯誤回應**:
- 400 Bad Request: 部門名稱已存在
- 403 Forbidden: 無管理員權限

### 3.3 更新部門 (Update Department)

**端點**: `PUT /departments/{departmentId}`

**描述**: 更新部門資訊 (僅管理員)

**請求標頭**:
```
Authorization: Bearer {token}
Content-Type: application/json
```

**路徑參數**:
- `departmentId`: 部門 ID

**請求主體**:
```json
{
  "name": "string"
}
```

**成功回應** (200 OK):
```json
{
  "id": "string",
  "name": "string",
  "organizationId": "string",
  "createdAt": "string (ISO 8601)",
  "updatedAt": "string (ISO 8601)"
}
```

**錯誤回應**:
- 400 Bad Request: 部門名稱已存在
- 403 Forbidden: 無管理員權限
- 404 Not Found: 部門不存在

### 3.4 刪除部門 (Delete Department)

**端點**: `DELETE /departments/{departmentId}`

**描述**: 刪除部門 (僅管理員)

**請求標頭**:
```
Authorization: Bearer {token}
```

**路徑參數**:
- `departmentId`: 部門 ID

**成功回應** (200 OK):
```json
{
  "message": "Department deleted successfully"
}
```

**錯誤回應**:
- 400 Bad Request: 部門仍有使用者或專案使用中
- 403 Forbidden: 無管理員權限
- 404 Not Found: 部門不存在

---

## 4. 供應商管理 APIs (Supplier Management)

### 4.1 取得供應商列表 (Get Suppliers)

**端點**: `GET /suppliers`

**描述**: 取得所有供應商組織列表 (僅製造商/主辦方可用)

**請求標頭**:
```
Authorization: Bearer {token}
```

**查詢參數** (可選):
- `page`: 頁碼 (預設: 1)
- `pageSize`: 每頁數量 (預設: 50)
- `search`: 搜尋關鍵字

**成功回應** (200 OK):
```json
{
  "data": [
    {
      "id": "string",
      "name": "string",
      "type": "SUPPLIER",
      "contactEmail": "string",
      "contactPhone": "string",
      "createdAt": "string (ISO 8601)",
      "updatedAt": "string (ISO 8601)"
    }
  ],
  "total": "number",
  "page": "number",
  "pageSize": "number"
}
```

**錯誤回應**:
- 403 Forbidden: 無權限存取 (僅製造商可用)

### 4.2 取得供應商詳情 (Get Supplier Detail)

**端點**: `GET /suppliers/{supplierId}`

**描述**: 取得特定供應商的詳細資訊

**請求標頭**:
```
Authorization: Bearer {token}
```

**路徑參數**:
- `supplierId`: 供應商 ID

**成功回應** (200 OK):
```json
{
  "id": "string",
  "name": "string",
  "type": "SUPPLIER",
  "contactEmail": "string",
  "contactPhone": "string",
  "address": "string",
  "createdAt": "string (ISO 8601)",
  "updatedAt": "string (ISO 8601)"
}
```

**錯誤回應**:
- 403 Forbidden: 無權限存取
- 404 Not Found: 供應商不存在

---

## 5. 專案管理 APIs (Project Management)

### 5.1 取得專案列表 (Get Projects)

**端點**: `GET /projects`

**描述**: 取得專案列表 (根據使用者角色過濾)

**請求標頭**:
```
Authorization: Bearer {token}
```

**查詢參數**:
- `type`: 專案類型 (必填: `SAQ` | `CONFLICT`)
- `status`: 專案狀態 (可選: `DRAFT` | `IN_PROGRESS` | `SUBMITTED` | `REVIEWING` | `APPROVED` | `RETURNED`)
- `year`: 年份 (可選)
- `page`: 頁碼 (預設: 1)
- `pageSize`: 每頁數量 (預設: 20)

**成功回應** (200 OK):
```json
{
  "data": [
    {
      "id": "string",
      "name": "string",
      "year": "number",
      "type": "SAQ | CONFLICT",
      "templateId": "string",
      "templateVersion": "string",
      "templateName": "string",
      "supplierId": "string",
      "supplierName": "string",
      "status": "DRAFT | IN_PROGRESS | SUBMITTED | REVIEWING | APPROVED | RETURNED",
      "currentStage": "number",
      "totalStages": "number",
      "createdAt": "string (ISO 8601)",
      "updatedAt": "string (ISO 8601)"
    }
  ],
  "total": "number",
  "page": "number",
  "pageSize": "number"
}
```

### 5.2 取得專案詳情 (Get Project Detail)

**端點**: `GET /projects/{projectId}`

**描述**: 取得特定專案的完整資訊

**請求標頭**:
```
Authorization: Bearer {token}
```

**路徑參數**:
- `projectId`: 專案 ID

**成功回應** (200 OK):
```json
{
  "id": "string",
  "name": "string",
  "year": "number",
  "type": "SAQ | CONFLICT",
  "templateId": "string",
  "templateVersion": "string",
  "template": {
    "id": "string",
    "name": "string",
    "version": "string",
    "questions": [
      {
        "id": "string",
        "text": "string",
        "type": "TEXT | NUMBER | DATE | BOOLEAN | SINGLE_CHOICE | MULTI_CHOICE | FILE | RATING",
        "required": "boolean",
        "options": ["string"],
        "config": {
          "maxFileSize": "number",
          "allowedFileTypes": ["string"],
          "ratingMin": "number",
          "ratingMax": "number",
          "ratingStep": "number",
          "numberMin": "number",
          "numberMax": "number",
          "maxLength": "number"
        }
      }
    ]
  },
  "supplierId": "string",
  "supplier": {
    "id": "string",
    "name": "string"
  },
  "status": "DRAFT | IN_PROGRESS | SUBMITTED | REVIEWING | APPROVED | RETURNED",
  "currentStage": "number",
  "reviewConfig": [
    {
      "stageOrder": "number",
      "departmentId": "string",
      "department": {
        "id": "string",
        "name": "string"
      }
    }
  ],
  "createdAt": "string (ISO 8601)",
  "updatedAt": "string (ISO 8601)"
}
```

**錯誤回應**:
- 403 Forbidden: 無權限存取此專案
- 404 Not Found: 專案不存在

### 5.3 建立專案 (Create Project)

**端點**: `POST /projects`

**描述**: 建立新專案 (僅製造商/主辦方可用)

**請求標頭**:
```
Authorization: Bearer {token}
Content-Type: application/json
```

**請求主體**:
```json
{
  "name": "string",
  "year": "number",
  "type": "SAQ | CONFLICT",
  "templateId": "string",
  "supplierId": "string",
  "reviewConfig": [
    {
      "stageOrder": "number (1-5)",
      "departmentId": "string"
    }
  ]
}
```

**成功回應** (201 Created):
```json
{
  "id": "string",
  "name": "string",
  "year": "number",
  "type": "SAQ | CONFLICT",
  "templateId": "string",
  "templateVersion": "string",
  "supplierId": "string",
  "status": "DRAFT",
  "currentStage": 0,
  "reviewConfig": [
    {
      "stageOrder": "number",
      "departmentId": "string",
      "department": {
        "id": "string",
        "name": "string"
      }
    }
  ],
  "createdAt": "string (ISO 8601)",
  "updatedAt": "string (ISO 8601)"
}
```

**錯誤回應**:
- 400 Bad Request: 資料格式錯誤或審核階段設定不正確
- 403 Forbidden: 無權限建立專案 (僅製造商可用)
- 404 Not Found: 範本或供應商不存在

### 5.4 更新專案 (Update Project)

**端點**: `PUT /projects/{projectId}`

**描述**: 更新專案資訊 (僅製造商/主辦方可用，僅草稿狀態可更新)

**請求標頭**:
```
Authorization: Bearer {token}
Content-Type: application/json
```

**路徑參數**:
- `projectId`: 專案 ID

**請求主體**:
```json
{
  "name": "string",
  "year": "number",
  "templateId": "string",
  "supplierId": "string",
  "reviewConfig": [
    {
      "stageOrder": "number (1-5)",
      "departmentId": "string"
    }
  ]
}
```

**成功回應** (200 OK):
```json
{
  "id": "string",
  "name": "string",
  "year": "number",
  "type": "SAQ | CONFLICT",
  "templateId": "string",
  "templateVersion": "string",
  "supplierId": "string",
  "status": "DRAFT",
  "reviewConfig": [
    {
      "stageOrder": "number",
      "departmentId": "string",
      "department": {
        "id": "string",
        "name": "string"
      }
    }
  ],
  "updatedAt": "string (ISO 8601)"
}
```

**錯誤回應**:
- 400 Bad Request: 專案狀態不允許更新或資料格式錯誤
- 403 Forbidden: 無權限更新此專案
- 404 Not Found: 專案不存在

### 5.5 刪除專案 (Delete Project)

**端點**: `DELETE /projects/{projectId}`

**描述**: 刪除專案 (僅製造商/主辦方可用，僅草稿狀態可刪除)

**請求標頭**:
```
Authorization: Bearer {token}
```

**路徑參數**:
- `projectId`: 專案 ID

**成功回應** (200 OK):
```json
{
  "message": "Project deleted successfully"
}
```

**錯誤回應**:
- 400 Bad Request: 專案狀態不允許刪除
- 403 Forbidden: 無權限刪除此專案
- 404 Not Found: 專案不存在

### 5.6 發布專案 (Publish Project)

**端點**: `POST /projects/{projectId}/publish`

**描述**: 將草稿專案發布為進行中狀態，供應商可開始填寫

**請求標頭**:
```
Authorization: Bearer {token}
```

**路徑參數**:
- `projectId`: 專案 ID

**成功回應** (200 OK):
```json
{
  "id": "string",
  "status": "IN_PROGRESS",
  "publishedAt": "string (ISO 8601)"
}
```

**錯誤回應**:
- 400 Bad Request: 專案狀態不是草稿或設定不完整
- 403 Forbidden: 無權限發布此專案
- 404 Not Found: 專案不存在

---

## 6. 範本管理 APIs (Template Management)

### 6.1 取得範本列表 (Get Templates)

**端點**: `GET /templates`

**描述**: 取得範本列表

**請求標頭**:
```
Authorization: Bearer {token}
```

**查詢參數**:
- `type`: 範本類型 (必填: `SAQ` | `CONFLICT`)
- `page`: 頁碼 (預設: 1)
- `pageSize`: 每頁數量 (預設: 20)

**成功回應** (200 OK):
```json
{
  "data": [
    {
      "id": "string",
      "name": "string",
      "type": "SAQ | CONFLICT",
      "latestVersion": "string",
      "versionCount": "number",
      "createdAt": "string (ISO 8601)",
      "updatedAt": "string (ISO 8601)"
    }
  ],
  "total": "number",
  "page": "number",
  "pageSize": "number"
}
```

### 6.2 取得範本詳情 (Get Template Detail)

**端點**: `GET /templates/{templateId}`

**描述**: 取得範本完整資訊，包含所有版本

**請求標頭**:
```
Authorization: Bearer {token}
```

**路徑參數**:
- `templateId`: 範本 ID

**查詢參數** (可選):
- `version`: 特定版本號 (不提供則返回最新版本)

**成功回應** (200 OK):
```json
{
  "id": "string",
  "name": "string",
  "type": "SAQ | CONFLICT",
  "latestVersion": "string",
  "versions": [
    {
      "version": "string",
      "questions": [
        {
          "id": "string",
          "text": "string",
          "type": "TEXT | NUMBER | DATE | BOOLEAN | SINGLE_CHOICE | MULTI_CHOICE | FILE | RATING",
          "required": "boolean",
          "options": ["string"],
          "config": {
            "maxFileSize": "number",
            "allowedFileTypes": ["string"],
            "ratingMin": "number",
            "ratingMax": "number",
            "ratingStep": "number",
            "numberMin": "number",
            "numberMax": "number",
            "maxLength": "number"
          }
        }
      ],
      "createdAt": "string (ISO 8601)"
    }
  ],
  "createdAt": "string (ISO 8601)",
  "updatedAt": "string (ISO 8601)"
}
```

**錯誤回應**:
- 404 Not Found: 範本不存在

### 6.3 建立範本 (Create Template)

**端點**: `POST /templates`

**描述**: 建立新範本 (僅製造商/主辦方可用)

**請求標頭**:
```
Authorization: Bearer {token}
Content-Type: application/json
```

**請求主體**:
```json
{
  "name": "string",
  "type": "SAQ | CONFLICT",
  "questions": [
    {
      "text": "string",
      "type": "TEXT | NUMBER | DATE | BOOLEAN | SINGLE_CHOICE | MULTI_CHOICE | FILE | RATING",
      "required": "boolean",
      "options": ["string"],
      "config": {
        "maxFileSize": "number",
        "allowedFileTypes": ["string"],
        "ratingMin": "number",
        "ratingMax": "number",
        "ratingStep": "number",
        "numberMin": "number",
        "numberMax": "number",
        "maxLength": "number"
      }
    }
  ]
}
```

**成功回應** (201 Created):
```json
{
  "id": "string",
  "name": "string",
  "type": "SAQ | CONFLICT",
  "latestVersion": "1.0.0",
  "versions": [
    {
      "version": "1.0.0",
      "questions": [...],
      "createdAt": "string (ISO 8601)"
    }
  ],
  "createdAt": "string (ISO 8601)",
  "updatedAt": "string (ISO 8601)"
}
```

**錯誤回應**:
- 400 Bad Request: 資料格式錯誤
- 403 Forbidden: 無權限建立範本

### 6.4 更新範本 (Update Template)

**端點**: `PUT /templates/{templateId}`

**描述**: 更新範本基本資訊 (不影響版本)

**請求標頭**:
```
Authorization: Bearer {token}
Content-Type: application/json
```

**路徑參數**:
- `templateId`: 範本 ID

**請求主體**:
```json
{
  "name": "string"
}
```

**成功回應** (200 OK):
```json
{
  "id": "string",
  "name": "string",
  "type": "SAQ | CONFLICT",
  "latestVersion": "string",
  "updatedAt": "string (ISO 8601)"
}
```

**錯誤回應**:
- 400 Bad Request: 資料格式錯誤
- 403 Forbidden: 無權限更新範本
- 404 Not Found: 範本不存在

### 6.5 發布新版本 (Publish New Version)

**端點**: `POST /templates/{templateId}/publish`

**描述**: 發布範本的新版本

**請求標頭**:
```
Authorization: Bearer {token}
Content-Type: application/json
```

**路徑參數**:
- `templateId`: 範本 ID

**請求主體**:
```json
{
  "questions": [
    {
      "text": "string",
      "type": "TEXT | NUMBER | DATE | BOOLEAN | SINGLE_CHOICE | MULTI_CHOICE | FILE | RATING",
      "required": "boolean",
      "options": ["string"],
      "config": {
        "maxFileSize": "number",
        "allowedFileTypes": ["string"],
        "ratingMin": "number",
        "ratingMax": "number",
        "ratingStep": "number",
        "numberMin": "number",
        "numberMax": "number",
        "maxLength": "number"
      }
    }
  ]
}
```

**成功回應** (201 Created):
```json
{
  "id": "string",
  "name": "string",
  "latestVersion": "string (e.g., 1.1.0)",
  "version": {
    "version": "string",
    "questions": [...],
    "createdAt": "string (ISO 8601)"
  }
}
```

**錯誤回應**:
- 400 Bad Request: 題目設定錯誤
- 403 Forbidden: 無權限發布版本
- 404 Not Found: 範本不存在

### 6.6 刪除範本 (Delete Template)

**端點**: `DELETE /templates/{templateId}`

**描述**: 刪除範本 (若有專案使用中則無法刪除)

**請求標頭**:
```
Authorization: Bearer {token}
```

**路徑參數**:
- `templateId`: 範本 ID

**成功回應** (200 OK):
```json
{
  "message": "Template deleted successfully"
}
```

**錯誤回應**:
- 400 Bad Request: 範本仍有專案使用中
- 403 Forbidden: 無權限刪除範本
- 404 Not Found: 範本不存在

---

## 7. 問卷題目管理 APIs (Question Management)

### 7.1 新增題目至範本 (Add Question to Template)

**端點**: `POST /templates/{templateId}/questions`

**描述**: 新增題目至範本的草稿版本 (需發布新版本後才會生效)

**請求標頭**:
```
Authorization: Bearer {token}
Content-Type: application/json
```

**路徑參數**:
- `templateId`: 範本 ID

**請求主體**:
```json
{
  "text": "string",
  "type": "TEXT | NUMBER | DATE | BOOLEAN | SINGLE_CHOICE | MULTI_CHOICE | FILE | RATING",
  "required": "boolean",
  "options": ["string"],
  "config": {
    "maxFileSize": "number",
    "allowedFileTypes": ["string"],
    "ratingMin": "number",
    "ratingMax": "number",
    "ratingStep": "number",
    "numberMin": "number",
    "numberMax": "number",
    "maxLength": "number"
  }
}
```

**成功回應** (201 Created):
```json
{
  "id": "string",
  "text": "string",
  "type": "TEXT | NUMBER | DATE | BOOLEAN | SINGLE_CHOICE | MULTI_CHOICE | FILE | RATING",
  "required": "boolean",
  "options": ["string"],
  "config": {...}
}
```

**錯誤回應**:
- 400 Bad Request: 題目設定錯誤
- 403 Forbidden: 無權限新增題目
- 404 Not Found: 範本不存在

### 7.2 更新題目 (Update Question)

**端點**: `PUT /templates/{templateId}/questions/{questionId}`

**描述**: 更新範本草稿版本中的題目

**請求標頭**:
```
Authorization: Bearer {token}
Content-Type: application/json
```

**路徑參數**:
- `templateId`: 範本 ID
- `questionId`: 題目 ID

**請求主體**:
```json
{
  "text": "string",
  "type": "TEXT | NUMBER | DATE | BOOLEAN | SINGLE_CHOICE | MULTI_CHOICE | FILE | RATING",
  "required": "boolean",
  "options": ["string"],
  "config": {...}
}
```

**成功回應** (200 OK):
```json
{
  "id": "string",
  "text": "string",
  "type": "...",
  "required": "boolean",
  "options": ["string"],
  "config": {...}
}
```

**錯誤回應**:
- 400 Bad Request: 題目設定錯誤
- 403 Forbidden: 無權限更新題目
- 404 Not Found: 範本或題目不存在

### 7.3 刪除題目 (Delete Question)

**端點**: `DELETE /templates/{templateId}/questions/{questionId}`

**描述**: 從範本草稿版本中刪除題目

**請求標頭**:
```
Authorization: Bearer {token}
```

**路徑參數**:
- `templateId`: 範本 ID
- `questionId`: 題目 ID

**成功回應** (200 OK):
```json
{
  "message": "Question deleted successfully"
}
```

**錯誤回應**:
- 403 Forbidden: 無權限刪除題目
- 404 Not Found: 範本或題目不存在

---

## 8. 問卷填寫 APIs (Questionnaire Answering)

### 8.1 取得專案答案 (Get Project Answers)

**端點**: `GET /projects/{projectId}/answers`

**描述**: 取得專案的所有答案 (供應商可取得自己的答案，製造商可取得所有答案)

**請求標頭**:
```
Authorization: Bearer {token}
```

**路徑參數**:
- `projectId`: 專案 ID

**成功回應** (200 OK):
```json
{
  "projectId": "string",
  "answers": {
    "questionId1": {
      "questionId": "string",
      "value": "any (依題目類型而定)"
    },
    "questionId2": {
      "questionId": "string",
      "value": "any"
    }
  },
  "lastSavedAt": "string (ISO 8601)"
}
```

**錯誤回應**:
- 403 Forbidden: 無權限存取此專案答案
- 404 Not Found: 專案不存在

### 8.2 儲存答案 (草稿) (Save Answers - Draft)

**端點**: `POST /projects/{projectId}/answers`

**描述**: 儲存答案但不提交 (暫存功能)

**請求標頭**:
```
Authorization: Bearer {token}
Content-Type: application/json
```

**路徑參數**:
- `projectId`: 專案 ID

**請求主體**:
```json
{
  "answers": {
    "questionId1": {
      "questionId": "string",
      "value": "any"
    },
    "questionId2": {
      "questionId": "string",
      "value": "any"
    }
  }
}
```

**成功回應** (200 OK):
```json
{
  "projectId": "string",
  "savedCount": "number",
  "lastSavedAt": "string (ISO 8601)"
}
```

**錯誤回應**:
- 400 Bad Request: 答案格式錯誤
- 403 Forbidden: 無權限填寫此專案或專案狀態不允許填寫
- 404 Not Found: 專案不存在

### 8.3 提交問卷 (Submit Questionnaire)

**端點**: `POST /projects/{projectId}/submit`

**描述**: 正式提交問卷，進入審核流程

**請求標頭**:
```
Authorization: Bearer {token}
Content-Type: application/json
```

**路徑參數**:
- `projectId`: 專案 ID

**請求主體**:
```json
{
  "answers": {
    "questionId1": {
      "questionId": "string",
      "value": "any"
    },
    "questionId2": {
      "questionId": "string",
      "value": "any"
    }
  }
}
```

**成功回應** (200 OK):
```json
{
  "projectId": "string",
  "status": "REVIEWING",
  "currentStage": 1,
  "submittedAt": "string (ISO 8601)"
}
```

**錯誤回應**:
- 400 Bad Request: 必填題未完成或答案格式錯誤
- 403 Forbidden: 無權限提交此專案
- 404 Not Found: 專案不存在

---

## 9. 審核流程 APIs (Review Process)

### 9.1 取得待審核專案列表 (Get Pending Reviews)

**端點**: `GET /review/pending`

**描述**: 取得當前使用者部門待審核的專案列表

**請求標頭**:
```
Authorization: Bearer {token}
```

**查詢參數** (可選):
- `type`: 專案類型 (`SAQ` | `CONFLICT`)
- `page`: 頁碼 (預設: 1)
- `pageSize`: 每頁數量 (預設: 20)

**成功回應** (200 OK):
```json
{
  "data": [
    {
      "id": "string",
      "name": "string",
      "year": "number",
      "type": "SAQ | CONFLICT",
      "supplierId": "string",
      "supplierName": "string",
      "status": "REVIEWING",
      "currentStage": "number",
      "stageDepartmentId": "string",
      "submittedAt": "string (ISO 8601)"
    }
  ],
  "total": "number",
  "page": "number",
  "pageSize": "number"
}
```

### 9.2 取得專案審核歷程 (Get Review Logs)

**端點**: `GET /projects/{projectId}/review-logs`

**描述**: 取得專案的完整審核歷程

**請求標頭**:
```
Authorization: Bearer {token}
```

**路徑參數**:
- `projectId`: 專案 ID

**成功回應** (200 OK):
```json
{
  "data": [
    {
      "id": "string",
      "projectId": "string",
      "reviewerId": "string",
      "reviewerName": "string",
      "stage": "number",
      "action": "APPROVE | RETURN",
      "comment": "string",
      "timestamp": "string (ISO 8601)"
    }
  ]
}
```

**錯誤回應**:
- 403 Forbidden: 無權限查看審核歷程
- 404 Not Found: 專案不存在

### 9.3 核准專案 (Approve Project)

**端點**: `POST /projects/{projectId}/approve`

**描述**: 核准當前階段，專案進入下一階段或結案

**請求標頭**:
```
Authorization: Bearer {token}
Content-Type: application/json
```

**路徑參數**:
- `projectId`: 專案 ID

**請求主體**:
```json
{
  "comment": "string (可選)"
}
```

**成功回應** (200 OK):
```json
{
  "projectId": "string",
  "status": "REVIEWING | APPROVED",
  "currentStage": "number (若已結案則為 0)",
  "reviewLog": {
    "id": "string",
    "reviewerId": "string",
    "reviewerName": "string",
    "stage": "number",
    "action": "APPROVE",
    "comment": "string",
    "timestamp": "string (ISO 8601)"
  }
}
```

**錯誤回應**:
- 400 Bad Request: 專案狀態不允許審核
- 403 Forbidden: 無權限審核此專案或不是當前階段的審核者
- 404 Not Found: 專案不存在

### 9.4 退回專案 (Return Project)

**端點**: `POST /projects/{projectId}/return`

**描述**: 退回專案給供應商重新填寫

**請求標頭**:
```
Authorization: Bearer {token}
Content-Type: application/json
```

**路徑參數**:
- `projectId`: 專案 ID

**請求主體**:
```json
{
  "comment": "string (必填，說明退回原因)"
}
```

**成功回應** (200 OK):
```json
{
  "projectId": "string",
  "status": "RETURNED",
  "currentStage": 0,
  "reviewLog": {
    "id": "string",
    "reviewerId": "string",
    "reviewerName": "string",
    "stage": "number",
    "action": "RETURN",
    "comment": "string",
    "timestamp": "string (ISO 8601)"
  }
}
```

**錯誤回應**:
- 400 Bad Request: 專案狀態不允許審核或缺少退回原因
- 403 Forbidden: 無權限審核此專案或不是當前階段的審核者
- 404 Not Found: 專案不存在

---

## 10. 檔案上傳 APIs (File Upload)

### 10.1 上傳檔案 (Upload File)

**端點**: `POST /files/upload`

**描述**: 上傳檔案 (用於檔案類型的問卷題目答案)

**請求標頭**:
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**請求主體** (Form Data):
- `file`: 檔案
- `projectId`: 專案 ID
- `questionId`: 題目 ID

**成功回應** (200 OK):
```json
{
  "fileId": "string",
  "fileName": "string",
  "fileSize": "number",
  "mimeType": "string",
  "url": "string",
  "uploadedAt": "string (ISO 8601)"
}
```

**錯誤回應**:
- 400 Bad Request: 檔案格式不支援或檔案大小超過限制
- 403 Forbidden: 無權限上傳檔案
- 413 Payload Too Large: 檔案太大

### 10.2 刪除檔案 (Delete File)

**端點**: `DELETE /files/{fileId}`

**描述**: 刪除已上傳的檔案

**請求標頭**:
```
Authorization: Bearer {token}
```

**路徑參數**:
- `fileId`: 檔案 ID

**成功回應** (200 OK):
```json
{
  "message": "File deleted successfully"
}
```

**錯誤回應**:
- 403 Forbidden: 無權限刪除此檔案
- 404 Not Found: 檔案不存在

### 10.3 下載檔案 (Download File)

**端點**: `GET /files/{fileId}/download`

**描述**: 下載檔案

**請求標頭**:
```
Authorization: Bearer {token}
```

**路徑參數**:
- `fileId`: 檔案 ID

**成功回應** (200 OK):
- 返回檔案二進位內容
- 標頭包含: `Content-Disposition: attachment; filename="..."`

**錯誤回應**:
- 403 Forbidden: 無權限下載此檔案
- 404 Not Found: 檔案不存在

---

## 通用規範

### 錯誤回應格式

所有 API 的錯誤回應均遵循以下格式:

```json
{
  "error": "ERROR_CODE",
  "message": "Human-readable error message",
  "details": {
    "field": "Additional error details (optional)"
  }
}
```

### 通用錯誤代碼

- `400 Bad Request`: 請求格式錯誤或參數不正確
- `401 Unauthorized`: 未驗證或 Token 無效
- `403 Forbidden`: 已驗證但無權限執行此操作
- `404 Not Found`: 資源不存在
- `409 Conflict`: 資源衝突 (如重複建立)
- `422 Unprocessable Entity`: 請求格式正確但語義錯誤
- `500 Internal Server Error`: 伺服器內部錯誤

### 分頁規範

所有支援分頁的 API 遵循以下規範:

**查詢參數**:
- `page`: 頁碼，從 1 開始
- `pageSize`: 每頁項目數，預設 20，最大 100

**回應格式**:
```json
{
  "data": [...],
  "total": "number (總項目數)",
  "page": "number (當前頁碼)",
  "pageSize": "number (每頁項目數)"
}
```

### 日期時間格式

所有日期時間均使用 ISO 8601 格式 (UTC):
```
2024-12-01T10:30:00.000Z
```

### 驗證標頭

除了登入 API，所有 API 都需要在請求標頭中包含:
```
Authorization: Bearer {JWT_TOKEN}
```

---

## 附錄

### A. 題目類型說明

| 類型 | 說明 | 值格式 |
|------|------|--------|
| TEXT | 文字輸入 | `string` |
| NUMBER | 數字輸入 | `number` |
| DATE | 日期選擇 | `string (ISO 8601 date)` |
| BOOLEAN | 是/否選擇 | `boolean` |
| SINGLE_CHOICE | 單選題 | `string (選項值)` |
| MULTI_CHOICE | 多選題 | `array of strings` |
| FILE | 檔案上傳 | `string (file ID)` |
| RATING | 評分量表 | `number` |

### B. 專案狀態流程

```
DRAFT → IN_PROGRESS → SUBMITTED → REVIEWING → APPROVED
                                      ↓
                                  RETURNED → IN_PROGRESS
```

- `DRAFT`: 草稿 (僅製造商可見，可編輯)
- `IN_PROGRESS`: 進行中 (供應商可填寫)
- `SUBMITTED`: 已提交 (即將進入審核)
- `REVIEWING`: 審核中 (在某個審核階段)
- `APPROVED`: 已核准 (完成所有審核階段)
- `RETURNED`: 已退回 (需重新填寫)

### C. 審核階段說明

- 每個專案可設定 1-5 個審核階段
- 每個階段指定一個負責部門
- 審核依照階段順序進行 (stageOrder: 1, 2, 3, ...)
- 任何階段的審核者都可以退回專案
- 最後階段核准後，專案狀態變更為 `APPROVED`

---

## 版本歷史

| 版本 | 日期 | 變更說明 |
|------|------|----------|
| 1.0.0 | 2024-12-02 | 初始版本，定義所有核心 API 需求 |
