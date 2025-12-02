# CRM Questionnaire System - Frontend API Requirements

## 文件說明 (Document Overview)

本文件詳細描述 CRM Questionnaire System 前端應用程式所需的所有 API 端點、請求格式、回應格式及錯誤處理機制。

This document details all API endpoints, request formats, response formats, and error handling mechanisms required by the CRM Questionnaire System frontend application.

**版本 (Version):** 1.0.0  
**最後更新 (Last Updated):** 2025-12-02  
**前端技術棧 (Frontend Tech Stack):** Nuxt 3, Vue 3, TypeScript

---

## 目錄 (Table of Contents)

1. [通用規範 (General Specifications)](#通用規範-general-specifications)
2. [認證 API (Authentication APIs)](#認證-api-authentication-apis)
3. [使用者管理 API (User Management APIs)](#使用者管理-api-user-management-apis)
4. [專案管理 API (Project Management APIs)](#專案管理-api-project-management-apis)
5. [範本管理 API (Template Management APIs)](#範本管理-api-template-management-apis)
6. [供應商管理 API (Supplier Management APIs)](#供應商管理-api-supplier-management-apis)
7. [部門管理 API (Department Management APIs)](#部門管理-api-department-management-apis)
8. [問卷回答 API (Answer Management APIs)](#問卷回答-api-answer-management-apis)
9. [審核流程 API (Review Process APIs)](#審核流程-api-review-process-apis)
10. [錯誤處理 (Error Handling)](#錯誤處理-error-handling)

---

## 通用規範 (General Specifications)

### Base URL

```
Development: http://localhost:3000/api
Production: {PRODUCTION_API_URL}/api
```

### 認證機制 (Authentication)

所有需要認證的 API 端點都必須在 HTTP Header 中包含 Bearer Token：

```
Authorization: Bearer {token}
```

### 請求格式 (Request Format)

- Content-Type: `application/json`
- 所有請求參數使用 camelCase 命名規則
- 日期格式使用 ISO 8601 標準 (YYYY-MM-DDTHH:mm:ss.sssZ)

### 回應格式 (Response Format)

成功回應：
```json
{
  "data": {...},
  "message": "Success message (optional)"
}
```

錯誤回應：
```json
{
  "error": "Error type",
  "message": "Detailed error message",
  "statusCode": 400
}
```

### HTTP Status Codes

- `200 OK` - 請求成功
- `201 Created` - 資源創建成功
- `400 Bad Request` - 請求參數錯誤
- `401 Unauthorized` - 未認證或 Token 無效
- `403 Forbidden` - 沒有權限執行此操作
- `404 Not Found` - 資源不存在
- `500 Internal Server Error` - 伺服器內部錯誤

---

## 認證 API (Authentication APIs)

### 1. 使用者登入 (User Login)

**端點 (Endpoint):** `POST /auth/login`

**需要認證 (Authentication Required):** ❌ No

**描述 (Description):**  
使用者使用帳號密碼登入系統，成功後返回 JWT Token 和使用者資訊。

**請求參數 (Request Body):**
```typescript
{
  username: string;  // 使用者帳號
  password: string;  // 使用者密碼
}
```

**請求範例 (Request Example):**
```json
{
  "username": "john.doe",
  "password": "SecurePassword123"
}
```

**成功回應 (Success Response):** `200 OK`
```typescript
{
  token: string;     // JWT Token
  user: {
    id: string;
    username: string;
    email: string;
    phone: string;
    departmentId: string;
    department?: {
      id: string;
      name: string;
      organizationId: string;
      createdAt: string;
      updatedAt: string;
    };
    role: 'HOST' | 'SUPPLIER';
    organizationId: string;
    organization?: {
      id: string;
      name: string;
      type: 'HOST' | 'SUPPLIER';
      createdAt: string;
      updatedAt: string;
    };
  }
}
```

**回應範例 (Response Example):**
```json
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": "user-123",
    "username": "john.doe",
    "email": "john.doe@example.com",
    "phone": "+886912345678",
    "departmentId": "dept-001",
    "department": {
      "id": "dept-001",
      "name": "品質管理部",
      "organizationId": "org-001",
      "createdAt": "2025-01-01T00:00:00.000Z",
      "updatedAt": "2025-01-01T00:00:00.000Z"
    },
    "role": "HOST",
    "organizationId": "org-001"
  }
}
```

**錯誤回應 (Error Responses):**
- `400 Bad Request` - 缺少必要參數
- `401 Unauthorized` - 帳號密碼錯誤

---

## 使用者管理 API (User Management APIs)

### 2. 更新使用者資料 (Update User Profile)

**端點 (Endpoint):** `PUT /users/{userId}`

**需要認證 (Authentication Required):** ✅ Yes

**描述 (Description):**  
更新目前登入使用者的個人資料。

**路徑參數 (Path Parameters):**
- `userId` (string) - 使用者 ID

**請求參數 (Request Body):**
```typescript
{
  email?: string;    // Email 地址 (可選)
  phone?: string;    // 電話號碼 (可選)
  // 其他可更新的欄位
}
```

**請求範例 (Request Example):**
```json
{
  "email": "new.email@example.com",
  "phone": "+886987654321"
}
```

**成功回應 (Success Response):** `200 OK`
```typescript
{
  id: string;
  username: string;
  email: string;
  phone: string;
  departmentId: string;
  role: 'HOST' | 'SUPPLIER';
  organizationId: string;
}
```

**錯誤回應 (Error Responses):**
- `400 Bad Request` - 請求參數格式錯誤
- `401 Unauthorized` - Token 無效或過期
- `403 Forbidden` - 沒有權限更新此使用者
- `404 Not Found` - 使用者不存在

### 3. 變更密碼 (Change Password)

**端點 (Endpoint):** `POST /users/change-password`

**需要認證 (Authentication Required):** ✅ Yes

**描述 (Description):**  
變更目前登入使用者的密碼。

**請求參數 (Request Body):**
```typescript
{
  currentPassword: string;  // 當前密碼
  newPassword: string;      // 新密碼
}
```

**請求範例 (Request Example):**
```json
{
  "currentPassword": "OldPassword123",
  "newPassword": "NewSecurePassword456"
}
```

**成功回應 (Success Response):** `200 OK`
```json
{
  "message": "Password changed successfully"
}
```

**錯誤回應 (Error Responses):**
- `400 Bad Request` - 密碼格式不符合要求
- `401 Unauthorized` - 當前密碼錯誤
- `401 Unauthorized` - Token 無效或過期

---

## 專案管理 API (Project Management APIs)

### 4. 取得專案列表 (Get Projects)

**端點 (Endpoint):** `GET /projects`

**需要認證 (Authentication Required):** ✅ Yes

**描述 (Description):**  
取得專案列表，可依類型篩選 (SAQ 或 CONFLICT)。供應商只能看到指派給自己的專案。

**查詢參數 (Query Parameters):**
- `type` (string, optional) - 專案類型: `SAQ` | `CONFLICT`

**請求範例 (Request Example):**
```
GET /projects?type=SAQ
```

**成功回應 (Success Response):** `200 OK`
```typescript
{
  data: Array<{
    id: string;
    name: string;
    year: number;
    type: 'SAQ' | 'CONFLICT';
    templateId: string;
    templateVersion: string;
    supplierId: string;
    status: 'DRAFT' | 'IN_PROGRESS' | 'SUBMITTED' | 'REVIEWING' | 'APPROVED' | 'RETURNED';
    currentStage: number;
    reviewConfig: Array<{
      stageOrder: number;
      departmentId: string;
      department?: {
        id: string;
        name: string;
        organizationId: string;
      };
      approverId?: string;
    }>;
    createdAt: string;
    updatedAt: string;
  }>
}
```

**回應範例 (Response Example):**
```json
{
  "data": [
    {
      "id": "project-001",
      "name": "2025 年度 SAQ 專案",
      "year": 2025,
      "type": "SAQ",
      "templateId": "template-001",
      "templateVersion": "1.0.0",
      "supplierId": "supplier-001",
      "status": "IN_PROGRESS",
      "currentStage": 0,
      "reviewConfig": [
        {
          "stageOrder": 1,
          "departmentId": "dept-001",
          "department": {
            "id": "dept-001",
            "name": "品質管理部"
          }
        }
      ],
      "createdAt": "2025-01-15T08:00:00.000Z",
      "updatedAt": "2025-01-15T08:00:00.000Z"
    }
  ]
}
```

### 5. 取得單一專案 (Get Project by ID)

**端點 (Endpoint):** `GET /projects/{id}`

**需要認證 (Authentication Required):** ✅ Yes

**描述 (Description):**  
取得特定專案的詳細資訊。

**路徑參數 (Path Parameters):**
- `id` (string) - 專案 ID

**成功回應 (Success Response):** `200 OK`
```typescript
{
  id: string;
  name: string;
  year: number;
  type: 'SAQ' | 'CONFLICT';
  templateId: string;
  templateVersion: string;
  supplierId: string;
  status: ProjectStatus;
  currentStage: number;
  reviewConfig: ReviewStageConfig[];
  createdAt: string;
  updatedAt: string;
}
```

**錯誤回應 (Error Responses):**
- `401 Unauthorized` - Token 無效或過期
- `403 Forbidden` - 沒有權限查看此專案
- `404 Not Found` - 專案不存在

### 6. 建立專案 (Create Project)

**端點 (Endpoint):** `POST /projects`

**需要認證 (Authentication Required):** ✅ Yes (僅限 HOST 角色)

**描述 (Description):**  
建立新專案並指派給供應商。

**請求參數 (Request Body):**
```typescript
{
  name: string;           // 專案名稱
  year: number;          // 年份
  type: 'SAQ' | 'CONFLICT';  // 專案類型
  templateId: string;    // 範本 ID
  supplierId: string;    // 供應商 ID
  reviewConfig: Array<{  // 審核流程配置
    stageOrder: number;
    departmentId: string;
    approverId?: string;
  }>;
}
```

**請求範例 (Request Example):**
```json
{
  "name": "2025 年度 SAQ 專案",
  "year": 2025,
  "type": "SAQ",
  "templateId": "template-001",
  "supplierId": "supplier-001",
  "reviewConfig": [
    {
      "stageOrder": 1,
      "departmentId": "dept-001"
    },
    {
      "stageOrder": 2,
      "departmentId": "dept-002"
    }
  ]
}
```

**成功回應 (Success Response):** `201 Created`
```typescript
{
  id: string;
  name: string;
  year: number;
  type: 'SAQ' | 'CONFLICT';
  templateId: string;
  templateVersion: string;
  supplierId: string;
  status: 'DRAFT';
  currentStage: 0;
  reviewConfig: ReviewStageConfig[];
  createdAt: string;
  updatedAt: string;
}
```

**錯誤回應 (Error Responses):**
- `400 Bad Request` - 請求參數錯誤或缺少必要欄位
- `401 Unauthorized` - Token 無效或過期
- `403 Forbidden` - 沒有權限建立專案 (非 HOST 角色)
- `404 Not Found` - 範本或供應商不存在

### 7. 更新專案 (Update Project)

**端點 (Endpoint):** `PUT /projects/{id}`

**需要認證 (Authentication Required):** ✅ Yes (僅限 HOST 角色)

**描述 (Description):**  
更新專案資訊。

**路徑參數 (Path Parameters):**
- `id` (string) - 專案 ID

**請求參數 (Request Body):**
```typescript
{
  name?: string;
  year?: number;
  supplierId?: string;
  reviewConfig?: ReviewStageConfig[];
}
```

**成功回應 (Success Response):** `200 OK`
```typescript
{
  id: string;
  name: string;
  year: number;
  // ... 完整專案物件
}
```

**錯誤回應 (Error Responses):**
- `400 Bad Request` - 請求參數錯誤
- `401 Unauthorized` - Token 無效或過期
- `403 Forbidden` - 沒有權限更新專案
- `404 Not Found` - 專案不存在

### 8. 刪除專案 (Delete Project)

**端點 (Endpoint):** `DELETE /projects/{id}`

**需要認證 (Authentication Required):** ✅ Yes (僅限 HOST 角色)

**描述 (Description):**  
刪除專案。

**路徑參數 (Path Parameters):**
- `id` (string) - 專案 ID

**成功回應 (Success Response):** `200 OK`
```json
{
  "message": "Project deleted successfully"
}
```

**錯誤回應 (Error Responses):**
- `401 Unauthorized` - Token 無效或過期
- `403 Forbidden` - 沒有權限刪除專案
- `404 Not Found` - 專案不存在

---

## 範本管理 API (Template Management APIs)

### 9. 取得範本列表 (Get Templates)

**端點 (Endpoint):** `GET /templates`

**需要認證 (Authentication Required):** ✅ Yes

**描述 (Description):**  
取得範本列表，可依類型篩選。

**查詢參數 (Query Parameters):**
- `type` (string, optional) - 範本類型: `SAQ` | `CONFLICT`

**請求範例 (Request Example):**
```
GET /templates?type=SAQ
```

**成功回應 (Success Response):** `200 OK`
```typescript
{
  data: Array<{
    id: string;
    name: string;
    type: 'SAQ' | 'CONFLICT';
    versions: Array<{
      version: string;
      questions: Array<{
        id: string;
        text: string;
        type: 'TEXT' | 'NUMBER' | 'DATE' | 'BOOLEAN' | 'SINGLE_CHOICE' | 'MULTI_CHOICE' | 'FILE' | 'RATING';
        required: boolean;
        options?: string[];
        config?: {
          maxFileSize?: number;
          allowedFileTypes?: string[];
          ratingMin?: number;
          ratingMax?: number;
          ratingStep?: number;
          numberMin?: number;
          numberMax?: number;
          maxLength?: number;
        };
      }>;
      createdAt: string;
    }>;
    latestVersion: string;
  }>
}
```

**回應範例 (Response Example):**
```json
{
  "data": [
    {
      "id": "template-001",
      "name": "標準 SAQ 範本",
      "type": "SAQ",
      "versions": [
        {
          "version": "1.0.0",
          "questions": [
            {
              "id": "q1",
              "text": "請描述貴公司的品質管理系統",
              "type": "TEXT",
              "required": true,
              "config": {
                "maxLength": 1000
              }
            }
          ],
          "createdAt": "2025-01-01T00:00:00.000Z"
        }
      ],
      "latestVersion": "1.0.0"
    }
  ]
}
```

### 10. 取得單一範本 (Get Template by ID)

**端點 (Endpoint):** `GET /templates/{id}`

**需要認證 (Authentication Required):** ✅ Yes

**描述 (Description):**  
取得特定範本的詳細資訊，包含所有版本。

**路徑參數 (Path Parameters):**
- `id` (string) - 範本 ID

**成功回應 (Success Response):** `200 OK`
```typescript
{
  id: string;
  name: string;
  type: 'SAQ' | 'CONFLICT';
  versions: TemplateVersion[];
  latestVersion: string;
}
```

**錯誤回應 (Error Responses):**
- `401 Unauthorized` - Token 無效或過期
- `404 Not Found` - 範本不存在

### 11. 建立範本 (Create Template)

**端點 (Endpoint):** `POST /templates`

**需要認證 (Authentication Required):** ✅ Yes (僅限 HOST 角色)

**描述 (Description):**  
建立新範本。

**請求參數 (Request Body):**
```typescript
{
  name: string;
  type: 'SAQ' | 'CONFLICT';
  questions: Array<{
    id: string;
    text: string;
    type: QuestionType;
    required: boolean;
    options?: string[];
    config?: QuestionConfig;
  }>;
}
```

**請求範例 (Request Example):**
```json
{
  "name": "新 SAQ 範本",
  "type": "SAQ",
  "questions": [
    {
      "id": "q1",
      "text": "請描述貴公司的品質管理系統",
      "type": "TEXT",
      "required": true,
      "config": {
        "maxLength": 1000
      }
    }
  ]
}
```

**成功回應 (Success Response):** `201 Created`
```typescript
{
  id: string;
  name: string;
  type: 'SAQ' | 'CONFLICT';
  versions: TemplateVersion[];
  latestVersion: string;
}
```

**錯誤回應 (Error Responses):**
- `400 Bad Request` - 請求參數錯誤
- `401 Unauthorized` - Token 無效或過期
- `403 Forbidden` - 沒有權限建立範本

### 12. 更新範本 (Update Template)

**端點 (Endpoint):** `PUT /templates/{id}`

**需要認證 (Authentication Required):** ✅ Yes (僅限 HOST 角色)

**描述 (Description):**  
更新範本，會建立新版本。

**路徑參數 (Path Parameters):**
- `id` (string) - 範本 ID

**請求參數 (Request Body):**
```typescript
{
  name?: string;
  questions?: Question[];
}
```

**成功回應 (Success Response):** `200 OK`
```typescript
{
  id: string;
  name: string;
  type: 'SAQ' | 'CONFLICT';
  versions: TemplateVersion[];
  latestVersion: string;
}
```

**錯誤回應 (Error Responses):**
- `400 Bad Request` - 請求參數錯誤
- `401 Unauthorized` - Token 無效或過期
- `403 Forbidden` - 沒有權限更新範本
- `404 Not Found` - 範本不存在

### 13. 發布範本版本 (Publish Template Version)

**端點 (Endpoint):** `POST /templates/{id}/publish`

**需要認證 (Authentication Required):** ✅ Yes (僅限 HOST 角色)

**描述 (Description):**  
發布範本的新版本。

**路徑參數 (Path Parameters):**
- `id` (string) - 範本 ID

**請求參數 (Request Body):**
```json
{}
```

**成功回應 (Success Response):** `200 OK`
```typescript
{
  id: string;
  name: string;
  type: 'SAQ' | 'CONFLICT';
  versions: TemplateVersion[];
  latestVersion: string;
}
```

**錯誤回應 (Error Responses):**
- `401 Unauthorized` - Token 無效或過期
- `403 Forbidden` - 沒有權限發布範本
- `404 Not Found` - 範本不存在

### 14. 刪除範本 (Delete Template)

**端點 (Endpoint):** `DELETE /templates/{id}`

**需要認證 (Authentication Required):** ✅ Yes (僅限 HOST 角色)

**描述 (Description):**  
刪除範本。

**路徑參數 (Path Parameters):**
- `id` (string) - 範本 ID

**成功回應 (Success Response):** `200 OK`
```json
{
  "message": "Template deleted successfully"
}
```

**錯誤回應 (Error Responses):**
- `401 Unauthorized` - Token 無效或過期
- `403 Forbidden` - 沒有權限刪除範本
- `404 Not Found` - 範本不存在
- `409 Conflict` - 範本正在被專案使用中

---

## 供應商管理 API (Supplier Management APIs)

### 15. 取得供應商列表 (Get Suppliers)

**端點 (Endpoint):** `GET /suppliers`

**需要認證 (Authentication Required):** ✅ Yes

**描述 (Description):**  
取得所有供應商列表。

**成功回應 (Success Response):** `200 OK`
```typescript
{
  data: Array<{
    id: string;
    name: string;
    type: 'SUPPLIER';
    createdAt: string;
    updatedAt: string;
  }>
}
```

**回應範例 (Response Example):**
```json
{
  "data": [
    {
      "id": "supplier-001",
      "name": "ABC 供應商公司",
      "type": "SUPPLIER",
      "createdAt": "2025-01-01T00:00:00.000Z",
      "updatedAt": "2025-01-01T00:00:00.000Z"
    }
  ]
}
```

**錯誤回應 (Error Responses):**
- `401 Unauthorized` - Token 無效或過期

---

## 部門管理 API (Department Management APIs)

### 16. 取得部門列表 (Get Departments)

**端點 (Endpoint):** `GET /departments`

**需要認證 (Authentication Required):** ✅ Yes

**描述 (Description):**  
取得組織內所有部門列表。

**成功回應 (Success Response):** `200 OK`
```typescript
{
  data: Array<{
    id: string;
    name: string;
    organizationId: string;
    createdAt: string;
    updatedAt: string;
  }>
}
```

**回應範例 (Response Example):**
```json
{
  "data": [
    {
      "id": "dept-001",
      "name": "品質管理部",
      "organizationId": "org-001",
      "createdAt": "2025-01-01T00:00:00.000Z",
      "updatedAt": "2025-01-01T00:00:00.000Z"
    },
    {
      "id": "dept-002",
      "name": "採購部",
      "organizationId": "org-001",
      "createdAt": "2025-01-01T00:00:00.000Z",
      "updatedAt": "2025-01-01T00:00:00.000Z"
    }
  ]
}
```

**錯誤回應 (Error Responses):**
- `401 Unauthorized` - Token 無效或過期

### 17. 建立部門 (Create Department)

**端點 (Endpoint):** `POST /departments`

**需要認證 (Authentication Required):** ✅ Yes (僅限管理員)

**描述 (Description):**  
建立新部門。

**請求參數 (Request Body):**
```typescript
{
  name: string;  // 部門名稱
}
```

**請求範例 (Request Example):**
```json
{
  "name": "研發部"
}
```

**成功回應 (Success Response):** `201 Created`
```typescript
{
  id: string;
  name: string;
  organizationId: string;
  createdAt: string;
  updatedAt: string;
}
```

**錯誤回應 (Error Responses):**
- `400 Bad Request` - 請求參數錯誤或部門名稱重複
- `401 Unauthorized` - Token 無效或過期
- `403 Forbidden` - 沒有權限建立部門

### 18. 更新部門 (Update Department)

**端點 (Endpoint):** `PUT /departments/{id}`

**需要認證 (Authentication Required):** ✅ Yes (僅限管理員)

**描述 (Description):**  
更新部門資訊。

**路徑參數 (Path Parameters):**
- `id` (string) - 部門 ID

**請求參數 (Request Body):**
```typescript
{
  name: string;  // 新的部門名稱
}
```

**請求範例 (Request Example):**
```json
{
  "name": "品質保證部"
}
```

**成功回應 (Success Response):** `200 OK`
```typescript
{
  id: string;
  name: string;
  organizationId: string;
  createdAt: string;
  updatedAt: string;
}
```

**錯誤回應 (Error Responses):**
- `400 Bad Request` - 請求參數錯誤或部門名稱重複
- `401 Unauthorized` - Token 無效或過期
- `403 Forbidden` - 沒有權限更新部門
- `404 Not Found` - 部門不存在

### 19. 刪除部門 (Delete Department)

**端點 (Endpoint):** `DELETE /departments/{id}`

**需要認證 (Authentication Required):** ✅ Yes (僅限管理員)

**描述 (Description):**  
刪除部門。

**路徑參數 (Path Parameters):**
- `id` (string) - 部門 ID

**成功回應 (Success Response):** `200 OK`
```json
{
  "message": "Department deleted successfully"
}
```

**錯誤回應 (Error Responses):**
- `401 Unauthorized` - Token 無效或過期
- `403 Forbidden` - 沒有權限刪除部門
- `404 Not Found` - 部門不存在
- `409 Conflict` - 部門仍有使用者或被專案引用

---

## 問卷回答 API (Answer Management APIs)

### 20. 取得專案答案 (Get Project Answers)

**端點 (Endpoint):** `GET /projects/{projectId}/answers`

**需要認證 (Authentication Required):** ✅ Yes

**描述 (Description):**  
取得特定專案的答案內容。

**路徑參數 (Path Parameters):**
- `projectId` (string) - 專案 ID

**成功回應 (Success Response):** `200 OK`
```typescript
{
  projectId: string;
  answers: Record<string, {
    questionId: string;
    value: any;
  }>;
  lastSavedAt: string;
}
```

**回應範例 (Response Example):**
```json
{
  "projectId": "project-001",
  "answers": {
    "q1": {
      "questionId": "q1",
      "value": "我們公司採用 ISO 9001 品質管理系統"
    },
    "q2": {
      "questionId": "q2",
      "value": 95
    }
  },
  "lastSavedAt": "2025-01-15T10:30:00.000Z"
}
```

**錯誤回應 (Error Responses):**
- `401 Unauthorized` - Token 無效或過期
- `403 Forbidden` - 沒有權限查看此專案的答案
- `404 Not Found` - 專案不存在

### 21. 儲存答案 (Save Answers - Draft)

**端點 (Endpoint):** `POST /projects/{projectId}/answers`

**需要認證 (Authentication Required):** ✅ Yes

**描述 (Description):**  
儲存專案答案（草稿狀態），可以多次儲存。

**路徑參數 (Path Parameters):**
- `projectId` (string) - 專案 ID

**請求參數 (Request Body):**
```typescript
{
  answers: Record<string, any>;  // 答案物件，key 為問題 ID
}
```

**請求範例 (Request Example):**
```json
{
  "answers": {
    "q1": "我們公司採用 ISO 9001 品質管理系統",
    "q2": 95,
    "q3": "2025-01-15",
    "q4": true
  }
}
```

**成功回應 (Success Response):** `200 OK`
```typescript
{
  projectId: string;
  answers: Record<string, Answer>;
  lastSavedAt: string;
}
```

**錯誤回應 (Error Responses):**
- `400 Bad Request` - 答案格式錯誤
- `401 Unauthorized` - Token 無效或過期
- `403 Forbidden` - 沒有權限編輯此專案
- `404 Not Found` - 專案不存在

### 22. 提交答案 (Submit Answers)

**端點 (Endpoint):** `POST /projects/{projectId}/submit`

**需要認證 (Authentication Required):** ✅ Yes

**描述 (Description):**  
提交專案答案，進入審核流程。提交後專案狀態變更為 SUBMITTED 或 REVIEWING。

**路徑參數 (Path Parameters):**
- `projectId` (string) - 專案 ID

**請求參數 (Request Body):**
```typescript
{
  answers: Record<string, any>;  // 完整答案物件
}
```

**請求範例 (Request Example):**
```json
{
  "answers": {
    "q1": "我們公司採用 ISO 9001 品質管理系統",
    "q2": 95,
    "q3": "2025-01-15",
    "q4": true
  }
}
```

**成功回應 (Success Response):** `200 OK`
```typescript
{
  projectId: string;
  status: 'SUBMITTED' | 'REVIEWING';
  currentStage: number;
  submittedAt: string;
}
```

**回應範例 (Response Example):**
```json
{
  "projectId": "project-001",
  "status": "REVIEWING",
  "currentStage": 1,
  "submittedAt": "2025-01-15T14:30:00.000Z"
}
```

**錯誤回應 (Error Responses):**
- `400 Bad Request` - 必填欄位未填寫或答案格式錯誤
- `401 Unauthorized` - Token 無效或過期
- `403 Forbidden` - 沒有權限提交此專案
- `404 Not Found` - 專案不存在
- `409 Conflict` - 專案狀態不允許提交

---

## 審核流程 API (Review Process APIs)

### 23. 取得待審核專案 (Get Pending Reviews)

**端點 (Endpoint):** `GET /review/pending`

**需要認證 (Authentication Required):** ✅ Yes

**描述 (Description):**  
取得目前使用者需要審核的專案列表。依據使用者所屬部門和專案的審核配置來判斷。

**成功回應 (Success Response):** `200 OK`
```typescript
{
  data: Array<{
    id: string;
    name: string;
    year: number;
    type: 'SAQ' | 'CONFLICT';
    status: 'REVIEWING';
    currentStage: number;
    supplierId: string;
    submittedAt: string;
    // ... 其他專案欄位
  }>
}
```

**回應範例 (Response Example):**
```json
{
  "data": [
    {
      "id": "project-001",
      "name": "2025 年度 SAQ 專案",
      "year": 2025,
      "type": "SAQ",
      "status": "REVIEWING",
      "currentStage": 1,
      "supplierId": "supplier-001",
      "submittedAt": "2025-01-15T14:30:00.000Z"
    }
  ]
}
```

**錯誤回應 (Error Responses):**
- `401 Unauthorized` - Token 無效或過期

### 24. 取得審核記錄 (Get Review Logs)

**端點 (Endpoint):** `GET /projects/{projectId}/review-logs`

**需要認證 (Authentication Required):** ✅ Yes

**描述 (Description):**  
取得專案的所有審核記錄。

**路徑參數 (Path Parameters):**
- `projectId` (string) - 專案 ID

**成功回應 (Success Response):** `200 OK`
```typescript
{
  data: Array<{
    id: string;
    projectId: string;
    reviewerId: string;
    reviewerName: string;
    stage: number;
    action: 'APPROVE' | 'RETURN';
    comment: string;
    timestamp: string;
  }>
}
```

**回應範例 (Response Example):**
```json
{
  "data": [
    {
      "id": "review-001",
      "projectId": "project-001",
      "reviewerId": "user-123",
      "reviewerName": "張經理",
      "stage": 1,
      "action": "APPROVE",
      "comment": "資料完整，核准通過",
      "timestamp": "2025-01-16T09:00:00.000Z"
    },
    {
      "id": "review-002",
      "projectId": "project-001",
      "reviewerId": "user-456",
      "reviewerName": "李主任",
      "stage": 2,
      "action": "RETURN",
      "comment": "Q5 的答案需要補充更多細節",
      "timestamp": "2025-01-17T10:30:00.000Z"
    }
  ]
}
```

**錯誤回應 (Error Responses):**
- `401 Unauthorized` - Token 無效或過期
- `403 Forbidden` - 沒有權限查看此專案的審核記錄
- `404 Not Found` - 專案不存在

### 25. 核准專案 (Approve Project)

**端點 (Endpoint):** `POST /projects/{projectId}/approve`

**需要認證 (Authentication Required):** ✅ Yes

**描述 (Description):**  
核准專案，推進到下一個審核階段或完成審核。

**路徑參數 (Path Parameters):**
- `projectId` (string) - 專案 ID

**請求參數 (Request Body):**
```typescript
{
  comment: string;  // 審核意見
}
```

**請求範例 (Request Example):**
```json
{
  "comment": "資料完整，核准通過"
}
```

**成功回應 (Success Response):** `200 OK`
```typescript
{
  projectId: string;
  status: 'REVIEWING' | 'APPROVED';
  currentStage: number;
  reviewLog: {
    id: string;
    projectId: string;
    reviewerId: string;
    reviewerName: string;
    stage: number;
    action: 'APPROVE';
    comment: string;
    timestamp: string;
  }
}
```

**回應範例 (Response Example):**
```json
{
  "projectId": "project-001",
  "status": "APPROVED",
  "currentStage": 2,
  "reviewLog": {
    "id": "review-003",
    "projectId": "project-001",
    "reviewerId": "user-789",
    "reviewerName": "陳總經理",
    "stage": 2,
    "action": "APPROVE",
    "comment": "最終核准",
    "timestamp": "2025-01-18T11:00:00.000Z"
  }
}
```

**錯誤回應 (Error Responses):**
- `400 Bad Request` - 請求參數錯誤
- `401 Unauthorized` - Token 無效或過期
- `403 Forbidden` - 沒有權限審核此專案（不是當前階段的審核者）
- `404 Not Found` - 專案不存在
- `409 Conflict` - 專案狀態不允許審核

### 26. 退回專案 (Return Project)

**端點 (Endpoint):** `POST /projects/{projectId}/return`

**需要認證 (Authentication Required):** ✅ Yes

**描述 (Description):**  
退回專案給供應商重新填寫。

**路徑參數 (Path Parameters):**
- `projectId` (string) - 專案 ID

**請求參數 (Request Body):**
```typescript
{
  comment: string;  // 退回原因（必填）
}
```

**請求範例 (Request Example):**
```json
{
  "comment": "Q5 的答案需要補充更多細節，請重新填寫"
}
```

**成功回應 (Success Response):** `200 OK`
```typescript
{
  projectId: string;
  status: 'RETURNED';
  currentStage: number;
  reviewLog: {
    id: string;
    projectId: string;
    reviewerId: string;
    reviewerName: string;
    stage: number;
    action: 'RETURN';
    comment: string;
    timestamp: string;
  }
}
```

**回應範例 (Response Example):**
```json
{
  "projectId": "project-001",
  "status": "RETURNED",
  "currentStage": 1,
  "reviewLog": {
    "id": "review-004",
    "projectId": "project-001",
    "reviewerId": "user-456",
    "reviewerName": "李主任",
    "stage": 1,
    "action": "RETURN",
    "comment": "Q5 的答案需要補充更多細節，請重新填寫",
    "timestamp": "2025-01-17T10:30:00.000Z"
  }
}
```

**錯誤回應 (Error Responses):**
- `400 Bad Request` - 請求參數錯誤（comment 為必填）
- `401 Unauthorized` - Token 無效或過期
- `403 Forbidden` - 沒有權限審核此專案
- `404 Not Found` - 專案不存在
- `409 Conflict` - 專案狀態不允許審核

---

## 錯誤處理 (Error Handling)

### 錯誤回應格式

所有 API 錯誤都遵循統一的格式：

```typescript
{
  error: string;        // 錯誤類型
  message: string;      // 詳細錯誤訊息
  statusCode: number;   // HTTP 狀態碼
  details?: any;        // 額外的錯誤詳情（可選）
}
```

### 常見錯誤類型

#### 1. 驗證錯誤 (Validation Error)
```json
{
  "error": "ValidationError",
  "message": "Invalid input data",
  "statusCode": 400,
  "details": {
    "field": "email",
    "reason": "Invalid email format"
  }
}
```

#### 2. 認證錯誤 (Authentication Error)
```json
{
  "error": "AuthenticationError",
  "message": "Invalid credentials",
  "statusCode": 401
}
```

#### 3. Token 過期
```json
{
  "error": "TokenExpiredError",
  "message": "Token has expired",
  "statusCode": 401
}
```

#### 4. 權限不足 (Authorization Error)
```json
{
  "error": "AuthorizationError",
  "message": "Insufficient permissions",
  "statusCode": 403
}
```

#### 5. 資源不存在 (Not Found)
```json
{
  "error": "NotFoundError",
  "message": "Resource not found",
  "statusCode": 404,
  "details": {
    "resource": "Project",
    "id": "project-001"
  }
}
```

#### 6. 資源衝突 (Conflict)
```json
{
  "error": "ConflictError",
  "message": "Resource conflict",
  "statusCode": 409,
  "details": {
    "reason": "Project is already submitted"
  }
}
```

#### 7. 伺服器錯誤 (Internal Server Error)
```json
{
  "error": "InternalServerError",
  "message": "An unexpected error occurred",
  "statusCode": 500
}
```

### 前端錯誤處理建議

前端應用程式應該：

1. **統一錯誤處理**: 在 `useApi` composable 中統一處理所有 API 錯誤
2. **Token 過期處理**: 偵測到 401 且錯誤類型為 `TokenExpiredError` 時，應自動登出並導向登入頁面
3. **使用者友善訊息**: 將技術性錯誤訊息轉換為使用者可理解的訊息
4. **錯誤日誌**: 記錄所有 API 錯誤以便除錯
5. **重試機制**: 對於網路錯誤或 500 錯誤，可以考慮自動重試

### 錯誤處理範例

```typescript
// 在 useApi composable 中的錯誤處理
const handleApiError = (error: any) => {
  if (error.statusCode === 401 && error.error === 'TokenExpiredError') {
    // Token 過期，登出使用者
    authStore.logout()
    navigateTo('/login')
    showNotification('登入已過期，請重新登入')
  } else if (error.statusCode === 403) {
    showNotification('您沒有權限執行此操作')
  } else if (error.statusCode === 404) {
    showNotification('找不到指定的資源')
  } else if (error.statusCode === 409) {
    showNotification(error.message || '操作衝突，請稍後再試')
  } else if (error.statusCode >= 500) {
    showNotification('伺服器錯誤，請稍後再試')
  } else {
    showNotification(error.message || '發生錯誤，請稍後再試')
  }
}
```

---

## 附錄 (Appendix)

### A. 資料類型定義 (Data Type Definitions)

所有資料類型的完整定義請參考：
- Frontend: `/frontend/app/types/index.ts`
- 此文件: [資料模型文件](./data-model.md)

### B. API 測試建議

1. **使用 Postman 或類似工具**: 建立 API Collection 進行測試
2. **環境變數**: 設定 Development 和 Production 環境
3. **測試順序**: 
   - 先測試 Authentication
   - 取得 Token 後測試其他端點
   - 測試各種錯誤情境

### C. 版本管理

- API 版本號遵循 Semantic Versioning (SemVer)
- 破壞性變更應該提升主版本號
- 新增功能提升次版本號
- Bug 修復提升修訂版本號

### D. 效能考量

1. **分頁**: 對於大量資料的列表 API，應實作分頁機制
2. **快取**: 考慮對不常變動的資料（如部門列表）實作快取
3. **請求限制**: 實作 Rate Limiting 防止濫用
4. **壓縮**: 啟用 gzip 壓縮減少傳輸大小

---

## 變更歷史 (Change History)

| 版本 | 日期 | 變更內容 | 作者 |
|------|------|---------|------|
| 1.0.0 | 2025-12-02 | 初始版本 - 完整的 API 需求文件 | System |

---

**文件結束 (End of Document)**
