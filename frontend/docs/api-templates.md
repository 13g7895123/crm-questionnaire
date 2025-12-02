# 範本管理 API

## 目錄
- [5.1 取得範本列表](#51-取得範本列表)
- [5.2 取得範本詳情](#52-取得範本詳情)
- [5.3 建立範本](#53-建立範本)
- [5.4 更新範本](#54-更新範本)
- [5.5 刪除範本](#55-刪除範本)
- [5.6 建立範本新版本](#56-建立範本新版本)
- [5.7 取得範本特定版本](#57-取得範本特定版本)

---

## 5.1 取得範本列表

**Endpoint**: `GET /api/v1/templates`  
**權限**: 需要認證 (HOST)  
**用途**: 取得範本列表

### Request Headers

```
Authorization: Bearer <accessToken>
```

### Query Parameters

| 參數 | 類型 | 必填 | 說明 |
|------|------|------|------|
| page | integer | ✗ | 頁碼 (預設: 1) |
| limit | integer | ✗ | 每頁筆數 (預設: 20，最大: 100) |
| type | string | ✗ | 範本類型 (SAQ, CONFLICT) |
| search | string | ✗ | 搜尋關鍵字 (搜尋範本名稱) |

### Response (200 OK)

```json
{
  "success": true,
  "data": [
    {
      "id": "tmpl_abc123",
      "name": "SAQ 標準範本",
      "type": "SAQ",
      "latestVersion": "1.2.0",
      "versions": [
        {
          "version": "1.0.0",
          "createdAt": "2025-01-01T00:00:00.000Z"
        },
        {
          "version": "1.1.0",
          "createdAt": "2025-03-15T00:00:00.000Z"
        },
        {
          "version": "1.2.0",
          "createdAt": "2025-11-01T00:00:00.000Z"
        }
      ],
      "createdAt": "2025-01-01T00:00:00.000Z",
      "updatedAt": "2025-11-01T00:00:00.000Z"
    },
    {
      "id": "tmpl_def456",
      "name": "衝突資產調查範本",
      "type": "CONFLICT",
      "latestVersion": "2.0.0",
      "versions": [
        {
          "version": "1.0.0",
          "createdAt": "2025-02-01T00:00:00.000Z"
        },
        {
          "version": "2.0.0",
          "createdAt": "2025-10-01T00:00:00.000Z"
        }
      ],
      "createdAt": "2025-02-01T00:00:00.000Z",
      "updatedAt": "2025-10-01T00:00:00.000Z"
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 20,
    "total": 8,
    "totalPages": 1
  }
}
```

---

## 5.2 取得範本詳情

**Endpoint**: `GET /api/v1/templates/{templateId}`  
**權限**: 需要認證  
**用途**: 取得範本的詳細資訊 (包含最新版本的題目)

### Request Headers

```
Authorization: Bearer <accessToken>
```

### Path Parameters

| 參數 | 類型 | 說明 |
|------|------|------|
| templateId | string | 範本 ID |

### Response (200 OK)

```json
{
  "success": true,
  "data": {
    "id": "tmpl_abc123",
    "name": "SAQ 標準範本",
    "type": "SAQ",
    "latestVersion": "1.2.0",
    "versions": [
      {
        "version": "1.0.0",
        "createdAt": "2025-01-01T00:00:00.000Z"
      },
      {
        "version": "1.1.0",
        "createdAt": "2025-03-15T00:00:00.000Z"
      },
      {
        "version": "1.2.0",
        "createdAt": "2025-11-01T00:00:00.000Z"
      }
    ],
    "currentVersionQuestions": [
      {
        "id": "q_001",
        "text": "貴公司是否具有 ISO 9001 認證？",
        "type": "BOOLEAN",
        "required": true,
        "options": null,
        "config": null
      },
      {
        "id": "q_002",
        "text": "請選擇貴公司的主要產業類別",
        "type": "SINGLE_CHOICE",
        "required": true,
        "options": [
          "電子製造",
          "化工製造",
          "機械製造",
          "其他"
        ],
        "config": null
      },
      {
        "id": "q_003",
        "text": "請上傳貴公司的營業執照",
        "type": "FILE",
        "required": true,
        "options": null,
        "config": {
          "maxFileSize": 5242880,
          "allowedFileTypes": ["pdf", "jpg", "png"]
        }
      },
      {
        "id": "q_004",
        "text": "請評分貴公司對品質管理的重視程度",
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
    "createdAt": "2025-01-01T00:00:00.000Z",
    "updatedAt": "2025-11-01T00:00:00.000Z"
  }
}
```

### Error Responses

**404 Not Found - 範本不存在**
```json
{
  "success": false,
  "error": {
    "code": "RESOURCE_NOT_FOUND",
    "message": "找不到指定的範本"
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

---

## 5.3 建立範本

**Endpoint**: `POST /api/v1/templates`  
**權限**: 需要認證 (HOST)  
**用途**: 建立新範本 (自動建立版本 1.0.0)

### Request Headers

```
Authorization: Bearer <accessToken>
```

### Request Body

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
    },
    {
      "text": "請簡述貴公司的品質管理流程",
      "type": "TEXT",
      "required": true,
      "config": {
        "maxLength": 500
      }
    }
  ]
}
```

**欄位說明:**

| 欄位 | 類型 | 必填 | 說明 |
|------|------|------|------|
| name | string | ✓ | 範本名稱 (最長 200 字元) |
| type | string | ✓ | 範本類型 (SAQ, CONFLICT) |
| questions | array | ✓ | 題目陣列 (至少 1 個題目) |
| questions[].text | string | ✓ | 題目文字 (最長 500 字元) |
| questions[].type | string | ✓ | 題目類型 (TEXT, NUMBER, DATE, BOOLEAN, SINGLE_CHOICE, MULTI_CHOICE, FILE, RATING) |
| questions[].required | boolean | ✓ | 是否必填 |
| questions[].options | array | ✗ | 選項陣列 (SINGLE_CHOICE, MULTI_CHOICE 必填) |
| questions[].config | object | ✗ | 額外設定 (依題型而定) |

### Response (201 Created)

```json
{
  "success": true,
  "data": {
    "id": "tmpl_new789",
    "name": "新 SAQ 範本",
    "type": "SAQ",
    "latestVersion": "1.0.0",
    "versions": [
      {
        "version": "1.0.0",
        "createdAt": "2025-12-02T06:08:38.435Z"
      }
    ],
    "currentVersionQuestions": [
      {
        "id": "q_new001",
        "text": "貴公司是否具有 ISO 9001 認證？",
        "type": "BOOLEAN",
        "required": true,
        "options": null,
        "config": null
      }
    ],
    "createdAt": "2025-12-02T06:08:38.435Z",
    "updatedAt": "2025-12-02T06:08:38.435Z"
  }
}
```

### Error Responses

**422 Unprocessable Entity - 驗證錯誤**
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "資料驗證失敗",
    "details": {
      "questions": "至少需要 1 個題目",
      "questions[1].options": "SINGLE_CHOICE 題型必須提供選項"
    }
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

---

## 5.4 更新範本

**Endpoint**: `PUT /api/v1/templates/{templateId}`  
**權限**: 需要認證 (HOST)  
**用途**: 更新範本基本資訊 (僅名稱)

### Request Headers

```
Authorization: Bearer <accessToken>
```

### Path Parameters

| 參數 | 類型 | 說明 |
|------|------|------|
| templateId | string | 範本 ID |

### Request Body

```json
{
  "name": "SAQ 標準範本 (2025 年版)"
}
```

**可更新欄位:**
- name

**不可更新欄位:**
- type
- questions (需使用「建立新版本」API)

### Response (200 OK)

```json
{
  "success": true,
  "data": {
    "id": "tmpl_abc123",
    "name": "SAQ 標準範本 (2025 年版)",
    "type": "SAQ",
    "latestVersion": "1.2.0",
    "updatedAt": "2025-12-02T06:08:38.435Z"
  }
}
```

---

## 5.5 刪除範本

**Endpoint**: `DELETE /api/v1/templates/{templateId}`  
**權限**: 需要認證 (HOST)  
**用途**: 刪除範本

### Request Headers

```
Authorization: Bearer <accessToken>
```

### Path Parameters

| 參數 | 類型 | 說明 |
|------|------|------|
| templateId | string | 範本 ID |

**注意事項:**
- 僅未被任何專案使用的範本可刪除
- 刪除範本會一併刪除所有版本

### Response (204 No Content)

成功刪除，無回應內容

### Error Responses

**409 Conflict - 範本正在使用中**
```json
{
  "success": false,
  "error": {
    "code": "TEMPLATE_IN_USE",
    "message": "此範本正在被專案使用，無法刪除",
    "details": {
      "projectCount": 5
    }
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

---

## 5.6 建立範本新版本

**Endpoint**: `POST /api/v1/templates/{templateId}/versions`  
**權限**: 需要認證 (HOST)  
**用途**: 為現有範本建立新版本 (用於修改題目)

### Request Headers

```
Authorization: Bearer <accessToken>
```

### Path Parameters

| 參數 | 類型 | 說明 |
|------|------|------|
| templateId | string | 範本 ID |

### Request Body

```json
{
  "version": "1.3.0",
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
    },
    {
      "text": "請簡述貴公司的環境管理政策",
      "type": "TEXT",
      "required": true,
      "config": {
        "maxLength": 1000
      }
    }
  ]
}
```

**欄位說明:**

| 欄位 | 類型 | 必填 | 說明 |
|------|------|------|------|
| version | string | ✓ | 版本號 (Semantic Versioning, 如: 1.3.0) |
| questions | array | ✓ | 新版本的題目陣列 |

**版本號規則:**
- 格式: `major.minor.patch`
- 新版本號必須大於目前最新版本
- 建議規則:
  - major: 重大變更 (不向下相容)
  - minor: 新增功能 (向下相容)
  - patch: 修正錯誤 (向下相容)

### Response (201 Created)

```json
{
  "success": true,
  "data": {
    "id": "tmpl_abc123",
    "name": "SAQ 標準範本",
    "type": "SAQ",
    "latestVersion": "1.3.0",
    "versions": [
      {
        "version": "1.0.0",
        "createdAt": "2025-01-01T00:00:00.000Z"
      },
      {
        "version": "1.1.0",
        "createdAt": "2025-03-15T00:00:00.000Z"
      },
      {
        "version": "1.2.0",
        "createdAt": "2025-11-01T00:00:00.000Z"
      },
      {
        "version": "1.3.0",
        "createdAt": "2025-12-02T06:08:38.435Z"
      }
    ],
    "currentVersionQuestions": [
      {
        "id": "q_new001",
        "text": "貴公司是否具有 ISO 9001 認證？",
        "type": "BOOLEAN",
        "required": true
      },
      {
        "id": "q_new002",
        "text": "請選擇貴公司的主要產業類別",
        "type": "SINGLE_CHOICE",
        "required": true,
        "options": ["電子製造", "化工製造", "機械製造", "其他"]
      },
      {
        "id": "q_new003",
        "text": "請簡述貴公司的環境管理政策",
        "type": "TEXT",
        "required": true,
        "config": {
          "maxLength": 1000
        }
      }
    ],
    "updatedAt": "2025-12-02T06:08:38.435Z"
  }
}
```

### Error Responses

**422 Unprocessable Entity - 版本號錯誤**
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "版本號必須大於目前最新版本 1.2.0"
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

---

## 5.7 取得範本特定版本

**Endpoint**: `GET /api/v1/templates/{templateId}/versions/{version}`  
**權限**: 需要認證  
**用途**: 取得範本的特定版本 (包含該版本的題目)

### Request Headers

```
Authorization: Bearer <accessToken>
```

### Path Parameters

| 參數 | 類型 | 說明 |
|------|------|------|
| templateId | string | 範本 ID |
| version | string | 版本號 (如: 1.0.0) |

### Response (200 OK)

```json
{
  "success": true,
  "data": {
    "templateId": "tmpl_abc123",
    "templateName": "SAQ 標準範本",
    "templateType": "SAQ",
    "version": "1.0.0",
    "questions": [
      {
        "id": "q_old001",
        "text": "貴公司是否具有 ISO 9001 認證？",
        "type": "BOOLEAN",
        "required": true
      },
      {
        "id": "q_old002",
        "text": "請選擇貴公司的主要產業類別",
        "type": "SINGLE_CHOICE",
        "required": true,
        "options": ["電子製造", "化工製造", "其他"]
      }
    ],
    "createdAt": "2025-01-01T00:00:00.000Z"
  }
}
```

### Error Responses

**404 Not Found - 版本不存在**
```json
{
  "success": false,
  "error": {
    "code": "RESOURCE_NOT_FOUND",
    "message": "找不到指定的範本版本"
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

---

## 題目類型與設定 (QuestionConfig)

### TEXT (簡答題)
```json
{
  "type": "TEXT",
  "config": {
    "maxLength": 500
  }
}
```

### NUMBER (數字題)
```json
{
  "type": "NUMBER",
  "config": {
    "numberMin": 0,
    "numberMax": 100
  }
}
```

### DATE (日期題)
```json
{
  "type": "DATE",
  "config": null
}
```

### BOOLEAN (布林題)
```json
{
  "type": "BOOLEAN",
  "config": null
}
```

### SINGLE_CHOICE (單選題)
```json
{
  "type": "SINGLE_CHOICE",
  "options": ["選項 A", "選項 B", "選項 C"],
  "config": null
}
```

### MULTI_CHOICE (複選題)
```json
{
  "type": "MULTI_CHOICE",
  "options": ["選項 A", "選項 B", "選項 C"],
  "config": null
}
```

### FILE (檔案上傳題)
```json
{
  "type": "FILE",
  "config": {
    "maxFileSize": 5242880,
    "allowedFileTypes": ["pdf", "jpg", "png", "docx"]
  }
}
```

### RATING (評分題)
```json
{
  "type": "RATING",
  "config": {
    "ratingMin": 1,
    "ratingMax": 5,
    "ratingStep": 1
  }
}
```

---

## 使用情境說明

### 情境 1: 建立新範本
1. 製造商進入「範本管理」
2. 點擊「新增範本」
3. 選擇類型 (SAQ 或衝突資產)
4. 輸入範本名稱
5. 新增題目並設定題型、必填、選項等
6. 呼叫 `POST /api/v1/templates`
7. 系統自動建立版本 1.0.0

### 情境 2: 修改範本題目 (建立新版本)
1. 製造商進入「範本管理」
2. 選擇要修改的範本
3. 檢視目前版本 (如: 1.2.0)
4. 點擊「建立新版本」
5. 修改題目 (新增、刪除、編輯)
6. 輸入新版本號 (如: 1.3.0)
7. 呼叫 `POST /api/v1/templates/{templateId}/versions`
8. 新版本建立成功，成為最新版本

### 情境 3: 建立專案時選擇範本版本
1. 製造商建立新專案
2. 呼叫 `GET /api/v1/templates` 取得範本列表
3. 選擇範本並檢視可用版本
4. 選擇特定版本 (如: 1.2.0) 或最新版本 (1.3.0)
5. 專案建立後鎖定該版本，即使範本推出新版本也不影響已建立的專案
