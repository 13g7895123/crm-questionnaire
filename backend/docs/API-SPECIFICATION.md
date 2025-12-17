# CRM 問卷系統後端 API 規格文件

**版本**: 2.0.0  
**最後更新**: 2025-12-04  
**文件編號**: API-DOC-001

> **v2.0.0 變更說明**：
> - 專案與供應商關係由 1:1 改為 1:N
> - 新增 `project_suppliers` 表管理專案-供應商關係
> - 答案與審核紀錄改為關聯 `project_supplier_id`
> - 新增 `/api/v1/project-suppliers/` 路徑處理供應商特定操作

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
| 參數 | 類型 | 必填 | 說明 |
|------|------|------|------|
| organizationId | string | 否 | 篩選組織 ID，若未提供則預設為當前使用者所屬組織 |
| search | string | 否 | 搜尋部門名稱（模糊比對） |
| page | integer | 否 | 頁碼（預設: 1） |
| limit | integer | 否 | 每頁筆數（預設: 20，最大: 100） |

**Response (200)：**
```json
{
  "success": true,
  "data": [
    {
      "id": "dept_qm001",
      "name": "品質管理部",
      "organizationId": "org_host001",
      "organization": {
        "id": "org_host001",
        "name": "製造商公司",
        "type": "HOST"
      },
      "createdAt": "2025-12-02T06:08:38.435Z",
      "updatedAt": "2025-12-02T06:08:38.435Z"
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 20,
    "total": 5,
    "totalPages": 1
  }
}
```

**權限說明：**
- 非 ADMIN 使用者只能查看自己所屬組織的部門
- ADMIN 可查看所有組織的部門

### 5.2 取得部門詳情

```
GET /api/v1/departments/{departmentId}
Authorization: Bearer <accessToken>
```

**Path Parameters：**
| 參數 | 類型 | 必填 | 說明 |
|------|------|------|------|
| departmentId | string | 是 | 部門 ID（格式：dept_xxx） |

**Response (200)：**
```json
{
  "success": true,
  "data": {
    "id": "dept_qm001",
    "name": "品質管理部",
    "organizationId": "org_host001",
    "organization": {
      "id": "org_host001",
      "name": "製造商公司",
      "type": "HOST"
    },
    "memberCount": 10,
    "createdAt": "2025-12-02T06:08:38.435Z",
    "updatedAt": "2025-12-02T06:08:38.435Z"
  }
}
```

**Response (404)：**
```json
{
  "success": false,
  "error": {
    "code": "RESOURCE_NOT_FOUND",
    "message": "找不到指定的部門"
  }
}
```

### 5.3 建立部門 (ADMIN)

```
POST /api/v1/departments
Authorization: Bearer <accessToken>
```

**Request Body：**
| 欄位 | 類型 | 必填 | 說明 |
|------|------|------|------|
| name | string | 是 | 部門名稱（最大長度: 100） |
| organizationId | string | 否 | 組織 ID，若未提供則自動使用當前使用者所屬組織 |

**Request Body 範例：**
```json
{
  "name": "環境安全部",
  "organizationId": "org_xyz789"
}
```

**或僅提供名稱（自動使用當前使用者組織）：**
```json
{
  "name": "環境安全部"
}
```

**Response (201)：**
```json
{
  "success": true,
  "data": {
    "id": "dept_abc123",
    "name": "環境安全部",
    "organizationId": "org_xyz789",
    "organization": {
      "id": "org_xyz789",
      "name": "製造商公司",
      "type": "HOST"
    },
    "createdAt": "2025-12-04T07:30:00.000Z",
    "updatedAt": "2025-12-04T07:30:00.000Z"
  }
}
```

**Response (409) - 部門名稱重複：**
```json
{
  "success": false,
  "error": {
    "code": "RESOURCE_CONFLICT",
    "message": "此組織已存在相同名稱的部門"
  }
}
```

**Response (404) - 組織不存在：**
```json
{
  "success": false,
  "error": {
    "code": "RESOURCE_NOT_FOUND",
    "message": "找不到指定的組織"
  }
}
```

**權限說明：**
- 僅 ADMIN 角色可建立部門
- 部門名稱在同一組織內不可重複

### 5.4 更新部門 (ADMIN)

```
PUT /api/v1/departments/{departmentId}
Authorization: Bearer <accessToken>
```

**Path Parameters：**
| 參數 | 類型 | 必填 | 說明 |
|------|------|------|------|
| departmentId | string | 是 | 部門 ID |

**Request Body：**
| 欄位 | 類型 | 必填 | 說明 |
|------|------|------|------|
| name | string | 是 | 部門名稱（最大長度: 100） |

**Request Body 範例：**
```json
{
  "name": "品質保證部"
}
```

**Response (200)：**
```json
{
  "success": true,
  "data": {
    "id": "dept_qm001",
    "name": "品質保證部",
    "organizationId": "org_host001",
    "organization": {
      "id": "org_host001",
      "name": "製造商公司",
      "type": "HOST"
    },
    "createdAt": "2025-12-02T06:08:38.435Z",
    "updatedAt": "2025-12-04T07:35:00.000Z"
  }
}
```

**Response (409) - 部門名稱重複：**
```json
{
  "success": false,
  "error": {
    "code": "RESOURCE_CONFLICT",
    "message": "此組織已存在相同名稱的部門"
  }
}
```

**權限說明：**
- 僅 ADMIN 角色可更新部門
- 部門名稱在同一組織內不可重複

### 5.5 刪除部門 (ADMIN)

```
DELETE /api/v1/departments/{departmentId}
Authorization: Bearer <accessToken>
```

**Path Parameters：**
| 參數 | 類型 | 必填 | 說明 |
|------|------|------|------|
| departmentId | string | 是 | 部門 ID |

**Response (204)：**
無內容（刪除成功）

**Response (409) - 部門正在使用中：**
```json
{
  "success": false,
  "error": {
    "code": "DEPARTMENT_IN_USE",
    "message": "此部門有使用者或被專案審核流程使用，無法刪除"
  },
  "data": {
    "userCount": 5,
    "projectCount": 2,
    "hasData": true
  }
}
```

**Response (404)：**
```json
{
  "success": false,
  "error": {
    "code": "RESOURCE_NOT_FOUND",
    "message": "找不到指定的部門"
  }
}
```

**權限說明：**
- 僅 ADMIN 角色可刪除部門
- 若部門下有使用者或被專案審核流程使用，無法刪除

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
| status | string | 專案狀態 (僅供應商使用) |
| year | integer | 年份 |
| search | string | 搜尋專案名稱 |
| sortBy | string | 排序欄位 |
| order | string | 排序方向 (asc, desc) |

**專案狀態（供應商層級）：**
- `DRAFT` - 草稿
- `IN_PROGRESS` - 進行中
- `SUBMITTED` - 已提交
- `REVIEWING` - 審核中
- `APPROVED` - 已核准
- `RETURNED` - 已退回

**Response (HOST)：**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "2025 SAQ 供應商評估",
      "year": 2025,
      "type": "SAQ",
      "templateId": 1,
      "templateVersion": "1.2.0",
      "supplierCount": 5,
      "approvedCount": 2,
      "reviewConfig": [...],
      "createdAt": "2025-12-02T06:08:38.435Z",
      "updatedAt": "2025-12-02T06:08:38.435Z"
    }
  ]
}
```

**Response (SUPPLIER)：**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "projectId": 1,
      "name": "2025 SAQ 供應商評估",
      "year": 2025,
      "type": "SAQ",
      "templateId": 1,
      "templateVersion": "1.2.0",
      "status": "IN_PROGRESS",
      "currentStage": 0,
      "submittedAt": null,
      "createdAt": "2025-12-02T06:08:38.435Z",
      "updatedAt": "2025-12-02T06:08:38.435Z"
    }
  ]
}
```

### 6.2 取得專案詳情

```
GET /api/v1/projects/{projectId}
Authorization: Bearer <accessToken>
```

**Response (HOST)：**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "2025 SAQ 供應商評估",
    "year": 2025,
    "type": "SAQ",
    "templateId": 1,
    "templateVersion": "1.2.0",
    "template": {
      "id": 1,
      "name": "SAQ 標準範本",
      "type": "SAQ",
      "latestVersion": "1.2.0"
    },
    "suppliers": [
      {
        "id": 1,
        "supplierId": 2,
        "supplierName": "供應商 A 公司",
        "status": "IN_PROGRESS",
        "currentStage": 0,
        "submittedAt": null
      },
      {
        "id": 2,
        "supplierId": 3,
        "supplierName": "供應商 B 公司",
        "status": "APPROVED",
        "currentStage": 2,
        "submittedAt": "2025-11-01T10:00:00.000Z"
      }
    ],
    "reviewConfig": [
      {
        "stageOrder": 1,
        "departmentId": 1,
        "department": {
          "id": 1,
          "name": "品質管理部"
        }
      }
    ],
    "createdAt": "2025-12-02T06:08:38.435Z",
    "updatedAt": "2025-12-02T06:08:38.435Z"
  }
}
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
  "templateId": 1,
  "templateVersion": "1.2.0",
  "supplierIds": [2, 3, 4],
  "reviewConfig": [
    {
      "stageOrder": 1,
      "departmentId": 1
    },
    {
      "stageOrder": 2,
      "departmentId": 2
    }
  ]
}
```

**Response (201)：**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "2025 SAQ 供應商評估",
    "year": 2025,
    "type": "SAQ",
    "templateId": 1,
    "templateVersion": "1.2.0",
    "suppliers": [
      {
        "id": 1,
        "supplierId": 2,
        "supplierName": "供應商 A 公司",
        "status": "IN_PROGRESS",
        "currentStage": 0
      },
      {
        "id": 2,
        "supplierId": 3,
        "supplierName": "供應商 B 公司",
        "status": "IN_PROGRESS",
        "currentStage": 0
      }
    ],
    "reviewConfig": [...],
    "createdAt": "2025-12-02T06:08:38.435Z",
    "updatedAt": "2025-12-02T06:08:38.435Z"
  }
}
```

### 6.4 更新專案 (HOST)

```
PUT /api/v1/projects/{projectId}
Authorization: Bearer <accessToken>
```

**Path Parameters：**
| 參數 | 類型 | 必填 | 說明 |
|------|------|------|------|
| projectId | string | 是 | 專案 ID |

**Request Body：**
```json
{
  "name": "2025 SAQ 供應商評估 (更新)",
  "year": 2025,
  "templateId": 1,
  "templateVersion": "1.3.0",
  "supplierIds": [2, 3, 5],
  "reviewConfig": [
    {
      "stageOrder": 1,
      "departmentId": 1
    },
    {
      "stageOrder": 2,
      "departmentId": 2
    }
  ]
}
```

**欄位說明：**
| 欄位 | 類型 | 必填 | 說明 |
|------|------|------|------|
| name | string | 否 | 專案名稱 (最長 200 字元) |
| year | integer | 否 | 年份 (1900-2100) |
| templateId | string/integer | 否 | 範本 ID (僅在所有供應商皆未填寫時可修改) |
| templateVersion | string | 否 | 範本版本 (僅在所有供應商皆未填寫時可修改) |
| supplierIds | array | 否 | 供應商組織 ID 列表 (可新增供應商) |
| reviewConfig | array | 否 | 審核流程設定 (僅在所有供應商皆未提交時可修改) |
| reviewConfig[].stageOrder | integer | 是 | 審核階段順序 (從 1 開始) |
| reviewConfig[].departmentId | string/integer | 是 | 負責部門 ID |
| reviewConfig[].approverId | string | 否 | 特定審核者 ID (可選) |

**可更新欄位與條件：**
| 欄位 | 條件 |
|------|------|
| name | 僅在所有供應商皆未核准 (APPROVED) 時可修改 |
| year | 僅在所有供應商皆未核准 (APPROVED) 時可修改 |
| templateId | 僅在所有供應商皆處於 DRAFT 或 IN_PROGRESS (未開始填寫) 狀態時可修改 |
| templateVersion | 僅在所有供應商皆處於 DRAFT 或 IN_PROGRESS (未開始填寫) 狀態時可修改 |
| supplierIds | 可新增供應商，已存在的供應商不受影響 |
| reviewConfig | 僅在所有供應商皆未提交 (SUBMITTED, REVIEWING, APPROVED) 時可修改 |

**不可更新欄位：**
- type (專案類型在建立後無法更改)

**注意事項：**
- 修改 `templateId` 或 `templateVersion` 時，若有供應商已開始填寫答案，則無法修改
- 若有任何供應商已提交 (SUBMITTED, REVIEWING, APPROVED)，則無法修改審核流程
- 若有任何供應商已核准 (APPROVED)，則無法修改專案基本資訊
- `supplierIds` 僅支援新增，移除供應商需使用其他方式 (目前未開放)
- 更換範本時，建議先確認現有供應商的填寫狀態
- `reviewConfig` 必須包含 1-5 個審核階段
- `stageOrder` 必須連續且從 1 開始
- 同一專案的 `departmentId` 不可重複

**Response (200)：**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "2025 SAQ 供應商評估 (更新)",
    "year": 2025,
    "type": "SAQ",
    "templateId": 1,
    "templateVersion": "1.3.0",
    "suppliers": [
      {
        "id": 1,
        "supplierId": 2,
        "supplierName": "供應商 A 公司",
        "status": "IN_PROGRESS",
        "currentStage": 0
      },
      {
        "id": 2,
        "supplierId": 3,
        "supplierName": "供應商 B 公司",
        "status": "IN_PROGRESS",
        "currentStage": 0
      },
      {
        "id": 3,
        "supplierId": 5,
        "supplierName": "供應商 C 公司",
        "status": "IN_PROGRESS",
        "currentStage": 0
      }
    ],
    "reviewConfig": [
      {
        "stageOrder": 1,
        "departmentId": 1,
        "department": {
          "id": 1,
          "name": "品質管理部"
        }
      },
      {
        "stageOrder": 2,
        "departmentId": 2,
        "department": {
          "id": 2,
          "name": "採購部"
        }
      }
    ],
    "updatedAt": "2025-12-02T06:08:38.435Z"
  }
}
```

**Response (409) - 專案已提交無法修改：**
```json
{
  "success": false,
  "error": {
    "code": "PROJECT_ALREADY_SUBMITTED",
    "message": "專案已提交，無法修改審核流程"
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

**Response (409) - 範本無法修改 (供應商已填寫)：**
```json
{
  "success": false,
  "error": {
    "code": "TEMPLATE_CHANGE_NOT_ALLOWED",
    "message": "範本無法修改，已有供應商開始填寫問卷",
    "details": {
      "suppliersWithAnswers": 2
    }
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

**Response (404) - 專案或範本版本不存在：**
```json
{
  "success": false,
  "error": {
    "code": "RESOURCE_NOT_FOUND",
    "message": "找不到指定的專案或範本版本"
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

**Response (422) - 審核流程設定錯誤：**
```json
{
  "success": false,
  "error": {
    "code": "REVIEW_STAGE_INVALID",
    "message": "審核流程設定錯誤",
    "details": {
      "reviewConfig": "審核階段必須為 1-5 個，且順序必須連續"
    }
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
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

## 6.5 專案供應商 API (Project-Suppliers)

### 6.5.1 取得答案

```
GET /api/v1/project-suppliers/{projectSupplierId}/answers
Authorization: Bearer <accessToken>
```

**Response：**
```json
{
  "success": true,
  "data": {
    "projectSupplierId": 1,
    "answers": {
      "q_001": {
        "questionId": "q_001",
        "value": true
      }
    },
    "lastSavedAt": "2025-12-01T15:30:00.000Z"
  }
}
```

### 6.5.2 暫存答案 (SUPPLIER)

```
PUT /api/v1/project-suppliers/{projectSupplierId}/answers
Authorization: Bearer <accessToken>
```

**Request Body：**
```json
{
  "answers": {
    "q_001": {
      "questionId": "q_001",
      "value": true
    }
  }
}
```

### 6.5.3 提交專案 (SUPPLIER)

```
POST /api/v1/project-suppliers/{projectSupplierId}/submit
Authorization: Bearer <accessToken>
```

### 6.5.4 審核專案 (HOST)

```
POST /api/v1/project-suppliers/{projectSupplierId}/review
Authorization: Bearer <accessToken>
```

**Request Body：**
```json
{
  "action": "APPROVE",
  "comment": "資料完整，核准通過。"
}
```

### 6.5.5 取得審核歷程

```
GET /api/v1/project-suppliers/{projectSupplierId}/reviews
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

> **注意：問卷填寫 API 已移至 `/api/v1/project-suppliers/` 路徑下**
> 
> 請參考 [6.5 專案供應商 API](#65-專案供應商-api-project-suppliers)

### 8.1 Template v2.0 API (新架構)

#### 8.1.1 取得範本結構 (Template Structure)

```
GET /api/v1/templates/{templateId}/structure
Authorization: Bearer <accessToken>
```

**說明：** 取得範本的完整階層結構（Section → Subsection → Question）

**Response (200)：**
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
          "description": "評估供應商的勞工政策與實踐",
          "subsections": [
            {
              "id": "A.1",
              "order": 1,
              "title": "勞動自由",
              "description": null,
              "questions": [
                {
                  "id": "A.1.1",
                  "order": 1,
                  "text": "貴公司是否禁止使用強迫勞工？",
                  "type": "BOOLEAN",
                  "required": true,
                  "config": null,
                  "conditionalLogic": {
                    "followUpQuestions": [
                      {
                        "condition": {
                          "operator": "equals",
                          "value": true
                        },
                        "questions": [
                          {
                            "id": "A.1.1.1",
                            "order": 1,
                            "text": "請說明貴公司如何確保沒有強迫勞工",
                            "type": "TEXT",
                            "required": true
                          }
                        ]
                      }
                    ]
                  }
                }
              ]
            }
          ]
        }
      ]
    }
  }
}
```

#### 8.1.2 儲存範本結構 (Save Template Structure)

```
PUT /api/v1/templates/{templateId}/structure
Authorization: Bearer <accessToken>
```

**說明：** 儲存或更新範本的完整階層結構（Section → Subsection → Question）

**Request Body：**
```json
{
  "sections": [
    {
      "id": "A",
      "order": 1,
      "title": "A. 勞工 (Labor)",
      "description": "勞工權益與工作條件評估",
      "subsections": [
        {
          "id": "A.1",
          "order": 1,
          "title": "A.1 僱傭自由選擇",
          "description": null,
          "questions": [
            {
              "id": "A.1.1",
              "order": 1,
              "text": "貴公司是否有制定並執行禁止強迫勞動的政策？",
              "type": "BOOLEAN",
              "required": true,
              "conditionalLogic": {
                "followUpQuestions": [
                  {
                    "condition": {"operator": "equals", "value": true},
                    "questions": [
                      {
                        "id": "A.1.1.1",
                        "text": "請簡述該政策的內容",
                        "type": "TEXT",
                        "required": false,
                        "config": {"maxLength": 500}
                      }
                    ]
                  }
                ]
              }
            }
          ]
        }
      ]
    }
  ]
}
```

**Response (200)：**
同 `GET /api/v1/templates/{templateId}/structure` 回應格式

**題目類型 (QuestionType)：**
- `BOOLEAN` - 是非題
- `TEXT` - 簡答題
- `NUMBER` - 數字題
- `RADIO` - 單選題
- `CHECKBOX` - 複選題
- `SELECT` - 下拉選單
- `DATE` - 日期題
- `FILE` - 檔案上傳
- `TABLE` - 表格題（v2.0 新增）

**條件邏輯運算子 (ConditionalOperator)：**
- `equals` - 等於
- `notEquals` - 不等於
- `contains` - 包含
- `greaterThan` - 大於
- `lessThan` - 小於
- `greaterThanOrEqual` - 大於等於
- `lessThanOrEqual` - 小於等於
- `isEmpty` - 為空
- `isNotEmpty` - 不為空

#### 8.1.3 取得基本資訊 (Basic Info)

```
GET /api/v1/project-suppliers/{projectSupplierId}/basic-info
Authorization: Bearer <accessToken>
```

**說明：** 取得供應商填寫的基本資訊（SAQ 第一步）

**Response (200)：**
```json
{
  "success": true,
  "data": {
    "projectSupplierId": 1,
    "basicInfo": {
      "companyName": "供應商 A 公司",
      "companyAddress": "台北市信義區信義路五段 7 號",
      "employees": {
        "total": 150,
        "male": 80,
        "female": 70,
        "foreign": 20
      },
      "facilities": [
        {
          "location": "台北工廠",
          "address": "新北市土城區工業路 123 號",
          "area": "5000",
          "type": "製造"
        },
        {
          "location": "新竹工廠",
          "address": "新竹縣湖口鄉科學園路 456 號",
          "area": "3000",
          "type": "研發"
        }
      ],
      "certifications": [
        "ISO 9001",
        "ISO 14001",
        "OHSAS 18001"
      ],
      "rbaOnlineMember": true,
      "contacts": [
        {
          "name": "張經理",
          "title": "品保經理",
          "department": "品質保證部",
          "email": "zhang@supplier-a.com",
          "phone": "02-1234-5678"
        },
        {
          "name": "李主任",
          "title": "環安主任",
          "department": "環安部",
          "email": "li@supplier-a.com",
          "phone": "02-2345-6789"
        }
      ]
    }
  }
}
```

**Response (404) - 尚未填寫：**
```json
{
  "success": true,
  "data": {
    "projectSupplierId": 1,
    "basicInfo": null
  }
}
```

#### 8.1.4 儲存基本資訊 (Save Basic Info)

```
PUT /api/v1/project-suppliers/{projectSupplierId}/basic-info
Authorization: Bearer <accessToken>
```

**Request Body：**
```json
{
  "companyName": "供應商 A 公司",
  "companyAddress": "台北市信義區信義路五段 7 號",
  "employees": {
    "total": 150,
    "male": 80,
    "female": 70,
    "foreign": 20
  },
  "facilities": [
    {
      "location": "台北工廠",
      "address": "新北市土城區工業路 123 號",
      "area": "5000",
      "type": "製造"
    }
  ],
  "certifications": ["ISO 9001", "ISO 14001"],
  "rbaOnlineMember": true,
  "contacts": [
    {
      "name": "張經理",
      "title": "品保經理",
      "department": "品質保證部",
      "email": "zhang@supplier-a.com",
      "phone": "02-1234-5678"
    }
  ]
}
```

**Response (200)：**
```json
{
  "success": true,
  "data": {
    "projectSupplierId": 1,
    "saved": true,
    "savedAt": "2025-12-04T10:30:00.000Z"
  }
}
```

#### 8.1.5 計算分數 (Calculate Score)

```
POST /api/v1/project-suppliers/{projectSupplierId}/calculate-score
Authorization: Bearer <accessToken>
```

**說明：** 計算當前答案的各區段分數及總分

**Response (200)：**
```json
{
  "success": true,
  "data": {
    "breakdown": {
      "A": {
        "sectionId": "A",
        "sectionName": "勞工 (Labor)",
        "score": 18,
        "maxScore": 20,
        "weight": 25,
        "answeredCount": 9,
        "totalCount": 10,
        "completionRate": 90
      },
      "B": {
        "sectionId": "B",
        "sectionName": "健康與安全 (Health & Safety)",
        "score": 16,
        "maxScore": 20,
        "weight": 25,
        "answeredCount": 8,
        "totalCount": 10,
        "completionRate": 80
      },
      "C": {
        "sectionId": "C",
        "sectionName": "環境 (Environment)",
        "score": 19,
        "maxScore": 20,
        "weight": 20,
        "answeredCount": 10,
        "totalCount": 10,
        "completionRate": 100
      },
      "D": {
        "sectionId": "D",
        "sectionName": "道德規範 (Ethics)",
        "score": 17,
        "maxScore": 20,
        "weight": 15,
        "answeredCount": 9,
        "totalCount": 10,
        "completionRate": 90
      },
      "E": {
        "sectionId": "E",
        "sectionName": "管理系統 (Management System)",
        "score": 18,
        "maxScore": 20,
        "weight": 15,
        "answeredCount": 10,
        "totalCount": 10,
        "completionRate": 100
      }
    },
    "totalScore": 88,
    "grade": "良好",
    "calculatedAt": "2025-12-04T10:35:00.000Z"
  }
}
```

**等級評定標準：**
| 等級 | 分數範圍 |
|------|---------|
| 優秀 | 90-100 |
| 良好 | 80-89 |
| 合格 | 70-79 |
| 待改進 | 60-69 |
| 不合格 | 0-59 |

#### 8.1.6 取得可見問題清單 (Get Visible Questions)

```
GET /api/v1/project-suppliers/{projectSupplierId}/visible-questions
Authorization: Bearer <accessToken>
```

**說明：** 根據條件邏輯計算當前應顯示的問題清單

**Response (200)：**
```json
{
  "success": true,
  "data": {
    "projectSupplierId": 1,
    "visibleQuestions": [
      "A.1.1",
      "A.1.1.1",
      "A.1.2",
      "A.2.1",
      "A.2.2",
      "B.1.1",
      "B.1.2",
      "B.1.2.1",
      "B.2.1",
      "C.1.1",
      "C.1.2",
      "D.1.1",
      "D.1.2",
      "E.1.1",
      "E.1.2"
    ]
  }
}
```

**說明：**
- 此 API 會根據已填寫的答案評估條件邏輯
- 返回應該顯示給使用者的問題 ID 清單
- 前端應根據此清單隱藏不符合條件的問題

#### 8.1.7 驗證答案 (Validate Answers)

```
POST /api/v1/project-suppliers/{projectSupplierId}/validate
Authorization: Bearer <accessToken>
```

**說明：** 驗證當前答案是否符合要求（提交前檢查）

**Response (200) - 驗證通過：**
```json
{
  "success": true,
  "data": {
    "projectSupplierId": 1,
    "valid": true,
    "errors": {}
  }
}
```

**Response (200) - 驗證失敗：**
```json
{
  "success": true,
  "data": {
    "projectSupplierId": 1,
    "valid": false,
    "errors": {
      "basicInfo": {
        "companyName": "公司名稱為必填欄位",
        "facilities": "至少需要提供一個設施資訊",
        "contacts": "至少需要提供一位聯絡人"
      },
      "missingRequired": [
        {
          "questionId": "A.1.1",
          "questionText": "貴公司是否禁止使用強迫勞工？"
        },
        {
          "questionId": "B.2.1",
          "questionText": "貴公司是否定期進行安全演練？"
        }
      ],
      "conditionalLogic": {
        "A.1.1.1": "當 A.1.1 回答「是」時，此問題為必填",
        "B.1.2.1": "當 B.1.2 選擇「每季一次」時，此問題為必填"
      },
      "tableAnswers": {
        "A.2.2": "表格至少需要 3 筆資料，目前只有 1 筆"
      }
    }
  }
}
```

**驗證項目：**
1. **基本資訊驗證**：
   - 必填欄位檢查（公司名稱、員工統計）
   - 至少一個設施資訊
   - 至少一位聯絡人
   - Email 格式驗證

2. **必填問題檢查**：
   - 檢查所有標記為 `required: true` 的問題是否已回答

3. **條件邏輯驗證**：
   - 檢查因條件觸發而變為必填的問題
   - 驗證條件式必填欄位是否已填寫

4. **表格答案驗證**：
   - 檢查表格行數是否符合 `minRows` 和 `maxRows` 限制
   - 驗證必填欄位是否完整
   - 檢查資料型別是否正確

---

## 9. 審核流程 API

### 9.1 取得待審核專案列表 (HOST)

```
GET /api/v1/reviews/pending
Authorization: Bearer <accessToken>
```

**Response：**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "projectId": 1,
      "name": "2025 SAQ 供應商評估",
      "year": 2025,
      "type": "SAQ",
      "supplierId": 2,
      "supplier": {
        "id": 2,
        "name": "供應商 A 公司"
      },
      "status": "REVIEWING",
      "currentStage": 1,
      "totalStages": 2,
      "submittedAt": "2025-11-01T10:00:00.000Z"
    }
  ]
}
```

### 9.2 審核專案 (HOST)

> **注意：審核 API 已移至 `/api/v1/project-suppliers/{projectSupplierId}/review`**
> 
> 請參考 [6.5.4 審核專案](#654-審核專案-host)

### 9.3 取得審核歷程

> **注意：審核歷程 API 已移至 `/api/v1/project-suppliers/{projectSupplierId}/reviews`**
> 
> 請參考 [6.5.5 取得審核歷程](#655-取得審核歷程)

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
