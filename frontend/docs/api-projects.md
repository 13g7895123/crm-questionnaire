# 專案管理 API

## 目錄
- [4.1 取得專案列表](#41-取得專案列表)
- [4.2 取得專案詳情](#42-取得專案詳情)
- [4.3 建立專案](#43-建立專案)
- [4.4 更新專案](#44-更新專案)
- [4.5 刪除專案](#45-刪除專案)
- [4.6 取得專案統計](#46-取得專案統計)

---

## 4.1 取得專案列表

**Endpoint**: `GET /api/v1/projects`  
**權限**: 需要認證  
**用途**: 取得專案列表 (HOST 看到所有專案，SUPPLIER 僅看到被指派的專案)

### Request Headers

```
Authorization: Bearer <accessToken>
```

### Query Parameters

| 參數 | 類型 | 必填 | 說明 |
|------|------|------|------|
| page | integer | ✗ | 頁碼 (預設: 1) |
| limit | integer | ✗ | 每頁筆數 (預設: 20，最大: 100) |
| type | string | ✗ | 專案類型 (SAQ, CONFLICT) |
| status | string | ✗ | 專案狀態 (DRAFT, IN_PROGRESS, SUBMITTED, REVIEWING, APPROVED, RETURNED) |
| year | integer | ✗ | 年份篩選 |
| search | string | ✗ | 搜尋關鍵字 (搜尋專案名稱) |
| sortBy | string | ✗ | 排序欄位 (createdAt, updatedAt, name, year) |
| order | string | ✗ | 排序方向 (asc, desc) |

### Response (200 OK)

**HOST 使用者回應:**
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
  ],
  "pagination": {
    "page": 1,
    "limit": 20,
    "total": 45,
    "totalPages": 3
  }
}
```

**SUPPLIER 使用者回應:** (僅顯示被指派的專案)
```json
{
  "success": true,
  "data": [
    {
      "id": 101,
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
  ],
  "pagination": {
    "page": 1,
    "limit": 20,
    "total": 3,
    "totalPages": 1
  }
}
```

**注意事項:**
- HOST 回應包含 `supplierCount` 與 `approvedCount`
- SUPPLIER 回應的 `id` 為 `project_supplier_id`，`projectId` 為原始專案 ID

---

## 4.2 取得專案詳情

**Endpoint**: `GET /api/v1/projects/{projectId}`  
**權限**: 需要認證  
**用途**: 取得特定專案的詳細資訊

### Request Headers

```
Authorization: Bearer <accessToken>
```

### Path Parameters

| 參數 | 類型 | 說明 |
|------|------|------|
| projectId | string | 專案 ID |

### Response (200 OK)

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
        "id": 101,
        "supplierId": 2,
        "supplierName": "供應商 A 公司",
        "status": "IN_PROGRESS",
        "currentStage": 0,
        "submittedAt": null
      },
      {
        "id": 102,
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

**欄位說明:**

| 欄位 | 類型 | 說明 |
|------|------|------|
| suppliers | array | 關聯的供應商列表 |
| suppliers[].id | integer | ProjectSupplier ID |
| suppliers[].status | string | 該供應商的填寫/審核狀態 |

### Error Responses

**404 Not Found - 專案不存在**
```json
{
  "success": false,
  "error": {
    "code": "RESOURCE_NOT_FOUND",
    "message": "找不到指定的專案"
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

**403 Forbidden - SUPPLIER 存取未被指派的專案**
```json
{
  "success": false,
  "error": {
    "code": "SUPPLIER_NOT_ASSIGNED",
    "message": "您無權存取此專案"
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

---

## 4.3 建立專案

**Endpoint**: `POST /api/v1/projects`  
**權限**: 需要認證 (HOST)  
**用途**: 製造商建立新專案並指派給供應商

### Request Headers

```
Authorization: Bearer <accessToken>
```

### Request Body

```json
{
  "name": "2025 SAQ 供應商評估",
  "year": 2025,
  "type": "SAQ",
  "templateId": "tmpl_def456",
  "templateVersion": "1.2.0",
  "supplierIds": ["org_supplier789", "org_supplier790"],
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

**欄位說明:**

| 欄位 | 類型 | 必填 | 說明 |
|------|------|------|------|
| name | string | ✓ | 專案名稱 (最長 200 字元) |
| year | integer | ✓ | 年份 (1900-2100) |
| type | string | ✓ | 專案類型 (SAQ, CONFLICT) |
| templateId | string | ✓ | 範本 ID |
| templateVersion | string | ✓ | 範本版本 (如: 1.2.0) |
| supplierIds | array | ✓ | 供應商組織 ID 列表 |
| reviewConfig | array | ✓ | 審核流程設定 (1-5 個階段) |
| reviewConfig[].stageOrder | integer | ✓ | 審核階段順序 (從 1 開始) |
| reviewConfig[].departmentId | string | ✓ | 負責部門 ID |
| reviewConfig[].approverId | string | ✗ | 特定審核者 ID (可選) |

**驗證規則:**
- `reviewConfig` 必須包含 1-5 個審核階段
- `stageOrder` 必須連續且從 1 開始
- 同一專案的 `departmentId` 不可重複
- `templateVersion` 必須存在於指定的範本中
- `supplierIds` 必須包含至少一個 SUPPLIER 類型的組織 ID

### Response (201 Created)

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
        "id": 101,
        "supplierId": 2,
        "supplierName": "供應商 A 公司",
        "status": "IN_PROGRESS",
        "currentStage": 0
      },
      {
        "id": 102,
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

### Error Responses

**403 Forbidden - SUPPLIER 嘗試建立專案**
```json
{
  "success": false,
  "error": {
    "code": "AUTH_INSUFFICIENT_PERMISSION",
    "message": "供應商無權建立專案"
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

**422 Unprocessable Entity - 審核流程設定錯誤**
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

**404 Not Found - 範本或供應商不存在**
```json
{
  "success": false,
  "error": {
    "code": "RESOURCE_NOT_FOUND",
    "message": "找不到指定的範本或供應商"
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

---

## 4.4 更新專案

**Endpoint**: `PUT /api/v1/projects/{projectId}`  
**權限**: 需要認證 (HOST)  
**用途**: 製造商更新專案資訊

### Request Headers

```
Authorization: Bearer <accessToken>
```

### Path Parameters

| 參數 | 類型 | 說明 |
|------|------|------|
| projectId | string | 專案 ID |

### Request Body

```json
{
  "name": "2025 SAQ 供應商評估 (更新)",
  "year": 2025,
  "supplierIds": [2, 3, 5],
  "reviewConfig": [
    {
      "stageOrder": 1,
      "departmentId": 1
    }
  ]
}
```

**可更新欄位:**
- name
- year
- supplierIds (可新增供應商，已存在的供應商不受影響)
- reviewConfig (僅在所有供應商皆未提交時可修改)

**不可更新欄位:**
- type
- templateId
- templateVersion

**注意事項:**
- 若有任何供應商已提交 (SUBMITTED, REVIEWING, APPROVED)，則無法修改審核流程
- 若有任何供應商已核准 (APPROVED)，則無法修改專案基本資訊
- `supplierIds` 僅支援新增，移除供應商需使用其他方式 (目前未開放)

### Response (200 OK)

```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "2025 SAQ 供應商評估 (更新)",
    "year": 2025,
    "type": "SAQ",
    "templateId": 1,
    "templateVersion": "1.2.0",
    "suppliers": [...],
    "reviewConfig": [...],
    "updatedAt": "2025-12-02T06:08:38.435Z"
  }
}
```

### Error Responses

**409 Conflict - 專案已提交無法修改**
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

---

## 4.5 刪除專案

**Endpoint**: `DELETE /api/v1/projects/{projectId}`  
**權限**: 需要認證 (HOST)  
**用途**: 製造商刪除專案

### Request Headers

```
Authorization: Bearer <accessToken>
```

### Path Parameters

| 參數 | 類型 | 說明 |
|------|------|------|
| projectId | string | 專案 ID |

**注意事項:**
- 僅狀態為 DRAFT 的專案可刪除
- 刪除專案會一併刪除相關的答案與審核紀錄

### Response (204 No Content)

成功刪除，無回應內容

### Error Responses

**404 Not Found - 專案不存在**
```json
{
  "success": false,
  "error": {
    "code": "RESOURCE_NOT_FOUND",
    "message": "找不到指定的專案"
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

**409 Conflict - 專案無法刪除**
```json
{
  "success": false,
  "error": {
    "code": "RESOURCE_CONFLICT",
    "message": "僅草稿狀態的專案可刪除"
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

---

## 4.6 取得專案統計

**Endpoint**: `GET /api/v1/projects/stats`  
**權限**: 需要認證 (HOST)  
**用途**: 取得專案統計資訊 (儀表板顯示用)

### Request Headers

```
Authorization: Bearer <accessToken>
```

### Query Parameters

| 參數 | 類型 | 必填 | 說明 |
|------|------|------|------|
| type | string | ✗ | 專案類型 (SAQ, CONFLICT) |
| year | integer | ✗ | 年份篩選 |

### Response (200 OK)

```json
{
  "success": true,
  "data": {
    "total": 150,
    "byStatus": {
      "DRAFT": 5,
      "IN_PROGRESS": 45,
      "SUBMITTED": 10,
      "REVIEWING": 30,
      "APPROVED": 55,
      "RETURNED": 5
    },
    "byType": {
      "SAQ": 100,
      "CONFLICT": 50
    },
    "byYear": {
      "2023": 45,
      "2024": 60,
      "2025": 45
    }
  }
}
```

---

## 專案狀態流程圖

```
DRAFT (草稿)
    ↓ (供應商開始填寫)
IN_PROGRESS (進行中)
    ↓ (供應商提交)
SUBMITTED (已提交)
    ↓ (進入第一階段審核)
REVIEWING (審核中)
    ├─ APPROVE → (有下一階段) → REVIEWING (下一階段)
    ├─ APPROVE → (無下一階段) → APPROVED (已核准)
    └─ RETURN → RETURNED (已退回) → IN_PROGRESS (重新填寫)
```

## 使用情境說明

### 情境 1: 製造商建立專案並指派供應商
1. 製造商登入並進入「SAQ 管理」
2. 點擊「新增專案」
3. 填寫專案資訊:
   - 名稱: "2025 Q1 供應商評估"
   - 年份: 2025
   - 選擇範本: "SAQ 標準範本 v1.2.0"
   - 選擇供應商: "供應商 A 公司", "供應商 B 公司" (可多選)
4. 設定審核流程:
   - 第一階段: 品質管理部
   - 第二階段: 採購部
5. 呼叫 `POST /api/v1/projects`
6. 專案建立成功，系統為每個供應商建立獨立的填寫記錄
7. 供應商可開始填寫

### 情境 2: 供應商查看被指派的專案
1. 供應商登入並進入「我的專案」
2. 呼叫 `GET /api/v1/projects?type=SAQ`
3. 僅顯示被指派的專案列表 (顯示 `project_supplier_id`)
4. 點擊專案進入填寫頁面 (使用 `project_supplier_id` 呼叫相關 API)
