# CRM 問卷系統後端 API 規格文件

**版本**: 1.0.0  
**最後更新**: 2025-12-02  
**文件編號**: API-DOC-001

---

## 目錄

1. [概述](#1-概述)
2. [認證 API](#2-認證-api)
3. [使用者管理 API](#3-使用者管理-api)
4. [組織管理 API](#4-組織管理-api)
5. [部門管理 API](#5-部門管理-api)
6. [專案管理 API](#6-專案管理-api)
7. [範本管理 API](#7-範本管理-api)
8. [問卷填寫 API](#8-問卷填寫-api)
9. [審核流程 API](#9-審核流程-api)
10. [錯誤代碼參考](#10-錯誤代碼參考)

---

## 1. 概述

### 1.1 基礎資訊

| 項目 | 說明 |
|------|------|
| Base URL | `http://localhost:3001/api/v1` (開發) / `https://api.example.com/api/v1` (生產) |
| Protocol | HTTPS (生產) / HTTP (開發) |
| Data Format | JSON |
| Character Encoding | UTF-8 |
| Date Format | ISO 8601 (`2025-12-02T06:08:38.435Z`) |

### 1.2 認證方式

所有需認證的 API 需在 Header 中包含 JWT Token：

```
Authorization: Bearer <accessToken>
```

### 1.3 角色權限

| 角色 | 說明 |
|------|------|
| `HOST` | 製造商，可建立專案、管理範本、設定審核流程 |
| `SUPPLIER` | 供應商，可填寫被指派的專案問卷 |
| `ADMIN` | 系統管理員，可管理組織、部門、使用者 |

### 1.4 通用回應格式

**成功回應：**
```json
{
  "success": true,
  "data": { ... }
}
```

**錯誤回應：**
```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "錯誤訊息"
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

---

## 2. 認證 API

### 2.1 登入

```
POST /api/v1/auth/login
```

**Request Body：**
```json
{
  "username": "user@example.com",
  "password": "password123"
}
```

**Response (200)：**
```json
{
  "success": true,
  "data": {
    "accessToken": "eyJhbGciOiJIUzI1NiIs...",
    "refreshToken": "eyJhbGciOiJIUzI1NiIs...",
    "expiresIn": 3600,
    "user": {
      "id": "usr_abc123",
      "username": "user@example.com",
      "email": "user@example.com",
      "role": "HOST",
      "organizationId": "org_xyz789",
      "departmentId": "dept_def456"
    }
  }
}
```

### 2.2 登出

```
POST /api/v1/auth/logout
Authorization: Bearer <accessToken>
```

**Response (200)：**
```json
{
  "success": true,
  "message": "登出成功"
}
```

### 2.3 取得當前使用者

```
GET /api/v1/auth/me
Authorization: Bearer <accessToken>
```

### 2.4 更新 Token

```
POST /api/v1/auth/refresh
```

**Request Body：**
```json
{
  "refreshToken": "eyJhbGciOiJIUzI1NiIs..."
}
```

### 2.5 驗證 Token

```
POST /api/v1/auth/verify
```

**Request Body：**
```json
{
  "token": "eyJhbGciOiJIUzI1NiIs..."
}
```

---

## 3. 使用者管理 API

### 3.1 更新個人資料

```
PUT /api/v1/users/me
Authorization: Bearer <accessToken>
```

**Request Body：**
```json
{
  "email": "newemail@example.com",
  "phone": "0987654321",
  "departmentId": "dept_new789"
}
```

### 3.2 修改密碼

```
PUT /api/v1/users/me/password
Authorization: Bearer <accessToken>
```

**Request Body：**
```json
{
  "currentPassword": "oldPassword123",
  "newPassword": "newPassword456",
  "confirmPassword": "newPassword456"
}
```

### 3.3 取得使用者列表 (ADMIN/HOST)

```
GET /api/v1/users
Authorization: Bearer <accessToken>
```

**Query Parameters：**
| 參數 | 類型 | 說明 |
|------|------|------|
| page | integer | 頁碼 (預設: 1) |
| limit | integer | 每頁筆數 (預設: 20) |
| role | string | 篩選角色 |
| organizationId | string | 篩選組織 |
| search | string | 搜尋關鍵字 |

### 3.4 建立使用者 (ADMIN)

```
POST /api/v1/users
Authorization: Bearer <accessToken>
```

**Request Body：**
```json
{
  "username": "newuser@example.com",
  "email": "newuser@example.com",
  "password": "tempPassword123",
  "phone": "0912345678",
  "role": "SUPPLIER",
  "organizationId": "org_supplier123",
  "departmentId": "dept_procurement456"
}
```

### 3.5 更新使用者 (ADMIN)

```
PUT /api/v1/users/{userId}
Authorization: Bearer <accessToken>
```

### 3.6 刪除使用者 (ADMIN)

```
DELETE /api/v1/users/{userId}
Authorization: Bearer <accessToken>
```

---

## 4. 組織管理 API

### 4.1 取得組織列表 (ADMIN)

```
GET /api/v1/organizations
Authorization: Bearer <accessToken>
```

**Query Parameters：**
| 參數 | 類型 | 說明 |
|------|------|------|
| type | string | 組織類型 (HOST, SUPPLIER) |
| search | string | 搜尋組織名稱 |

### 4.2 取得組織詳情

```
GET /api/v1/organizations/{organizationId}
Authorization: Bearer <accessToken>
```

### 4.3 建立組織 (ADMIN)

```
POST /api/v1/organizations
Authorization: Bearer <accessToken>
```

**Request Body：**
```json
{
  "name": "新供應商公司",
  "type": "SUPPLIER"
}
```

### 4.4 更新組織 (ADMIN)

```
PUT /api/v1/organizations/{organizationId}
Authorization: Bearer <accessToken>
```

### 4.5 刪除組織 (ADMIN)

```
DELETE /api/v1/organizations/{organizationId}
Authorization: Bearer <accessToken>
```

### 4.6 取得供應商列表 (HOST)

```
GET /api/v1/suppliers
Authorization: Bearer <accessToken>
```

---

## 5. 部門管理 API

### 5.1 取得部門列表

```
GET /api/v1/departments
Authorization: Bearer <accessToken>
```

**Query Parameters：**
| 參數 | 類型 | 說明 |
|------|------|------|
| organizationId | string | 篩選組織 ID |
| search | string | 搜尋部門名稱 |

### 5.2 取得部門詳情

```
GET /api/v1/departments/{departmentId}
Authorization: Bearer <accessToken>
```

### 5.3 建立部門 (ADMIN)

```
POST /api/v1/departments
Authorization: Bearer <accessToken>
```

**Request Body：**
```json
{
  "name": "環境安全部",
  "organizationId": "org_xyz789"
}
```

### 5.4 更新部門 (ADMIN)

```
PUT /api/v1/departments/{departmentId}
Authorization: Bearer <accessToken>
```

### 5.5 刪除部門 (ADMIN)

```
DELETE /api/v1/departments/{departmentId}
Authorization: Bearer <accessToken>
```

---

## 6. 專案管理 API

### 6.1 取得專案列表

```
GET /api/v1/projects
Authorization: Bearer <accessToken>
```

**Query Parameters：**
| 參數 | 類型 | 說明 |
|------|------|------|
| type | string | 專案類型 (SAQ, CONFLICT) |
| status | string | 專案狀態 |
| year | integer | 年份 |
| search | string | 搜尋專案名稱 |
| sortBy | string | 排序欄位 |
| order | string | 排序方向 (asc, desc) |

**專案狀態：**
- `DRAFT` - 草稿
- `IN_PROGRESS` - 進行中
- `SUBMITTED` - 已提交
- `REVIEWING` - 審核中
- `APPROVED` - 已核准
- `RETURNED` - 已退回

### 6.2 取得專案詳情

```
GET /api/v1/projects/{projectId}
Authorization: Bearer <accessToken>
```

### 6.3 建立專案 (HOST)

```
POST /api/v1/projects
Authorization: Bearer <accessToken>
```

**Request Body：**
```json
{
  "name": "2025 SAQ 供應商評估",
  "year": 2025,
  "type": "SAQ",
  "templateId": "tmpl_def456",
  "templateVersion": "1.2.0",
  "supplierId": "org_supplier789",
  "reviewConfig": [
    {
      "stageOrder": 1,
      "departmentId": "dept_qm123"
    },
    {
      "stageOrder": 2,
      "departmentId": "dept_proc456"
    }
  ]
}
```

### 6.4 更新專案 (HOST)

```
PUT /api/v1/projects/{projectId}
Authorization: Bearer <accessToken>
```

### 6.5 刪除專案 (HOST)

```
DELETE /api/v1/projects/{projectId}
Authorization: Bearer <accessToken>
```

### 6.6 取得專案統計 (HOST)

```
GET /api/v1/projects/stats
Authorization: Bearer <accessToken>
```

---

## 7. 範本管理 API

### 7.1 取得範本列表 (HOST)

```
GET /api/v1/templates
Authorization: Bearer <accessToken>
```

**Query Parameters：**
| 參數 | 類型 | 說明 |
|------|------|------|
| type | string | 範本類型 (SAQ, CONFLICT) |
| search | string | 搜尋範本名稱 |

### 7.2 取得範本詳情

```
GET /api/v1/templates/{templateId}
Authorization: Bearer <accessToken>
```

### 7.3 建立範本 (HOST)

```
POST /api/v1/templates
Authorization: Bearer <accessToken>
```

**Request Body：**
```json
{
  "name": "新 SAQ 範本",
  "type": "SAQ",
  "questions": [
    {
      "text": "貴公司是否具有 ISO 9001 認證？",
      "type": "BOOLEAN",
      "required": true
    },
    {
      "text": "請選擇貴公司的主要產業類別",
      "type": "SINGLE_CHOICE",
      "required": true,
      "options": ["電子製造", "化工製造", "機械製造", "其他"]
    }
  ]
}
```

**題目類型 (QuestionType)：**
| 類型 | 說明 | 答案格式 |
|------|------|---------|
| TEXT | 簡答題 | string |
| NUMBER | 數字題 | number |
| DATE | 日期題 | string (ISO 8601) |
| BOOLEAN | 是非題 | boolean |
| SINGLE_CHOICE | 單選題 | string |
| MULTI_CHOICE | 複選題 | string[] |
| FILE | 檔案上傳 | string (URL) |
| RATING | 評分題 | number |

### 7.4 更新範本 (HOST)

```
PUT /api/v1/templates/{templateId}
Authorization: Bearer <accessToken>
```

### 7.5 刪除範本 (HOST)

```
DELETE /api/v1/templates/{templateId}
Authorization: Bearer <accessToken>
```

### 7.6 建立範本新版本 (HOST)

```
POST /api/v1/templates/{templateId}/versions
Authorization: Bearer <accessToken>
```

**Request Body：**
```json
{
  "version": "1.3.0",
  "questions": [...]
}
```

### 7.7 取得範本特定版本

```
GET /api/v1/templates/{templateId}/versions/{version}
Authorization: Bearer <accessToken>
```

---

## 8. 問卷填寫 API

### 8.1 取得專案答案

```
GET /api/v1/projects/{projectId}/answers
Authorization: Bearer <accessToken>
```

**Response：**
```json
{
  "success": true,
  "data": {
    "projectId": "proj_abc123",
    "answers": {
      "q_001": {
        "questionId": "q_001",
        "value": true
      },
      "q_002": {
        "questionId": "q_002",
        "value": "電子製造"
      }
    },
    "lastSavedAt": "2025-12-01T15:30:00.000Z"
  }
}
```

### 8.2 暫存答案 (SUPPLIER)

```
PUT /api/v1/projects/{projectId}/answers
Authorization: Bearer <accessToken>
```

**Request Body：**
```json
{
  "answers": {
    "q_001": {
      "questionId": "q_001",
      "value": true
    },
    "q_002": {
      "questionId": "q_002",
      "value": "電子製造"
    }
  }
}
```

### 8.3 提交專案 (SUPPLIER)

```
POST /api/v1/projects/{projectId}/submit
Authorization: Bearer <accessToken>
```

### 8.4 上傳檔案

```
POST /api/v1/files/upload
Authorization: Bearer <accessToken>
Content-Type: multipart/form-data
```

**Form Data：**
| 欄位 | 類型 | 說明 |
|------|------|------|
| file | file | 檔案 |
| projectId | string | 專案 ID |
| questionId | string | 題目 ID |

---

## 9. 審核流程 API

### 9.1 取得待審核專案列表 (HOST)

```
GET /api/v1/reviews/pending
Authorization: Bearer <accessToken>
```

### 9.2 審核專案 (HOST)

```
POST /api/v1/projects/{projectId}/review
Authorization: Bearer <accessToken>
```

**Request Body (核准)：**
```json
{
  "action": "APPROVE",
  "comment": "資料完整，核准通過。"
}
```

**Request Body (退回)：**
```json
{
  "action": "RETURN",
  "comment": "請補充 ISO 9001 認證文件。"
}
```

### 9.3 取得審核歷程

```
GET /api/v1/projects/{projectId}/reviews
Authorization: Bearer <accessToken>
```

### 9.4 取得審核統計 (HOST)

```
GET /api/v1/reviews/stats
Authorization: Bearer <accessToken>
```

---

## 10. 錯誤代碼參考

| 錯誤代碼 | HTTP 狀態 | 說明 |
|----------|-----------|------|
| AUTH_INVALID_CREDENTIALS | 401 | 帳號或密碼錯誤 |
| AUTH_TOKEN_EXPIRED | 401 | Token 已過期 |
| AUTH_TOKEN_INVALID | 401 | Token 無效 |
| AUTH_INSUFFICIENT_PERMISSION | 403 | 權限不足 |
| VALIDATION_ERROR | 422 | 資料驗證失敗 |
| RESOURCE_NOT_FOUND | 404 | 資源不存在 |
| RESOURCE_CONFLICT | 409 | 資源衝突 |
| TEMPLATE_IN_USE | 409 | 範本正在使用中 |
| PROJECT_ALREADY_SUBMITTED | 409 | 專案已提交 |
| REVIEW_STAGE_INVALID | 422 | 審核階段設定錯誤 |
| SUPPLIER_NOT_ASSIGNED | 403 | 供應商未被指派 |
| DEPARTMENT_IN_USE | 409 | 部門正在使用中 |
| FILE_TOO_LARGE | 413 | 檔案過大 |

---

## 附錄

### A. 分頁回應格式

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

### B. 審核流程狀態圖

```
DRAFT → IN_PROGRESS → SUBMITTED → REVIEWING → APPROVED
                                      ↓
                                  RETURNED → IN_PROGRESS
```

### C. 詳細文件參考

完整的 API 規格請參考 `/frontend/docs/` 目錄下的各模組文件：
- api-auth.md - 認證 API 詳細規格
- api-users.md - 使用者管理 API 詳細規格
- api-organizations.md - 組織管理 API 詳細規格
- api-departments.md - 部門管理 API 詳細規格
- api-projects.md - 專案管理 API 詳細規格
- api-templates.md - 範本管理 API 詳細規格
- api-answers.md - 問卷填寫 API 詳細規格
- api-reviews.md - 審核流程 API 詳細規格

---

**文件維護者**: CRM Questionnaire Team  
**最後更新**: 2025-12-02
