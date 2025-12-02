# 使用者管理 API

## 目錄
- [2.1 更新個人資料](#21-更新個人資料)
- [2.2 修改密碼](#22-修改密碼)
- [2.3 取得使用者列表](#23-取得使用者列表)
- [2.4 建立使用者](#24-建立使用者-admin)
- [2.5 更新使用者資料](#25-更新使用者資料-admin)
- [2.6 刪除使用者](#26-刪除使用者-admin)

---

## 2.1 更新個人資料

**Endpoint**: `PUT /api/v1/users/me`  
**權限**: 需要認證  
**用途**: 更新當前登入使用者的個人資料

### Request Headers

```
Authorization: Bearer <accessToken>
```

### Request Body

```json
{
  "email": "newemail@example.com",
  "phone": "0987654321",
  "departmentId": "dept_new789"
}
```

**欄位說明:**

| 欄位 | 類型 | 必填 | 說明 |
|------|------|------|------|
| email | string | ✗ | Email 地址 (需符合 Email 格式) |
| phone | string | ✗ | 電話號碼 |
| departmentId | string | ✗ | 所屬部門 ID |

**注意事項:**
- 使用者無法修改自己的 `username` 和 `role`
- Email 必須唯一，不可與其他使用者重複
- 部門必須屬於使用者所屬組織

### Response (200 OK)

```json
{
  "success": true,
  "data": {
    "id": "usr_abc123",
    "username": "user@example.com",
    "email": "newemail@example.com",
    "phone": "0987654321",
    "role": "HOST",
    "organizationId": "org_xyz789",
    "departmentId": "dept_new789",
    "department": {
      "id": "dept_new789",
      "name": "新部門名稱"
    },
    "updatedAt": "2025-12-02T06:08:38.435Z"
  }
}
```

### Error Responses

**409 Conflict - Email 已存在**
```json
{
  "success": false,
  "error": {
    "code": "RESOURCE_CONFLICT",
    "message": "此 Email 已被使用"
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

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

## 2.2 修改密碼

**Endpoint**: `PUT /api/v1/users/me/password`  
**權限**: 需要認證  
**用途**: 修改當前使用者的密碼

### Request Headers

```
Authorization: Bearer <accessToken>
```

### Request Body

```json
{
  "currentPassword": "oldPassword123",
  "newPassword": "newSecurePassword456",
  "confirmPassword": "newSecurePassword456"
}
```

**欄位說明:**

| 欄位 | 類型 | 必填 | 說明 |
|------|------|------|------|
| currentPassword | string | ✓ | 目前的密碼 |
| newPassword | string | ✓ | 新密碼 (最少 8 字元) |
| confirmPassword | string | ✓ | 確認新密碼 (需與 newPassword 相同) |

### Response (200 OK)

```json
{
  "success": true,
  "message": "密碼更新成功，請使用新密碼重新登入"
}
```

**注意事項:**
- 修改密碼後，所有已發放的 Token 將失效
- 使用者需要重新登入

### Error Responses

**401 Unauthorized - 目前密碼錯誤**
```json
{
  "success": false,
  "error": {
    "code": "AUTH_INVALID_CREDENTIALS",
    "message": "目前密碼錯誤"
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

**422 Unprocessable Entity - 密碼不符合要求**
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "新密碼與確認密碼不一致"
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

---

## 2.3 取得使用者列表

**Endpoint**: `GET /api/v1/users`  
**權限**: 需要認證 (ADMIN 或 HOST)  
**用途**: 取得使用者列表 (供管理員或製造商查詢)

### Request Headers

```
Authorization: Bearer <accessToken>
```

### Query Parameters

| 參數 | 類型 | 必填 | 說明 |
|------|------|------|------|
| page | integer | ✗ | 頁碼 (預設: 1) |
| limit | integer | ✗ | 每頁筆數 (預設: 20，最大: 100) |
| role | string | ✗ | 篩選角色 (HOST, SUPPLIER, ADMIN) |
| organizationId | string | ✗ | 篩選組織 ID |
| departmentId | string | ✗ | 篩選部門 ID |
| search | string | ✗ | 搜尋關鍵字 (搜尋 username, email) |

### Response (200 OK)

```json
{
  "success": true,
  "data": [
    {
      "id": "usr_abc123",
      "username": "user1@example.com",
      "email": "user1@example.com",
      "phone": "0912345678",
      "role": "HOST",
      "organizationId": "org_xyz789",
      "organization": {
        "id": "org_xyz789",
        "name": "製造商公司"
      },
      "departmentId": "dept_def456",
      "department": {
        "id": "dept_def456",
        "name": "品質管理部"
      },
      "createdAt": "2025-01-01T00:00:00.000Z",
      "updatedAt": "2025-12-02T06:08:38.435Z"
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 20,
    "total": 50,
    "totalPages": 3
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
    "message": "您沒有權限存取此資源"
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

---

## 2.4 建立使用者 (ADMIN)

**Endpoint**: `POST /api/v1/users`  
**權限**: 需要認證 (ADMIN)  
**用途**: 管理員建立新使用者

### Request Headers

```
Authorization: Bearer <accessToken>
```

### Request Body

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

**欄位說明:**

| 欄位 | 類型 | 必填 | 說明 |
|------|------|------|------|
| username | string | ✓ | 使用者名稱 (唯一) |
| email | string | ✓ | Email 地址 (唯一) |
| password | string | ✓ | 初始密碼 (最少 8 字元) |
| phone | string | ✗ | 電話號碼 |
| role | string | ✓ | 角色 (HOST, SUPPLIER, ADMIN) |
| organizationId | string | ✓ | 所屬組織 ID |
| departmentId | string | ✓ | 所屬部門 ID |

### Response (201 Created)

```json
{
  "success": true,
  "data": {
    "id": "usr_new789",
    "username": "newuser@example.com",
    "email": "newuser@example.com",
    "phone": "0912345678",
    "role": "SUPPLIER",
    "organizationId": "org_supplier123",
    "departmentId": "dept_procurement456",
    "createdAt": "2025-12-02T06:08:38.435Z",
    "updatedAt": "2025-12-02T06:08:38.435Z"
  }
}
```

### Error Responses

**409 Conflict - 使用者名稱或 Email 已存在**
```json
{
  "success": false,
  "error": {
    "code": "RESOURCE_CONFLICT",
    "message": "使用者名稱或 Email 已被使用"
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

---

## 2.5 更新使用者資料 (ADMIN)

**Endpoint**: `PUT /api/v1/users/{userId}`  
**權限**: 需要認證 (ADMIN)  
**用途**: 管理員更新使用者資料

### Request Headers

```
Authorization: Bearer <accessToken>
```

### Path Parameters

| 參數 | 類型 | 說明 |
|------|------|------|
| userId | string | 使用者 ID |

### Request Body

```json
{
  "email": "updated@example.com",
  "phone": "0987654321",
  "role": "HOST",
  "departmentId": "dept_new789"
}
```

**可更新欄位:**
- email
- phone
- role
- departmentId
- organizationId (需謹慎使用)

### Response (200 OK)

```json
{
  "success": true,
  "data": {
    "id": "usr_abc123",
    "username": "user@example.com",
    "email": "updated@example.com",
    "phone": "0987654321",
    "role": "HOST",
    "organizationId": "org_xyz789",
    "departmentId": "dept_new789",
    "updatedAt": "2025-12-02T06:08:38.435Z"
  }
}
```

### Error Responses

**404 Not Found - 使用者不存在**
```json
{
  "success": false,
  "error": {
    "code": "RESOURCE_NOT_FOUND",
    "message": "找不到指定的使用者"
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

---

## 2.6 刪除使用者 (ADMIN)

**Endpoint**: `DELETE /api/v1/users/{userId}`  
**權限**: 需要認證 (ADMIN)  
**用途**: 管理員刪除使用者

### Request Headers

```
Authorization: Bearer <accessToken>
```

### Path Parameters

| 參數 | 類型 | 說明 |
|------|------|------|
| userId | string | 使用者 ID |

### Response (204 No Content)

成功刪除，無回應內容

### Error Responses

**404 Not Found - 使用者不存在**
```json
{
  "success": false,
  "error": {
    "code": "RESOURCE_NOT_FOUND",
    "message": "找不到指定的使用者"
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

**409 Conflict - 使用者有關聯資料**
```json
{
  "success": false,
  "error": {
    "code": "RESOURCE_CONFLICT",
    "message": "此使用者有關聯的專案或審核紀錄，無法刪除"
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

---

## 使用情境說明

### 情境 1: 使用者更新個人資料
1. 使用者登入系統
2. 進入「帳戶管理」頁面
3. 修改 Email 或電話
4. 呼叫 `PUT /api/v1/users/me`
5. 系統更新資料並回傳最新資訊

### 情境 2: 使用者修改密碼
1. 使用者進入「修改密碼」頁面
2. 輸入目前密碼與新密碼
3. 呼叫 `PUT /api/v1/users/me/password`
4. 系統驗證目前密碼
5. 成功後使所有 Token 失效
6. 引導使用者重新登入

### 情境 3: 管理員建立供應商帳號
1. 管理員進入「使用者管理」頁面
2. 點擊「新增使用者」
3. 選擇組織類型為「SUPPLIER」
4. 填寫基本資料並設定初始密碼
5. 呼叫 `POST /api/v1/users`
6. 供應商收到帳號資訊並首次登入
