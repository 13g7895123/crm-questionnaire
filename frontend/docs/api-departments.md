# 部門管理 API

## 目錄
- [3.1 取得部門列表](#31-取得部門列表)
- [3.2 取得部門詳情](#32-取得部門詳情)
- [3.3 建立部門](#33-建立部門)
- [3.4 更新部門](#34-更新部門)
- [3.5 刪除部門](#35-刪除部門)

---

## 3.1 取得部門列表

**Endpoint**: `GET /api/v1/departments`  
**權限**: 需要認證  
**用途**: 取得部門列表 (供使用者選擇所屬部門、設定審核流程時使用)

### Request Headers

```
Authorization: Bearer <accessToken>
```

### Query Parameters

| 參數 | 類型 | 必填 | 說明 |
|------|------|------|------|
| page | integer | ✗ | 頁碼 (預設: 1) |
| limit | integer | ✗ | 每頁筆數 (預設: 20，最大: 100) |
| organizationId | string | ✗ | 篩選組織 ID (未指定則顯示使用者所屬組織) |
| search | string | ✗ | 搜尋關鍵字 (搜尋部門名稱) |

### Response (200 OK)

```json
{
  "success": true,
  "data": [
    {
      "id": "dept_abc123",
      "name": "品質管理部",
      "organizationId": "org_xyz789",
      "organization": {
        "id": "org_xyz789",
        "name": "製造商公司",
        "type": "HOST"
      },
      "createdAt": "2025-01-01T00:00:00.000Z",
      "updatedAt": "2025-12-02T06:08:38.435Z"
    },
    {
      "id": "dept_def456",
      "name": "採購部",
      "organizationId": "org_xyz789",
      "organization": {
        "id": "org_xyz789",
        "name": "製造商公司",
        "type": "HOST"
      },
      "createdAt": "2025-01-01T00:00:00.000Z",
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

### Error Responses

**401 Unauthorized - Token 無效**
```json
{
  "success": false,
  "error": {
    "code": "AUTH_TOKEN_INVALID",
    "message": "Token 無效或已過期"
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

---

## 3.2 取得部門詳情

**Endpoint**: `GET /api/v1/departments/{departmentId}`  
**權限**: 需要認證  
**用途**: 取得特定部門的詳細資訊

### Request Headers

```
Authorization: Bearer <accessToken>
```

### Path Parameters

| 參數 | 類型 | 說明 |
|------|------|------|
| departmentId | string | 部門 ID |

### Response (200 OK)

```json
{
  "success": true,
  "data": {
    "id": "dept_abc123",
    "name": "品質管理部",
    "organizationId": "org_xyz789",
    "organization": {
      "id": "org_xyz789",
      "name": "製造商公司",
      "type": "HOST"
    },
    "memberCount": 15,
    "createdAt": "2025-01-01T00:00:00.000Z",
    "updatedAt": "2025-12-02T06:08:38.435Z"
  }
}
```

**欄位說明:**

| 欄位 | 類型 | 說明 |
|------|------|------|
| memberCount | integer | 部門成員數量 |

### Error Responses

**404 Not Found - 部門不存在**
```json
{
  "success": false,
  "error": {
    "code": "RESOURCE_NOT_FOUND",
    "message": "找不到指定的部門"
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

---

## 3.3 建立部門

**Endpoint**: `POST /api/v1/departments`  
**權限**: 需要認證 (ADMIN)  
**用途**: 管理員建立新部門

### Request Headers

```
Authorization: Bearer <accessToken>
```

### Request Body

```json
{
  "name": "環境安全部",
  "organizationId": "org_xyz789"
}
```

**欄位說明:**

| 欄位 | 類型 | 必填 | 說明 |
|------|------|------|------|
| name | string | ✓ | 部門名稱 (最長 100 字元) |
| organizationId | string | ✓ | 所屬組織 ID |

### Response (201 Created)

```json
{
  "success": true,
  "data": {
    "id": "dept_new789",
    "name": "環境安全部",
    "organizationId": "org_xyz789",
    "organization": {
      "id": "org_xyz789",
      "name": "製造商公司",
      "type": "HOST"
    },
    "createdAt": "2025-12-02T06:08:38.435Z",
    "updatedAt": "2025-12-02T06:08:38.435Z"
  }
}
```

### Error Responses

**403 Forbidden - 權限不足**
```json
{
  "success": false,
  "error": {
    "code": "AUTH_INSUFFICIENT_PERMISSION",
    "message": "您沒有權限執行此操作"
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

**409 Conflict - 部門名稱重複**
```json
{
  "success": false,
  "error": {
    "code": "RESOURCE_CONFLICT",
    "message": "此組織已存在相同名稱的部門"
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

**422 Unprocessable Entity - 驗證錯誤**
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "資料驗證失敗",
    "details": {
      "name": "部門名稱為必填欄位"
    }
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

---

## 3.4 更新部門

**Endpoint**: `PUT /api/v1/departments/{departmentId}`  
**權限**: 需要認證 (ADMIN)  
**用途**: 管理員更新部門資訊

### Request Headers

```
Authorization: Bearer <accessToken>
```

### Path Parameters

| 參數 | 類型 | 說明 |
|------|------|------|
| departmentId | string | 部門 ID |

### Request Body

```json
{
  "name": "品質與環境管理部"
}
```

**欄位說明:**

| 欄位 | 類型 | 必填 | 說明 |
|------|------|------|------|
| name | string | ✓ | 新的部門名稱 (最長 100 字元) |

**注意事項:**
- 無法修改部門的所屬組織 (organizationId)
- 部門名稱在同組織內必須唯一

### Response (200 OK)

```json
{
  "success": true,
  "data": {
    "id": "dept_abc123",
    "name": "品質與環境管理部",
    "organizationId": "org_xyz789",
    "organization": {
      "id": "org_xyz789",
      "name": "製造商公司",
      "type": "HOST"
    },
    "createdAt": "2025-01-01T00:00:00.000Z",
    "updatedAt": "2025-12-02T06:08:38.435Z"
  }
}
```

### Error Responses

**404 Not Found - 部門不存在**
```json
{
  "success": false,
  "error": {
    "code": "RESOURCE_NOT_FOUND",
    "message": "找不到指定的部門"
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

**409 Conflict - 部門名稱重複**
```json
{
  "success": false,
  "error": {
    "code": "RESOURCE_CONFLICT",
    "message": "此組織已存在相同名稱的部門"
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

---

## 3.5 刪除部門

**Endpoint**: `DELETE /api/v1/departments/{departmentId}`  
**權限**: 需要認證 (ADMIN)  
**用途**: 管理員刪除部門

### Request Headers

```
Authorization: Bearer <accessToken>
```

### Path Parameters

| 參數 | 類型 | 說明 |
|------|------|------|
| departmentId | string | 部門 ID |

### Response (204 No Content)

成功刪除，無回應內容

### Error Responses

**404 Not Found - 部門不存在**
```json
{
  "success": false,
  "error": {
    "code": "RESOURCE_NOT_FOUND",
    "message": "找不到指定的部門"
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

**409 Conflict - 部門正在使用中**
```json
{
  "success": false,
  "error": {
    "code": "DEPARTMENT_IN_USE",
    "message": "此部門有使用者或被專案審核流程使用，無法刪除",
    "details": {
      "userCount": 5,
      "projectCount": 3
    }
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

**建議處理方式:**
- 在刪除部門前，應先將部門內的使用者轉移至其他部門
- 檢查是否有專案的審核流程使用該部門
- 考慮使用「停用」而非「刪除」功能 (需後端支援)

---

## 使用情境說明

### 情境 1: 使用者選擇所屬部門
1. 使用者進入「帳戶管理」頁面
2. 點擊「編輯部門」欄位
3. 呼叫 `GET /api/v1/departments` 取得部門下拉選單
4. 使用者選擇部門並儲存
5. 呼叫 `PUT /api/v1/users/me` 更新部門資訊

### 情境 2: 建立專案時設定審核流程
1. 製造商建立 SAQ 專案
2. 在「審核流程設定」區塊
3. 呼叫 `GET /api/v1/departments` 取得本組織所有部門
4. 選擇多個部門設定審核階段順序
   - 第一階段: 品質管理部
   - 第二階段: 採購部
   - 第三階段: 高階主管部
5. 儲存專案時將審核流程一併儲存

### 情境 3: 管理員維護部門清單
1. 管理員進入「部門管理」頁面
2. 呼叫 `GET /api/v1/departments` 列出所有部門
3. 點擊「新增部門」
4. 輸入部門名稱 (如: "法務部")
5. 呼叫 `POST /api/v1/departments` 建立部門
6. 新部門立即可供使用者選擇

### 情境 4: 刪除部門前檢查
1. 管理員嘗試刪除「採購部」
2. 呼叫 `DELETE /api/v1/departments/dept_def456`
3. 後端檢查發現該部門有 5 名使用者與 3 個專案關聯
4. 回傳 409 錯誤，提示需先處理關聯資料
5. 管理員將 5 名使用者轉移至其他部門
6. 更新 3 個專案的審核流程設定
7. 再次呼叫刪除 API，成功刪除

---

## 資料關聯說明

部門與以下實體有關聯:

### 與使用者 (User)
- 每個使用者必須屬於一個部門
- 刪除部門前需確保無使用者歸屬該部門

### 與專案審核流程 (ReviewStageConfig)
- 專案的每個審核階段會指定負責部門
- 刪除部門前需確保無專案審核流程使用該部門

### 與組織 (Organization)
- 每個部門必須屬於一個組織
- 同一組織內的部門名稱不可重複
- 使用者只能看到與選擇自己組織內的部門

---

## 注意事項

1. **權限控制**
   - 一般使用者只能查看 (GET) 部門列表
   - 建立、更新、刪除操作需要 ADMIN 權限

2. **組織隔離**
   - HOST 組織與 SUPPLIER 組織的部門完全獨立
   - 使用者只能看到自己所屬組織的部門

3. **審核流程考量**
   - 設定專案審核流程時，部門選擇順序代表審核階段順序
   - 同一專案的審核流程不可重複使用同一部門

4. **刪除限制**
   - 有使用者或專案關聯的部門無法刪除
   - 建議實作「停用」功能而非直接刪除
   - 刪除前應顯示警告訊息與關聯資料統計
