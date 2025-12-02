# Frontend API Requirements Documentation

## 概述 (Overview)

本文件說明前端應用程式所需的所有 API 端點、請求格式、回應格式及錯誤處理。本系統為 CRM 問卷系統，包含會員管理、SAQ 問卷、衝突資產管理等功能。

This document describes all API endpoints, request formats, response formats, and error handling required by the frontend application. This system is a CRM questionnaire system that includes member management, SAQ questionnaires, and conflict asset management.

## 基礎設定 (Base Configuration)

### Base URL
- **Development**: `http://localhost:3000/api`
- **Production**: 根據環境變數 `API_BASE_URL` 設定

### 認證方式 (Authentication)
所有需要認證的 API 都需在請求標頭中包含 JWT Token：

```
Authorization: Bearer {token}
```

### 通用回應格式 (Common Response Format)

#### 成功回應 (Success Response)
```json
{
  "data": { ... },
  "message": "Success message (optional)"
}
```

#### 錯誤回應 (Error Response)
```json
{
  "error": "Error type",
  "message": "Error message description"
}
```

### HTTP 狀態碼 (HTTP Status Codes)
- `200 OK`: 請求成功
- `201 Created`: 資源建立成功
- `400 Bad Request`: 請求格式錯誤
- `401 Unauthorized`: 未認證或 Token 無效
- `403 Forbidden`: 無權限存取
- `404 Not Found`: 資源不存在
- `500 Internal Server Error`: 伺服器錯誤

---

## 1. 認證 API (Authentication APIs)

### 1.1 登入 (Login)

**Endpoint**: `POST /api/auth/login`

**Description**: 使用者登入並取得 JWT Token

**Authentication Required**: ❌ No

**Request Body**:
```json
{
  "username": "string",
  "password": "string"
}
```

**Request Example**:
```json
{
  "username": "john.doe",
  "password": "securepassword123"
}
```

**Response** (200 OK):
```json
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": "user-uuid",
    "username": "john.doe",
    "email": "john@example.com",
    "phone": "0912345678",
    "departmentId": "dept-uuid",
    "department": {
      "id": "dept-uuid",
      "name": "Engineering",
      "organizationId": "org-uuid"
    },
    "role": "HOST",
    "organizationId": "org-uuid",
    "organization": {
      "id": "org-uuid",
      "name": "Company Name",
      "type": "HOST"
    }
  }
}
```

**Error Responses**:
- `401 Unauthorized`: 帳號或密碼錯誤
```json
{
  "error": "INVALID_CREDENTIALS",
  "message": "Invalid username or password"
}
```

---

## 2. 使用者 API (User APIs)

### 2.1 更新個人資料 (Update Profile)

**Endpoint**: `PUT /api/users/{userId}`

**Description**: 更新使用者個人資料

**Authentication Required**: ✅ Yes

**Path Parameters**:
- `userId` (string, required): 使用者 ID

**Request Body**:
```json
{
  "email": "string (optional)",
  "phone": "string (optional)",
  "departmentId": "string (optional)"
}
```

**Request Example**:
```json
{
  "email": "newemail@example.com",
  "phone": "0987654321"
}
```

**Response** (200 OK):
```json
{
  "id": "user-uuid",
  "username": "john.doe",
  "email": "newemail@example.com",
  "phone": "0987654321",
  "departmentId": "dept-uuid",
  "role": "HOST",
  "organizationId": "org-uuid"
}
```

### 2.2 變更密碼 (Change Password)

**Endpoint**: `POST /api/users/change-password`

**Description**: 變更使用者密碼

**Authentication Required**: ✅ Yes

**Request Body**:
```json
{
  "currentPassword": "string",
  "newPassword": "string"
}
```

**Request Example**:
```json
{
  "currentPassword": "oldpassword123",
  "newPassword": "newpassword456"
}
```

**Response** (200 OK):
```json
{
  "message": "Password changed successfully"
}
```

**Error Responses**:
- `400 Bad Request`: 當前密碼錯誤
```json
{
  "error": "INVALID_PASSWORD",
  "message": "Current password is incorrect"
}
```

---

## 3. 部門 API (Department APIs)

### 3.1 取得部門列表 (Get Departments)

**Endpoint**: `GET /api/departments`

**Description**: 取得所有部門列表

**Authentication Required**: ✅ Yes

**Query Parameters**: None

**Response** (200 OK):
```json
{
  "data": [
    {
      "id": "dept-uuid-1",
      "name": "Engineering",
      "organizationId": "org-uuid",
      "createdAt": "2025-01-01T00:00:00Z",
      "updatedAt": "2025-01-01T00:00:00Z"
    },
    {
      "id": "dept-uuid-2",
      "name": "Sales",
      "organizationId": "org-uuid",
      "createdAt": "2025-01-01T00:00:00Z",
      "updatedAt": "2025-01-01T00:00:00Z"
    }
  ]
}
```

### 3.2 建立部門 (Create Department)

**Endpoint**: `POST /api/departments`

**Description**: 建立新部門

**Authentication Required**: ✅ Yes (Admin only)

**Request Body**:
```json
{
  "name": "string"
}
```

**Request Example**:
```json
{
  "name": "Marketing"
}
```

**Response** (201 Created):
```json
{
  "id": "dept-uuid-3",
  "name": "Marketing",
  "organizationId": "org-uuid",
  "createdAt": "2025-01-01T00:00:00Z",
  "updatedAt": "2025-01-01T00:00:00Z"
}
```

### 3.3 更新部門 (Update Department)

**Endpoint**: `PUT /api/departments/{departmentId}`

**Description**: 更新部門資訊

**Authentication Required**: ✅ Yes (Admin only)

**Path Parameters**:
- `departmentId` (string, required): 部門 ID

**Request Body**:
```json
{
  "name": "string"
}
```

**Request Example**:
```json
{
  "name": "Marketing Department"
}
```

**Response** (200 OK):
```json
{
  "id": "dept-uuid-3",
  "name": "Marketing Department",
  "organizationId": "org-uuid",
  "createdAt": "2025-01-01T00:00:00Z",
  "updatedAt": "2025-01-15T00:00:00Z"
}
```

### 3.4 刪除部門 (Delete Department)

**Endpoint**: `DELETE /api/departments/{departmentId}`

**Description**: 刪除部門

**Authentication Required**: ✅ Yes (Admin only)

**Path Parameters**:
- `departmentId` (string, required): 部門 ID

**Response** (200 OK):
```json
{
  "message": "Department deleted successfully"
}
```

**Error Responses**:
- `409 Conflict`: 部門仍有使用者，無法刪除
```json
{
  "error": "DEPARTMENT_IN_USE",
  "message": "Cannot delete department with active users"
}
```

---

## 4. 供應商 API (Supplier APIs)

### 4.1 取得供應商列表 (Get Suppliers)

**Endpoint**: `GET /api/suppliers`

**Description**: 取得所有供應商列表

**Authentication Required**: ✅ Yes

**Query Parameters**: None

**Response** (200 OK):
```json
{
  "data": [
    {
      "id": "supplier-uuid-1",
      "name": "Supplier Company A",
      "organizationId": "org-uuid",
      "type": "SUPPLIER",
      "createdAt": "2025-01-01T00:00:00Z",
      "updatedAt": "2025-01-01T00:00:00Z"
    },
    {
      "id": "supplier-uuid-2",
      "name": "Supplier Company B",
      "organizationId": "org-uuid",
      "type": "SUPPLIER",
      "createdAt": "2025-01-01T00:00:00Z",
      "updatedAt": "2025-01-01T00:00:00Z"
    }
  ]
}
```

---

## 5. 範本 API (Template APIs)

### 5.1 取得範本列表 (Get Templates)

**Endpoint**: `GET /api/templates`

**Description**: 取得範本列表

**Authentication Required**: ✅ Yes

**Query Parameters**:
- `type` (string, optional): 範本類型，可選值：`SAQ`, `CONFLICT`

**Request Example**:
```
GET /api/templates?type=SAQ
```

**Response** (200 OK):
```json
{
  "data": [
    {
      "id": "template-uuid-1",
      "name": "2025 SAQ Template",
      "type": "SAQ",
      "versions": [
        {
          "version": "1.0",
          "questions": [...],
          "createdAt": "2025-01-01T00:00:00Z"
        }
      ],
      "latestVersion": "1.0"
    }
  ]
}
```

### 5.2 取得單一範本 (Get Template)

**Endpoint**: `GET /api/templates/{templateId}`

**Description**: 取得單一範本詳細資訊

**Authentication Required**: ✅ Yes

**Path Parameters**:
- `templateId` (string, required): 範本 ID

**Response** (200 OK):
```json
{
  "id": "template-uuid-1",
  "name": "2025 SAQ Template",
  "type": "SAQ",
  "versions": [
    {
      "version": "1.0",
      "questions": [
        {
          "id": "q1",
          "text": "What is your company name?",
          "type": "TEXT",
          "required": true,
          "options": null,
          "config": {
            "maxLength": 200
          }
        },
        {
          "id": "q2",
          "text": "Select your industry",
          "type": "SINGLE_CHOICE",
          "required": true,
          "options": ["Manufacturing", "Services", "Retail"],
          "config": null
        },
        {
          "id": "q3",
          "text": "Number of employees",
          "type": "NUMBER",
          "required": true,
          "options": null,
          "config": {
            "numberMin": 1,
            "numberMax": 100000
          }
        },
        {
          "id": "q4",
          "text": "Upload company certificate",
          "type": "FILE",
          "required": false,
          "options": null,
          "config": {
            "maxFileSize": 10485760,
            "allowedFileTypes": ["pdf", "jpg", "png"]
          }
        },
        {
          "id": "q5",
          "text": "Rate your satisfaction",
          "type": "RATING",
          "required": true,
          "options": null,
          "config": {
            "ratingMin": 1,
            "ratingMax": 5,
            "ratingStep": 1
          }
        }
      ],
      "createdAt": "2025-01-01T00:00:00Z"
    }
  ],
  "latestVersion": "1.0"
}
```

### 5.3 建立範本 (Create Template)

**Endpoint**: `POST /api/templates`

**Description**: 建立新範本

**Authentication Required**: ✅ Yes (Admin only)

**Request Body**:
```json
{
  "name": "string",
  "type": "SAQ | CONFLICT",
  "questions": [
    {
      "id": "string",
      "text": "string",
      "type": "TEXT | NUMBER | DATE | BOOLEAN | SINGLE_CHOICE | MULTI_CHOICE | FILE | RATING",
      "required": "boolean",
      "options": ["string"] // for SINGLE_CHOICE and MULTI_CHOICE,
      "config": {
        // Question type specific configuration
      }
    }
  ]
}
```

**Request Example**:
```json
{
  "name": "2025 SAQ Template",
  "type": "SAQ",
  "questions": [
    {
      "id": "q1",
      "text": "Company Name",
      "type": "TEXT",
      "required": true,
      "config": {
        "maxLength": 200
      }
    },
    {
      "id": "q2",
      "text": "Industry Type",
      "type": "SINGLE_CHOICE",
      "required": true,
      "options": ["Manufacturing", "Services", "Retail"]
    }
  ]
}
```

**Response** (201 Created):
```json
{
  "id": "template-uuid-new",
  "name": "2025 SAQ Template",
  "type": "SAQ",
  "versions": [
    {
      "version": "1.0",
      "questions": [...],
      "createdAt": "2025-01-15T00:00:00Z"
    }
  ],
  "latestVersion": "1.0"
}
```

### 5.4 更新範本 (Update Template)

**Endpoint**: `PUT /api/templates/{templateId}`

**Description**: 更新範本（會建立新的草稿版本）

**Authentication Required**: ✅ Yes (Admin only)

**Path Parameters**:
- `templateId` (string, required): 範本 ID

**Request Body**:
```json
{
  "name": "string (optional)",
  "questions": [
    {
      "id": "string",
      "text": "string",
      "type": "TEXT | NUMBER | DATE | BOOLEAN | SINGLE_CHOICE | MULTI_CHOICE | FILE | RATING",
      "required": "boolean",
      "options": ["string"],
      "config": { ... }
    }
  ]
}
```

**Response** (200 OK):
```json
{
  "id": "template-uuid-1",
  "name": "2025 SAQ Template Updated",
  "type": "SAQ",
  "versions": [
    {
      "version": "1.0",
      "questions": [...],
      "createdAt": "2025-01-01T00:00:00Z"
    },
    {
      "version": "1.1-draft",
      "questions": [...],
      "createdAt": "2025-01-15T00:00:00Z"
    }
  ],
  "latestVersion": "1.0"
}
```

### 5.5 發布範本版本 (Publish Template Version)

**Endpoint**: `POST /api/templates/{templateId}/publish`

**Description**: 發布範本的草稿版本

**Authentication Required**: ✅ Yes (Admin only)

**Path Parameters**:
- `templateId` (string, required): 範本 ID

**Request Body**:
```json
{}
```

**Response** (200 OK):
```json
{
  "id": "template-uuid-1",
  "name": "2025 SAQ Template",
  "type": "SAQ",
  "versions": [
    {
      "version": "1.0",
      "questions": [...],
      "createdAt": "2025-01-01T00:00:00Z"
    },
    {
      "version": "1.1",
      "questions": [...],
      "createdAt": "2025-01-15T00:00:00Z"
    }
  ],
  "latestVersion": "1.1"
}
```

### 5.6 刪除範本 (Delete Template)

**Endpoint**: `DELETE /api/templates/{templateId}`

**Description**: 刪除範本

**Authentication Required**: ✅ Yes (Admin only)

**Path Parameters**:
- `templateId` (string, required): 範本 ID

**Response** (200 OK):
```json
{
  "message": "Template deleted successfully"
}
```

**Error Responses**:
- `409 Conflict`: 範本已被專案使用，無法刪除
```json
{
  "error": "TEMPLATE_IN_USE",
  "message": "Cannot delete template that is being used by projects"
}
```

---

## 6. 專案 API (Project APIs)

### 6.1 取得專案列表 (Get Projects)

**Endpoint**: `GET /api/projects`

**Description**: 取得專案列表

**Authentication Required**: ✅ Yes

**Query Parameters**:
- `type` (string, optional): 專案類型，可選值：`SAQ`, `CONFLICT`

**Request Example**:
```
GET /api/projects?type=SAQ
```

**Response** (200 OK):
```json
{
  "data": [
    {
      "id": "project-uuid-1",
      "name": "2025 Q1 SAQ Project",
      "year": 2025,
      "type": "SAQ",
      "templateId": "template-uuid-1",
      "templateVersion": "1.0",
      "supplierId": "supplier-uuid-1",
      "status": "IN_PROGRESS",
      "currentStage": 1,
      "reviewConfig": [
        {
          "stageOrder": 1,
          "departmentId": "dept-uuid-1",
          "department": {
            "id": "dept-uuid-1",
            "name": "Quality Assurance"
          },
          "approverId": "user-uuid-1"
        },
        {
          "stageOrder": 2,
          "departmentId": "dept-uuid-2",
          "department": {
            "id": "dept-uuid-2",
            "name": "Management"
          }
        }
      ],
      "createdAt": "2025-01-01T00:00:00Z",
      "updatedAt": "2025-01-15T00:00:00Z"
    }
  ]
}
```

### 6.2 取得單一專案 (Get Project)

**Endpoint**: `GET /api/projects/{projectId}`

**Description**: 取得單一專案詳細資訊

**Authentication Required**: ✅ Yes

**Path Parameters**:
- `projectId` (string, required): 專案 ID

**Response** (200 OK):
```json
{
  "id": "project-uuid-1",
  "name": "2025 Q1 SAQ Project",
  "year": 2025,
  "type": "SAQ",
  "templateId": "template-uuid-1",
  "templateVersion": "1.0",
  "supplierId": "supplier-uuid-1",
  "status": "IN_PROGRESS",
  "currentStage": 1,
  "reviewConfig": [
    {
      "stageOrder": 1,
      "departmentId": "dept-uuid-1",
      "department": {
        "id": "dept-uuid-1",
        "name": "Quality Assurance"
      },
      "approverId": "user-uuid-1"
    }
  ],
  "createdAt": "2025-01-01T00:00:00Z",
  "updatedAt": "2025-01-15T00:00:00Z"
}
```

### 6.3 建立專案 (Create Project)

**Endpoint**: `POST /api/projects`

**Description**: 建立新專案

**Authentication Required**: ✅ Yes

**Request Body**:
```json
{
  "name": "string",
  "year": "number",
  "type": "SAQ | CONFLICT",
  "templateId": "string",
  "supplierId": "string",
  "reviewConfig": [
    {
      "stageOrder": "number",
      "departmentId": "string",
      "approverId": "string (optional)"
    }
  ]
}
```

**Request Example**:
```json
{
  "name": "2025 Q1 SAQ Project",
  "year": 2025,
  "type": "SAQ",
  "templateId": "template-uuid-1",
  "supplierId": "supplier-uuid-1",
  "reviewConfig": [
    {
      "stageOrder": 1,
      "departmentId": "dept-uuid-1",
      "approverId": "user-uuid-1"
    },
    {
      "stageOrder": 2,
      "departmentId": "dept-uuid-2"
    }
  ]
}
```

**Response** (201 Created):
```json
{
  "id": "project-uuid-new",
  "name": "2025 Q1 SAQ Project",
  "year": 2025,
  "type": "SAQ",
  "templateId": "template-uuid-1",
  "templateVersion": "1.0",
  "supplierId": "supplier-uuid-1",
  "status": "DRAFT",
  "currentStage": 0,
  "reviewConfig": [...],
  "createdAt": "2025-01-15T00:00:00Z",
  "updatedAt": "2025-01-15T00:00:00Z"
}
```

### 6.4 更新專案 (Update Project)

**Endpoint**: `PUT /api/projects/{projectId}`

**Description**: 更新專案資訊

**Authentication Required**: ✅ Yes

**Path Parameters**:
- `projectId` (string, required): 專案 ID

**Request Body**:
```json
{
  "name": "string (optional)",
  "year": "number (optional)",
  "supplierId": "string (optional)",
  "reviewConfig": [
    {
      "stageOrder": "number",
      "departmentId": "string",
      "approverId": "string (optional)"
    }
  ] // (optional)
}
```

**Response** (200 OK):
```json
{
  "id": "project-uuid-1",
  "name": "Updated Project Name",
  "year": 2025,
  "type": "SAQ",
  "templateId": "template-uuid-1",
  "templateVersion": "1.0",
  "supplierId": "supplier-uuid-1",
  "status": "DRAFT",
  "currentStage": 0,
  "reviewConfig": [...],
  "createdAt": "2025-01-01T00:00:00Z",
  "updatedAt": "2025-01-15T12:00:00Z"
}
```

### 6.5 刪除專案 (Delete Project)

**Endpoint**: `DELETE /api/projects/{projectId}`

**Description**: 刪除專案

**Authentication Required**: ✅ Yes

**Path Parameters**:
- `projectId` (string, required): 專案 ID

**Response** (200 OK):
```json
{
  "message": "Project deleted successfully"
}
```

---

## 7. 答案 API (Answer APIs)

### 7.1 取得專案答案 (Get Answers)

**Endpoint**: `GET /api/projects/{projectId}/answers`

**Description**: 取得專案的答案資料

**Authentication Required**: ✅ Yes

**Path Parameters**:
- `projectId` (string, required): 專案 ID

**Response** (200 OK):
```json
{
  "projectId": "project-uuid-1",
  "answers": {
    "q1": {
      "questionId": "q1",
      "value": "ABC Corporation"
    },
    "q2": {
      "questionId": "q2",
      "value": "Manufacturing"
    },
    "q3": {
      "questionId": "q3",
      "value": 500
    },
    "q4": {
      "questionId": "q4",
      "value": {
        "fileName": "certificate.pdf",
        "fileUrl": "https://storage.example.com/files/cert.pdf",
        "fileSize": 1048576
      }
    },
    "q5": {
      "questionId": "q5",
      "value": 4
    }
  },
  "lastSavedAt": "2025-01-15T10:30:00Z"
}
```

### 7.2 儲存答案 (Save Answers)

**Endpoint**: `POST /api/projects/{projectId}/answers`

**Description**: 儲存專案答案（草稿）

**Authentication Required**: ✅ Yes

**Path Parameters**:
- `projectId` (string, required): 專案 ID

**Request Body**:
```json
{
  "answers": {
    "questionId": {
      "questionId": "string",
      "value": "any"
    }
  }
}
```

**Request Example**:
```json
{
  "answers": {
    "q1": {
      "questionId": "q1",
      "value": "ABC Corporation"
    },
    "q2": {
      "questionId": "q2",
      "value": "Manufacturing"
    },
    "q3": {
      "questionId": "q3",
      "value": 500
    }
  }
}
```

**Response** (200 OK):
```json
{
  "projectId": "project-uuid-1",
  "answers": { ... },
  "lastSavedAt": "2025-01-15T10:35:00Z"
}
```

### 7.3 提交答案 (Submit Answers)

**Endpoint**: `POST /api/projects/{projectId}/submit`

**Description**: 提交專案答案並進入審核流程

**Authentication Required**: ✅ Yes

**Path Parameters**:
- `projectId` (string, required): 專案 ID

**Request Body**:
```json
{
  "answers": {
    "questionId": {
      "questionId": "string",
      "value": "any"
    }
  }
}
```

**Request Example**:
```json
{
  "answers": {
    "q1": {
      "questionId": "q1",
      "value": "ABC Corporation"
    },
    "q2": {
      "questionId": "q2",
      "value": "Manufacturing"
    },
    "q3": {
      "questionId": "q3",
      "value": 500
    }
  }
}
```

**Response** (200 OK):
```json
{
  "projectId": "project-uuid-1",
  "status": "SUBMITTED",
  "currentStage": 1,
  "message": "Answers submitted successfully and sent for review"
}
```

**Error Responses**:
- `400 Bad Request`: 必填題目未填寫完整
```json
{
  "error": "INCOMPLETE_ANSWERS",
  "message": "Required questions not answered",
  "missingQuestions": ["q1", "q5"]
}
```

---

## 8. 審核 API (Review APIs)

### 8.1 取得待審核專案 (Get Pending Reviews)

**Endpoint**: `GET /api/review/pending`

**Description**: 取得目前使用者待審核的專案列表

**Authentication Required**: ✅ Yes

**Query Parameters**: None

**Response** (200 OK):
```json
{
  "data": [
    {
      "id": "project-uuid-1",
      "name": "2025 Q1 SAQ Project",
      "year": 2025,
      "type": "SAQ",
      "templateId": "template-uuid-1",
      "templateVersion": "1.0",
      "supplierId": "supplier-uuid-1",
      "status": "REVIEWING",
      "currentStage": 1,
      "reviewConfig": [...],
      "createdAt": "2025-01-01T00:00:00Z",
      "updatedAt": "2025-01-15T00:00:00Z"
    }
  ]
}
```

### 8.2 取得專案審核記錄 (Get Review Logs)

**Endpoint**: `GET /api/projects/{projectId}/review-logs`

**Description**: 取得專案的審核記錄

**Authentication Required**: ✅ Yes

**Path Parameters**:
- `projectId` (string, required): 專案 ID

**Response** (200 OK):
```json
{
  "data": [
    {
      "id": "review-log-uuid-1",
      "projectId": "project-uuid-1",
      "reviewerId": "user-uuid-1",
      "reviewerName": "John Doe",
      "stage": 1,
      "action": "APPROVE",
      "comment": "All requirements met",
      "timestamp": "2025-01-15T14:30:00Z"
    },
    {
      "id": "review-log-uuid-2",
      "projectId": "project-uuid-1",
      "reviewerId": "user-uuid-2",
      "reviewerName": "Jane Smith",
      "stage": 1,
      "action": "RETURN",
      "comment": "Please provide more details on question 3",
      "timestamp": "2025-01-14T10:15:00Z"
    }
  ]
}
```

### 8.3 核准專案 (Approve Project)

**Endpoint**: `POST /api/projects/{projectId}/approve`

**Description**: 核准專案，進入下一階段或完成審核

**Authentication Required**: ✅ Yes

**Path Parameters**:
- `projectId` (string, required): 專案 ID

**Request Body**:
```json
{
  "comment": "string"
}
```

**Request Example**:
```json
{
  "comment": "All requirements met, approved for next stage"
}
```

**Response** (200 OK):
```json
{
  "id": "project-uuid-1",
  "status": "APPROVED",
  "currentStage": 2,
  "message": "Project approved successfully"
}
```

### 8.4 退回專案 (Return Project)

**Endpoint**: `POST /api/projects/{projectId}/return`

**Description**: 退回專案給供應商修改

**Authentication Required**: ✅ Yes

**Path Parameters**:
- `projectId` (string, required): 專案 ID

**Request Body**:
```json
{
  "comment": "string"
}
```

**Request Example**:
```json
{
  "comment": "Please provide more details on question 3 and upload required certificates"
}
```

**Response** (200 OK):
```json
{
  "id": "project-uuid-1",
  "status": "RETURNED",
  "currentStage": 0,
  "message": "Project returned to supplier for revision"
}
```

---

## 9. 資料模型 (Data Models)

### User
```typescript
{
  id: string
  username: string
  email: string
  phone: string
  departmentId: string
  department?: Department
  role: 'HOST' | 'SUPPLIER'
  organizationId: string
  organization?: Organization
}
```

### Organization
```typescript
{
  id: string
  name: string
  type: 'HOST' | 'SUPPLIER'
  createdAt: string (ISO 8601)
  updatedAt: string (ISO 8601)
}
```

### Department
```typescript
{
  id: string
  name: string
  organizationId: string
  createdAt: string (ISO 8601)
  updatedAt: string (ISO 8601)
}
```

### Template
```typescript
{
  id: string
  name: string
  type: 'SAQ' | 'CONFLICT'
  versions: TemplateVersion[]
  latestVersion: string
}
```

### TemplateVersion
```typescript
{
  version: string
  questions: Question[]
  createdAt: string (ISO 8601)
}
```

### Question
```typescript
{
  id: string
  text: string
  type: 'TEXT' | 'NUMBER' | 'DATE' | 'BOOLEAN' | 'SINGLE_CHOICE' | 'MULTI_CHOICE' | 'FILE' | 'RATING'
  required: boolean
  options?: string[]  // For SINGLE_CHOICE and MULTI_CHOICE
  config?: QuestionConfig
}
```

### QuestionConfig
```typescript
{
  // For FILE type
  maxFileSize?: number  // in bytes
  allowedFileTypes?: string[]  // e.g., ['pdf', 'jpg', 'png']
  
  // For RATING type
  ratingMin?: number
  ratingMax?: number
  ratingStep?: number
  
  // For NUMBER type
  numberMin?: number
  numberMax?: number
  
  // For TEXT type
  maxLength?: number
}
```

### Project
```typescript
{
  id: string
  name: string
  year: number
  type: 'SAQ' | 'CONFLICT'
  templateId: string
  templateVersion: string
  supplierId: string
  status: 'DRAFT' | 'IN_PROGRESS' | 'SUBMITTED' | 'REVIEWING' | 'APPROVED' | 'RETURNED'
  currentStage: number
  reviewConfig: ReviewStageConfig[]
  createdAt: string (ISO 8601)
  updatedAt: string (ISO 8601)
}
```

### ReviewStageConfig
```typescript
{
  stageOrder: number
  departmentId: string
  department?: Department
  approverId?: string  // Optional specific approver
}
```

### Answer
```typescript
{
  questionId: string
  value: any  // Type depends on question type
}
```

### ProjectAnswers
```typescript
{
  projectId: string
  answers: Record<string, Answer>
  lastSavedAt: string (ISO 8601)
}
```

### ReviewLog
```typescript
{
  id: string
  projectId: string
  reviewerId: string
  reviewerName: string
  stage: number
  action: 'APPROVE' | 'RETURN'
  comment: string
  timestamp: string (ISO 8601)
}
```

---

## 10. 錯誤處理 (Error Handling)

前端應用程式使用 `useApi` composable 處理所有 API 錯誤，並透過 `parseApiError` 和 `handleResponseStatus` 函數統一處理錯誤回應。

### 錯誤類型 (Error Types)

```typescript
interface ErrorResponse {
  error: string        // Error type/code
  message: string      // Human-readable error message
  statusCode?: number  // HTTP status code
}
```

### 常見錯誤碼 (Common Error Codes)

| Error Code | HTTP Status | Description |
|-----------|-------------|-------------|
| `INVALID_CREDENTIALS` | 401 | 帳號或密碼錯誤 |
| `UNAUTHORIZED` | 401 | 未認證或 Token 無效 |
| `FORBIDDEN` | 403 | 無權限存取此資源 |
| `NOT_FOUND` | 404 | 資源不存在 |
| `INVALID_PASSWORD` | 400 | 密碼格式錯誤或當前密碼不正確 |
| `DEPARTMENT_IN_USE` | 409 | 部門仍有使用者，無法刪除 |
| `TEMPLATE_IN_USE` | 409 | 範本已被專案使用，無法刪除 |
| `INCOMPLETE_ANSWERS` | 400 | 必填題目未填寫完整 |
| `VALIDATION_ERROR` | 400 | 請求資料驗證失敗 |
| `SERVER_ERROR` | 500 | 伺服器內部錯誤 |

---

## 11. 檔案上傳 (File Upload)

對於 `FILE` 類型的問題，答案值應包含檔案相關資訊：

### 檔案答案格式 (File Answer Format)
```typescript
{
  questionId: "q4",
  value: {
    fileName: "certificate.pdf",
    fileUrl: "https://storage.example.com/files/cert.pdf",
    fileSize: 1048576,  // in bytes
    mimeType: "application/pdf"
  }
}
```

### 檔案上傳流程 (File Upload Flow)
1. 客戶端選擇檔案
2. 呼叫檔案上傳 API（需實作）取得上傳 URL
3. 將檔案上傳至儲存服務
4. 將檔案資訊儲存在答案中

**注意**: 具體的檔案上傳 API 端點需要後端團隊實作並提供。

---

## 12. 分頁支援 (Pagination Support)

對於可能返回大量資料的 API，建議實作分頁功能：

### 分頁參數 (Pagination Parameters)
```
GET /api/projects?page=1&pageSize=20&type=SAQ
```

- `page` (number, optional): 頁碼，從 1 開始，預設為 1
- `pageSize` (number, optional): 每頁項目數量，預設為 20，最大為 100

### 分頁回應格式 (Paginated Response)
```json
{
  "items": [...],
  "total": 150,
  "page": 1,
  "pageSize": 20,
  "totalPages": 8
}
```

---

## 13. 開發注意事項 (Development Notes)

### 認證 Token 處理
- Token 儲存在 Pinia store (`authStore`)
- 所有需要認證的請求自動注入 `Authorization` header
- Token 過期時應導向登入頁面

### 錯誤處理最佳實踐
- 使用 `useApi` composable 的 `error` 和 `isLoading` 狀態
- 在 UI 中顯示適當的錯誤訊息
- 對於 401 錯誤，自動登出並重導向至登入頁

### API 呼叫範例
```typescript
// 在 Vue component 中使用
import { useProjects } from '~/composables/useProjects'

const { projects, fetchProjects, createProject } = useProjects()

// 取得專案列表
await fetchProjects('SAQ')

// 建立新專案
const newProject = await createProject({
  name: '2025 Q1 SAQ Project',
  year: 2025,
  type: 'SAQ',
  templateId: 'template-uuid-1',
  supplierId: 'supplier-uuid-1',
  reviewConfig: [...]
})
```

### 環境變數設定
```bash
# .env 檔案
API_BASE_URL=http://localhost:3000
```

---

## 附錄：API 端點總覽 (Appendix: API Endpoints Summary)

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| POST | `/api/auth/login` | 登入 | ❌ |
| PUT | `/api/users/{userId}` | 更新個人資料 | ✅ |
| POST | `/api/users/change-password` | 變更密碼 | ✅ |
| GET | `/api/departments` | 取得部門列表 | ✅ |
| POST | `/api/departments` | 建立部門 | ✅ (Admin) |
| PUT | `/api/departments/{id}` | 更新部門 | ✅ (Admin) |
| DELETE | `/api/departments/{id}` | 刪除部門 | ✅ (Admin) |
| GET | `/api/suppliers` | 取得供應商列表 | ✅ |
| GET | `/api/templates` | 取得範本列表 | ✅ |
| GET | `/api/templates/{id}` | 取得單一範本 | ✅ |
| POST | `/api/templates` | 建立範本 | ✅ (Admin) |
| PUT | `/api/templates/{id}` | 更新範本 | ✅ (Admin) |
| POST | `/api/templates/{id}/publish` | 發布範本版本 | ✅ (Admin) |
| DELETE | `/api/templates/{id}` | 刪除範本 | ✅ (Admin) |
| GET | `/api/projects` | 取得專案列表 | ✅ |
| GET | `/api/projects/{id}` | 取得單一專案 | ✅ |
| POST | `/api/projects` | 建立專案 | ✅ |
| PUT | `/api/projects/{id}` | 更新專案 | ✅ |
| DELETE | `/api/projects/{id}` | 刪除專案 | ✅ |
| GET | `/api/projects/{id}/answers` | 取得專案答案 | ✅ |
| POST | `/api/projects/{id}/answers` | 儲存答案 | ✅ |
| POST | `/api/projects/{id}/submit` | 提交答案 | ✅ |
| GET | `/api/review/pending` | 取得待審核專案 | ✅ |
| GET | `/api/projects/{id}/review-logs` | 取得審核記錄 | ✅ |
| POST | `/api/projects/{id}/approve` | 核准專案 | ✅ |
| POST | `/api/projects/{id}/return` | 退回專案 | ✅ |

---

## 版本歷史 (Version History)

- **v1.0.0** (2025-12-02): 初版文件，包含所有前端所需的 API 規格
