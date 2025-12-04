# 問卷填寫 API

## 目錄
- [6.1 取得專案答案](#61-取得專案答案)
- [6.2 暫存答案](#62-暫存答案)
- [6.3 提交專案](#63-提交專案)
- [6.4 上傳檔案](#64-上傳檔案)

---

## 6.1 取得專案答案

**Endpoint**: `GET /api/v1/project-suppliers/{projectSupplierId}/answers`  
**權限**: 需要認證 (被指派的 SUPPLIER 或專案建立者 HOST)  
**用途**: 取得專案的所有答案 (供供應商繼續填寫或檢視)

### Request Headers

```
Authorization: Bearer <accessToken>
```

### Path Parameters

| 參數 | 類型 | 說明 |
|------|------|------|
| projectSupplierId | integer | 專案供應商 ID |

### Response (200 OK)

```json
{
  "success": true,
  "data": {
    "projectSupplierId": 101,
    "answers": {
      "q_001": {
        "questionId": "q_001",
        "value": true
      },
      "q_002": {
        "questionId": "q_002",
        "value": "電子製造"
      },
      "q_003": {
        "questionId": "q_003",
        "value": "https://storage.example.com/files/abc123.pdf"
      },
      "q_004": {
        "questionId": "q_004",
        "value": 5
      },
      "q_005": {
        "questionId": "q_005",
        "value": ["選項 A", "選項 C"]
      }
    },
    "lastSavedAt": "2025-12-01T15:30:00.000Z"
  }
}
```

**欄位說明:**

| 欄位 | 類型 | 說明 |
|------|------|------|
| answers | object | 答案物件，key 為 questionId |
| answers[questionId].value | any | 答案值 (依題型而定) |
| lastSavedAt | string | 最後儲存時間 |

**答案值類型:**
- TEXT: `string`
- NUMBER: `number`
- DATE: `string` (ISO 8601 格式)
- BOOLEAN: `boolean`
- SINGLE_CHOICE: `string`
- MULTI_CHOICE: `array<string>`
- FILE: `string` (檔案 URL)
- RATING: `number`

### Error Responses

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

---

## 6.2 暫存答案

**Endpoint**: `PUT /api/v1/project-suppliers/{projectSupplierId}/answers`  
**權限**: 需要認證 (被指派的 SUPPLIER)  
**用途**: 暫存專案答案 (允許部分填寫)

### Request Headers

```
Authorization: Bearer <accessToken>
```

### Path Parameters

| 參數 | 類型 | 說明 |
|------|------|------|
| projectSupplierId | integer | 專案供應商 ID |

### Request Body

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
    },
    "q_003": {
      "questionId": "q_003",
      "value": "https://storage.example.com/files/abc123.pdf"
    }
  }
}
```

**欄位說明:**

| 欄位 | 類型 | 必填 | 說明 |
|------|------|------|------|
| answers | object | ✓ | 答案物件 (可部分填寫) |
| answers[questionId] | object | ✓ | 單一題目的答案 |
| answers[questionId].questionId | string | ✓ | 題目 ID |
| answers[questionId].value | any | ✓ | 答案值 |

**注意事項:**
- 暫存時不驗證必填欄位
- 可多次呼叫，後續呼叫會覆蓋先前的答案
- 專案狀態必須為 `IN_PROGRESS` 或 `RETURNED`

### Response (200 OK)

```json
{
  "success": true,
  "data": {
    "projectSupplierId": 101,
    "savedCount": 3,
    "lastSavedAt": "2025-12-02T06:08:38.435Z"
  }
}
```

### Error Responses

**403 Forbidden - SUPPLIER 未被指派**
```json
{
  "success": false,
  "error": {
    "code": "SUPPLIER_NOT_ASSIGNED",
    "message": "您無權編輯此專案"
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

**409 Conflict - 專案狀態不允許編輯**
```json
{
  "success": false,
  "error": {
    "code": "PROJECT_ALREADY_SUBMITTED",
    "message": "專案已提交，無法修改答案"
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

**422 Unprocessable Entity - 答案格式錯誤**
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "答案格式錯誤",
    "details": {
      "q_001": "BOOLEAN 題型的答案必須為 true 或 false",
      "q_002": "選項不在允許範圍內"
    }
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

---

## 6.3 提交專案

**Endpoint**: `POST /api/v1/project-suppliers/{projectSupplierId}/submit`  
**權限**: 需要認證 (被指派的 SUPPLIER)  
**用途**: 提交專案進入審核流程

### Request Headers

```
Authorization: Bearer <accessToken>
```

### Path Parameters

| 參數 | 類型 | 說明 |
|------|------|------|
| projectSupplierId | integer | 專案供應商 ID |

### Request Body

無需 Request Body

**提交前驗證:**
- 所有必填題目必須已回答
- 答案格式必須正確
- 專案狀態必須為 `IN_PROGRESS` 或 `RETURNED`

### Response (200 OK)

```json
{
  "success": true,
  "data": {
    "projectSupplierId": 101,
    "status": "SUBMITTED",
    "submittedAt": "2025-12-02T06:08:38.435Z",
    "message": "專案已成功提交，將進入審核流程"
  }
}
```

**狀態變更:**
- 專案狀態從 `IN_PROGRESS` 變更為 `SUBMITTED`
- 若有設定審核流程，會自動進入第一階段審核 (`REVIEWING`)
- `currentStage` 設為 1

### Error Responses

**403 Forbidden - SUPPLIER 未被指派**
```json
{
  "success": false,
  "error": {
    "code": "SUPPLIER_NOT_ASSIGNED",
    "message": "您無權提交此專案"
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

**409 Conflict - 專案已提交**
```json
{
  "success": false,
  "error": {
    "code": "PROJECT_ALREADY_SUBMITTED",
    "message": "專案已提交，無法重複提交"
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

**422 Unprocessable Entity - 必填題目未完成**
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "尚有必填題目未完成",
    "details": {
      "missingQuestions": [
        {
          "questionId": "q_005",
          "questionText": "請簡述貴公司的品質管理流程"
        },
        {
          "questionId": "q_007",
          "questionText": "請上傳貴公司的營業執照"
        }
      ]
    }
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

---

## 6.4 上傳檔案

**Endpoint**: `POST /api/v1/files/upload`  
**權限**: 需要認證  
**用途**: 上傳檔案 (用於 FILE 類型題目)

### Request Headers

```
Authorization: Bearer <accessToken>
Content-Type: multipart/form-data
```

### Request Body (multipart/form-data)

| 欄位 | 類型 | 必填 | 說明 |
|------|------|------|------|
| file | file | ✓ | 檔案 |
| projectId | string | ✓ | 所屬專案 ID |
| questionId | string | ✓ | 所屬題目 ID |

**檔案限制:**
- 最大檔案大小: 依題目設定 (預設 5MB)
- 允許的檔案類型: 依題目設定 (預設: pdf, jpg, png, docx)

### Response (200 OK)

```json
{
  "success": true,
  "data": {
    "fileId": "file_abc123",
    "fileName": "company-license.pdf",
    "fileSize": 1024000,
    "fileUrl": "https://storage.example.com/files/abc123.pdf",
    "contentType": "application/pdf",
    "uploadedAt": "2025-12-02T06:08:38.435Z"
  }
}
```

**使用流程:**
1. 呼叫此 API 上傳檔案
2. 取得回傳的 `fileUrl`
3. 將 `fileUrl` 作為答案值儲存至對應題目
4. 呼叫 `PUT /api/v1/projects/{projectId}/answers` 暫存答案

### Error Responses

**413 Payload Too Large - 檔案過大**
```json
{
  "success": false,
  "error": {
    "code": "FILE_TOO_LARGE",
    "message": "檔案大小超過限制 (5MB)"
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

**422 Unprocessable Entity - 檔案類型不允許**
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "不允許的檔案類型",
    "details": {
      "allowedTypes": ["pdf", "jpg", "png"],
      "receivedType": "exe"
    }
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

---

## 使用情境說明

### 情境 1: 供應商填寫問卷 (暫存)
1. 供應商登入並進入專案
2. 呼叫 `GET /api/v1/project-suppliers/{projectSupplierId}/answers` 載入已儲存的答案
3. 供應商填寫部分題目 (如: 前 5 題)
4. 點擊「暫存」按鈕
5. 呼叫 `PUT /api/v1/project-suppliers/{projectSupplierId}/answers` 暫存答案
6. 系統提示「儲存成功」
7. 供應商稍後可再次進入繼續填寫

### 情境 2: 供應商填寫問卷 (提交)
1. 供應商進入專案並載入已儲存的答案
2. 完成所有必填題目
3. 點擊「提交」按鈕
4. 前端先驗證必填題目是否完成
5. 呼叫 `POST /api/v1/project-suppliers/{projectSupplierId}/submit`
6. 若有未完成的必填題目，後端回傳錯誤，前端顯示未完成的題目清單
7. 若全部完成，專案狀態變更為 `SUBMITTED`，進入審核流程
8. 供應商無法再修改答案

### 情境 3: 上傳檔案
1. 供應商填寫到「請上傳貴公司的營業執照」題目
2. 點擊「選擇檔案」按鈕，選擇 PDF 檔案
3. 呼叫 `POST /api/v1/files/upload` 上傳檔案
4. 取得回傳的 `fileUrl`
5. 將 `fileUrl` 作為答案值儲存至對應題目
6. 呼叫 `PUT /api/v1/project-suppliers/{projectSupplierId}/answers` 暫存答案

### 情境 4: 專案被退回後重新填寫
1. 專案被審核者退回，狀態變更為 `RETURNED`
2. 供應商收到通知 (Email 或系統通知)
3. 供應商進入專案，看到退回原因
4. 修改被指出問題的答案
5. 重新提交專案
6. 專案狀態變更為 `SUBMITTED`，重新進入第一階段審核

---

## 答案驗證規則

### TEXT (簡答題)
- 檢查 `maxLength` (若有設定)
- 必填時不可為空字串

### NUMBER (數字題)
- 必須為數字類型
- 檢查 `numberMin` 與 `numberMax` (若有設定)

### DATE (日期題)
- 必須為 ISO 8601 格式字串
- 例: `2025-12-02`

### BOOLEAN (布林題)
- 必須為 `true` 或 `false`

### SINGLE_CHOICE (單選題)
- 必須為字串
- 必須在 `options` 陣列中

### MULTI_CHOICE (複選題)
- 必須為字串陣列
- 所有選項必須在 `options` 陣列中
- 不可為空陣列 (必填時)

### FILE (檔案上傳題)
- 必須為有效的檔案 URL
- 檔案必須存在於系統中
- 檔案類型與大小符合題目設定

### RATING (評分題)
- 必須為數字類型
- 必須在 `ratingMin` 與 `ratingMax` 範圍內
- 必須符合 `ratingStep` 的倍數

---

## 前端實作建議

### 自動儲存功能
```javascript
// 每 30 秒自動暫存一次
setInterval(() => {
  if (hasUnsavedChanges) {
    saveAnswers();
  }
}, 30000);

// 使用者離開頁面前提示
window.addEventListener('beforeunload', (e) => {
  if (hasUnsavedChanges) {
    e.preventDefault();
    e.returnValue = '';
  }
});
```

### 檔案上傳進度顯示
```javascript
const uploadFile = async (file, projectId, questionId) => {
  const formData = new FormData();
  formData.append('file', file);
  formData.append('projectId', projectId);
  formData.append('questionId', questionId);

  const response = await fetch('/api/v1/files/upload', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`
    },
    body: formData,
    onUploadProgress: (progressEvent) => {
      const percentCompleted = Math.round(
        (progressEvent.loaded * 100) / progressEvent.total
      );
      updateProgressBar(percentCompleted);
    }
  });

  return response.json();
};
```

### 提交前驗證
```javascript
const validateBeforeSubmit = (questions, answers) => {
  const missing = [];
  
  questions.forEach(question => {
    if (question.required && !answers[question.id]) {
      missing.push({
        questionId: question.id,
        questionText: question.text
      });
    }
  });
  
  if (missing.length > 0) {
    showMissingQuestionsDialog(missing);
    return false;
  }
  
  return true;
};
```
