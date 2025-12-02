# 認證相關 API

## 目錄
- [1.1 登入](#11-登入)
- [1.2 登出](#12-登出)
- [1.3 取得當前使用者資訊](#13-取得當前使用者資訊)
- [1.4 更新 Token](#14-更新-token)
- [1.5 驗證 Token](#15-驗證-token)

---

## 1.1 登入

**Endpoint**: `POST /api/v1/auth/login`  
**權限**: 公開 (無需認證)  
**用途**: 使用者登入系統，取得 JWT Token

### Request Body

```json
{
  "username": "user@example.com",
  "password": "securePassword123"
}
```

**欄位說明:**

| 欄位 | 類型 | 必填 | 說明 |
|------|------|------|------|
| username | string | ✓ | 使用者帳號 (Email 或帳號名稱) |
| password | string | ✓ | 密碼 (最少 8 字元) |

### Response (200 OK)

```json
{
  "success": true,
  "data": {
    "accessToken": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "refreshToken": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "expiresIn": 3600,
    "user": {
      "id": "usr_abc123",
      "username": "user@example.com",
      "email": "user@example.com",
      "phone": "0912345678",
      "role": "HOST",
      "organizationId": "org_xyz789",
      "organization": {
        "id": "org_xyz789",
        "name": "製造商公司",
        "type": "HOST"
      },
      "departmentId": "dept_def456",
      "department": {
        "id": "dept_def456",
        "name": "品質管理部"
      }
    }
  }
}
```

### Error Responses

**401 Unauthorized - 帳號或密碼錯誤**
```json
{
  "success": false,
  "error": {
    "code": "AUTH_INVALID_CREDENTIALS",
    "message": "帳號或密碼錯誤"
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
      "username": "帳號為必填欄位",
      "password": "密碼最少需要 8 個字元"
    }
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

---

## 1.2 登出

**Endpoint**: `POST /api/v1/auth/logout`  
**權限**: 需要認證  
**用途**: 使用者登出，使 Token 失效

### Request Headers

```
Authorization: Bearer <accessToken>
```

### Request Body

無需 Request Body

### Response (200 OK)

```json
{
  "success": true,
  "message": "登出成功"
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

## 1.3 取得當前使用者資訊

**Endpoint**: `GET /api/v1/auth/me`  
**權限**: 需要認證  
**用途**: 取得當前登入使用者的完整資訊

### Request Headers

```
Authorization: Bearer <accessToken>
```

### Response (200 OK)

```json
{
  "success": true,
  "data": {
    "id": "usr_abc123",
    "username": "user@example.com",
    "email": "user@example.com",
    "phone": "0912345678",
    "role": "HOST",
    "organizationId": "org_xyz789",
    "organization": {
      "id": "org_xyz789",
      "name": "製造商公司",
      "type": "HOST",
      "createdAt": "2025-01-01T00:00:00.000Z",
      "updatedAt": "2025-01-01T00:00:00.000Z"
    },
    "departmentId": "dept_def456",
    "department": {
      "id": "dept_def456",
      "name": "品質管理部",
      "organizationId": "org_xyz789",
      "createdAt": "2025-01-01T00:00:00.000Z",
      "updatedAt": "2025-01-01T00:00:00.000Z"
    },
    "createdAt": "2025-01-01T00:00:00.000Z",
    "updatedAt": "2025-12-02T06:08:38.435Z"
  }
}
```

### Error Responses

**401 Unauthorized**
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

## 1.4 更新 Token

**Endpoint**: `POST /api/v1/auth/refresh`  
**權限**: 需要 Refresh Token  
**用途**: 使用 Refresh Token 取得新的 Access Token

### Request Body

```json
{
  "refreshToken": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

**欄位說明:**

| 欄位 | 類型 | 必填 | 說明 |
|------|------|------|------|
| refreshToken | string | ✓ | 登入時取得的 Refresh Token |

### Response (200 OK)

```json
{
  "success": true,
  "data": {
    "accessToken": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "expiresIn": 3600
  }
}
```

### Error Responses

**401 Unauthorized - Refresh Token 無效**
```json
{
  "success": false,
  "error": {
    "code": "AUTH_TOKEN_INVALID",
    "message": "Refresh Token 無效或已過期，請重新登入"
  },
  "timestamp": "2025-12-02T06:08:38.435Z"
}
```

---

## 1.5 驗證 Token

**Endpoint**: `POST /api/v1/auth/verify`  
**權限**: 公開  
**用途**: 驗證 Access Token 是否有效 (前端用於確認登入狀態)

### Request Body

```json
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

**欄位說明:**

| 欄位 | 類型 | 必填 | 說明 |
|------|------|------|------|
| token | string | ✓ | 要驗證的 Access Token |

### Response (200 OK)

```json
{
  "success": true,
  "data": {
    "valid": true,
    "expiresAt": "2025-12-02T07:08:38.435Z",
    "userId": "usr_abc123"
  }
}
```

**Token 無效時:**
```json
{
  "success": true,
  "data": {
    "valid": false
  }
}
```

---

## 認證流程圖

```
使用者輸入帳號密碼
    ↓
POST /auth/login
    ↓
取得 accessToken & refreshToken
    ↓
前端儲存 Token (LocalStorage/Cookies)
    ↓
每次 API 請求帶 Authorization Header
    ↓
Token 過期 (401)?
    ├─ Yes → POST /auth/refresh 更新 Token
    └─ No → 繼續使用
    ↓
使用者登出
    ↓
POST /auth/logout
    ↓
清除前端 Token
```

## 安全性考量

1. **密碼要求**: 最少 8 字元，建議包含大小寫字母、數字與特殊符號
2. **Token 儲存**: 建議使用 httpOnly Cookies 或 LocalStorage (需注意 XSS 風險)
3. **HTTPS**: 生產環境必須使用 HTTPS
4. **Rate Limiting**: 登入 API 限制每 IP 每分鐘最多 5 次嘗試
5. **Token 過期處理**: Access Token 過期後自動呼叫 Refresh Token API
6. **登出後清理**: 登出時必須清除前端所有 Token
