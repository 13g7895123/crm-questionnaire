# 錯誤處理規範 (Error Handling Specification)

## 概述

本文件定義 CRM 問卷系統 API 的統一錯誤處理規範，包含錯誤碼、錯誤格式、前端處理策略等。

## 統一錯誤回應格式

所有 API 錯誤回應遵循以下格式：

```json
{
  "error": "ERROR_CODE",
  "message": "Human-readable error message",
  "details": {
    // Optional additional error information
  }
}
```

### 欄位說明

- `error`: 錯誤碼（字串），用於程式判斷錯誤類型
- `message`: 人類可讀的錯誤訊息（繁體中文）
- `details`: 選填，額外的錯誤細節資訊

## HTTP 狀態碼

### 2xx 成功

- `200 OK`: 請求成功
- `201 Created`: 資源建立成功
- `204 No Content`: 請求成功但無回應內容（如刪除操作）

### 4xx 客戶端錯誤

- `400 Bad Request`: 請求參數錯誤或驗證失敗
- `401 Unauthorized`: 未認證或 Token 無效
- `403 Forbidden`: 已認證但無權限執行操作
- `404 Not Found`: 請求的資源不存在
- `409 Conflict`: 資源衝突（如重複建立）
- `422 Unprocessable Entity`: 請求格式正確但語意錯誤
- `429 Too Many Requests`: 請求次數過多（限流）

### 5xx 伺服器錯誤

- `500 Internal Server Error`: 伺服器內部錯誤
- `502 Bad Gateway`: 閘道錯誤
- `503 Service Unavailable`: 服務暫時無法使用
- `504 Gateway Timeout`: 閘道逾時

## 標準錯誤碼

### 認證與授權錯誤 (AUTH_*)

#### AUTH_001: MISSING_CREDENTIALS

```json
{
  "error": "MISSING_CREDENTIALS",
  "message": "使用者名稱與密碼為必填項目"
}
```

**HTTP 狀態**: 400 Bad Request

---

#### AUTH_002: INVALID_CREDENTIALS

```json
{
  "error": "INVALID_CREDENTIALS",
  "message": "帳號或密碼錯誤"
}
```

**HTTP 狀態**: 401 Unauthorized

---

#### AUTH_003: TOKEN_EXPIRED

```json
{
  "error": "TOKEN_EXPIRED",
  "message": "登入憑證已過期，請重新登入"
}
```

**HTTP 狀態**: 401 Unauthorized

---

#### AUTH_004: TOKEN_INVALID

```json
{
  "error": "TOKEN_INVALID",
  "message": "無效的登入憑證"
}
```

**HTTP 狀態**: 401 Unauthorized

---

#### AUTH_005: PERMISSION_DENIED

```json
{
  "error": "PERMISSION_DENIED",
  "message": "您無權限執行此操作"
}
```

**HTTP 狀態**: 403 Forbidden

---

### 驗證錯誤 (VALIDATION_*)

#### VALIDATION_001: INVALID_EMAIL

```json
{
  "error": "INVALID_EMAIL",
  "message": "Email 格式不正確"
}
```

---

#### VALIDATION_002: INVALID_PHONE

```json
{
  "error": "INVALID_PHONE",
  "message": "電話號碼格式不正確"
}
```

---

#### VALIDATION_003: INVALID_PASSWORD_FORMAT

```json
{
  "error": "INVALID_PASSWORD_FORMAT",
  "message": "密碼長度至少需要 8 個字元"
}
```

---

#### VALIDATION_004: VALIDATION_FAILED

```json
{
  "error": "VALIDATION_FAILED",
  "message": "資料驗證失敗",
  "details": {
    "fields": [
      {
        "field": "email",
        "error": "Email 格式不正確"
      },
      {
        "field": "phone",
        "error": "電話號碼為必填"
      }
    ]
  }
}
```

---

### 資源錯誤 (RESOURCE_*)

#### RESOURCE_001: NOT_FOUND

```json
{
  "error": "NOT_FOUND",
  "message": "請求的資源不存在"
}
```

**HTTP 狀態**: 404 Not Found

---

#### RESOURCE_002: ALREADY_EXISTS

```json
{
  "error": "ALREADY_EXISTS",
  "message": "資源已存在",
  "details": {
    "resource": "Department",
    "name": "採購部"
  }
}
```

**HTTP 狀態**: 409 Conflict

---

#### RESOURCE_003: IN_USE

```json
{
  "error": "IN_USE",
  "message": "資源正在使用中，無法刪除",
  "details": {
    "resource": "Template",
    "usedBy": ["project-001", "project-002"]
  }
}
```

**HTTP 狀態**: 400 Bad Request

---

### 專案相關錯誤 (PROJECT_*)

#### PROJECT_001: PROJECT_NOT_FOUND

```json
{
  "error": "PROJECT_NOT_FOUND",
  "message": "專案不存在"
}
```

---

#### PROJECT_002: PROJECT_NOT_EDITABLE

```json
{
  "error": "PROJECT_NOT_EDITABLE",
  "message": "此專案目前不可編輯",
  "details": {
    "status": "APPROVED"
  }
}
```

---

#### PROJECT_003: PROJECT_NOT_DELETABLE

```json
{
  "error": "PROJECT_NOT_DELETABLE",
  "message": "只有草稿狀態的專案可以刪除"
}
```

---

#### PROJECT_004: INVALID_REVIEW_CONFIG

```json
{
  "error": "INVALID_REVIEW_CONFIG",
  "message": "審核階段數量必須在 1-5 之間"
}
```

---

### 答案相關錯誤 (ANSWER_*)

#### ANSWER_001: MISSING_REQUIRED_ANSWER

```json
{
  "error": "MISSING_REQUIRED_ANSWER",
  "message": "缺少必填答案",
  "details": {
    "missingQuestions": ["q-001", "q-005"]
  }
}
```

---

#### ANSWER_002: INVALID_ANSWER_VALUE

```json
{
  "error": "INVALID_ANSWER_VALUE",
  "message": "答案值不符合規則",
  "details": {
    "questionId": "q-003",
    "error": "數值必須在 1-100 之間"
  }
}
```

---

### 檔案上傳錯誤 (FILE_*)

#### FILE_001: FILE_TOO_LARGE

```json
{
  "error": "FILE_TOO_LARGE",
  "message": "檔案大小超過限制",
  "details": {
    "maxSize": 10485760,
    "actualSize": 15728640
  }
}
```

---

#### FILE_002: INVALID_FILE_TYPE

```json
{
  "error": "INVALID_FILE_TYPE",
  "message": "不支援的檔案類型",
  "details": {
    "allowedTypes": ["pdf", "jpg", "png"],
    "actualType": "exe"
  }
}
```

---

### 審核相關錯誤 (REVIEW_*)

#### REVIEW_001: NOT_YOUR_TURN

```json
{
  "error": "NOT_YOUR_TURN",
  "message": "目前不是您的部門審核階段",
  "details": {
    "currentStage": 2,
    "yourStage": 1
  }
}
```

---

#### REVIEW_002: PROJECT_NOT_REVIEWABLE

```json
{
  "error": "PROJECT_NOT_REVIEWABLE",
  "message": "此專案目前不可審核",
  "details": {
    "status": "DRAFT"
  }
}
```

---

### 系統錯誤 (SYSTEM_*)

#### SYSTEM_001: INTERNAL_ERROR

```json
{
  "error": "INTERNAL_ERROR",
  "message": "系統發生錯誤，請稍後再試"
}
```

**HTTP 狀態**: 500 Internal Server Error

---

#### SYSTEM_002: SERVICE_UNAVAILABLE

```json
{
  "error": "SERVICE_UNAVAILABLE",
  "message": "服務暫時無法使用，請稍後再試"
}
```

**HTTP 狀態**: 503 Service Unavailable

---

## 前端錯誤處理

### 統一錯誤處理器 (useApi)

```typescript
// ~/composables/useApi.ts
import { parseApiError, handleResponseStatus } from '~/utils/api-error'

export const useApi = () => {
  const fetchApi = async (endpoint: string, options?: any) => {
    try {
      const response = await fetch(endpoint, options)
      
      if (!response.ok) {
        let data
        try {
          data = await response.json()
        } catch {
          data = null
        }
        handleResponseStatus(response.status, data)
      }
      
      return await response.json()
    } catch (err) {
      const parsed = parseApiError(err)
      throw parsed
    }
  }
  
  return { fetchApi, get, post, put, delete: delete_ }
}
```

---

### 錯誤解析器 (api-error.ts)

```typescript
// ~/utils/api-error.ts

export interface ErrorResponse {
  error: string
  message: string
  details?: any
  status?: number
}

/**
 * 解析 API 錯誤
 */
export const parseApiError = (error: any): ErrorResponse => {
  // 已經是我們的錯誤格式
  if (error.error && error.message) {
    return error
  }
  
  // 網路錯誤
  if (error instanceof TypeError && error.message === 'Failed to fetch') {
    return {
      error: 'NETWORK_ERROR',
      message: '網路連線失敗，請檢查您的網路設定'
    }
  }
  
  // 其他未知錯誤
  return {
    error: 'UNKNOWN_ERROR',
    message: error.message || '發生未知錯誤'
  }
}

/**
 * 處理 HTTP 狀態碼
 */
export const handleResponseStatus = (status: number, data: any) => {
  switch (status) {
    case 401:
      handleUnauthorized(data)
      break
    case 403:
      throw {
        error: data?.error || 'FORBIDDEN',
        message: data?.message || '您無權限執行此操作',
        status
      }
    case 404:
      throw {
        error: data?.error || 'NOT_FOUND',
        message: data?.message || '請求的資源不存在',
        status
      }
    case 409:
      throw {
        error: data?.error || 'CONFLICT',
        message: data?.message || '資源衝突',
        status
      }
    case 422:
      throw {
        error: data?.error || 'VALIDATION_ERROR',
        message: data?.message || '資料驗證失敗',
        details: data?.details,
        status
      }
    case 500:
      throw {
        error: 'INTERNAL_ERROR',
        message: '系統發生錯誤，請稍後再試',
        status
      }
    default:
      throw {
        error: data?.error || 'API_ERROR',
        message: data?.message || `請求失敗 (${status})`,
        status
      }
  }
}

/**
 * 處理未授權錯誤（401）
 */
const handleUnauthorized = (data: any) => {
  const authStore = useAuthStore()
  
  // 清除 Token 與使用者資訊
  authStore.logout()
  
  // 導向登入頁面
  if (process.client) {
    navigateTo('/login')
  }
  
  throw {
    error: data?.error || 'UNAUTHORIZED',
    message: data?.message || '登入憑證已過期，請重新登入',
    status: 401
  }
}

/**
 * 取得錯誤訊息（用於 UI 顯示）
 */
export const getErrorMessage = (error: ErrorResponse): string => {
  return error.message || '發生錯誤'
}

/**
 * 檢查是否為特定錯誤
 */
export const isErrorCode = (error: ErrorResponse, code: string): boolean => {
  return error.error === code
}
```

---

### 在元件中使用

```vue
<script setup lang="ts">
import { ref } from 'vue'
import { useProjects } from '~/composables/useProjects'
import { getErrorMessage } from '~/utils/api-error'

const { createProject } = useProjects()

const error = ref<string | null>(null)
const isLoading = ref(false)

const handleSubmit = async (data: any) => {
  isLoading.value = true
  error.value = null
  
  try {
    await createProject(data)
    alert('專案建立成功')
  } catch (err: any) {
    error.value = getErrorMessage(err)
    
    // 針對特定錯誤碼做處理
    if (err.error === 'INVALID_REVIEW_CONFIG') {
      // 顯示特定的錯誤處理 UI
    }
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div>
    <div v-if="error" class="error-message">
      {{ error }}
    </div>
    
    <button @click="handleSubmit" :disabled="isLoading">
      {{ isLoading ? '處理中...' : '提交' }}
    </button>
  </div>
</template>
```

---

## 錯誤訊息多語系

### i18n 錯誤訊息

```typescript
// ~/locales/zh-TW.json
{
  "errors": {
    "MISSING_CREDENTIALS": "使用者名稱與密碼為必填項目",
    "INVALID_CREDENTIALS": "帳號或密碼錯誤",
    "TOKEN_EXPIRED": "登入憑證已過期，請重新登入",
    "PERMISSION_DENIED": "您無權限執行此操作",
    "NOT_FOUND": "請求的資源不存在",
    "NETWORK_ERROR": "網路連線失敗，請檢查您的網路設定"
  }
}
```

### 使用 i18n 取得錯誤訊息

```typescript
export const getErrorMessage = (error: ErrorResponse): string => {
  const { t } = useI18n()
  
  // 嘗試使用 i18n 取得訊息
  const i18nKey = `errors.${error.error}`
  const i18nMessage = t(i18nKey)
  
  // 如果有翻譯則使用，否則使用 API 回傳的訊息
  return i18nMessage !== i18nKey ? i18nMessage : error.message
}
```

---

## 錯誤追蹤與日誌

### 錯誤日誌

```typescript
export const logError = (error: ErrorResponse, context?: any) => {
  console.error('[API Error]', {
    error: error.error,
    message: error.message,
    details: error.details,
    status: error.status,
    context,
    timestamp: new Date().toISOString()
  })
  
  // 在生產環境發送到錯誤追蹤服務（如 Sentry）
  if (process.env.NODE_ENV === 'production') {
    // Sentry.captureException(error)
  }
}
```

---

## 全域錯誤處理

### Nuxt 全域錯誤處理器

```typescript
// ~/plugins/error-handler.ts
export default defineNuxtPlugin((nuxtApp) => {
  nuxtApp.hook('vue:error', (error, instance, info) => {
    console.error('Vue Error:', error, info)
    
    // 記錄錯誤
    logError(parseApiError(error), { component: instance?.$options.name })
  })
})
```

---

## 測試錯誤處理

```typescript
describe('API Error Handling', () => {
  it('should parse API error correctly', () => {
    const error = {
      error: 'INVALID_CREDENTIALS',
      message: '帳號或密碼錯誤'
    }
    
    const parsed = parseApiError(error)
    
    expect(parsed.error).toBe('INVALID_CREDENTIALS')
    expect(parsed.message).toBe('帳號或密碼錯誤')
  })
  
  it('should handle 401 unauthorized', async () => {
    const authStore = useAuthStore()
    
    try {
      handleResponseStatus(401, { error: 'TOKEN_EXPIRED' })
    } catch (err: any) {
      expect(err.status).toBe(401)
      expect(authStore.token).toBeNull()
    }
  })
  
  it('should handle network error', () => {
    const networkError = new TypeError('Failed to fetch')
    const parsed = parseApiError(networkError)
    
    expect(parsed.error).toBe('NETWORK_ERROR')
  })
})
```

---

## 最佳實踐

### 1. 統一錯誤處理

所有 API 呼叫都應使用 `useApi` composable，確保錯誤處理一致。

### 2. 使用者友善的錯誤訊息

錯誤訊息應：
- 使用繁體中文
- 清楚說明問題
- 提供解決方案（如可能）

### 3. 不洩漏敏感資訊

生產環境的錯誤訊息不應包含：
- 資料庫錯誤細節
- 系統路徑資訊
- 內部技術細節

### 4. 適當的日誌記錄

- 開發環境：記錄完整錯誤資訊
- 生產環境：發送到錯誤追蹤服務

### 5. 重試機制

對於暫時性錯誤（如網路問題），可實作自動重試：

```typescript
const fetchWithRetry = async (url: string, options: any, retries = 3) => {
  for (let i = 0; i < retries; i++) {
    try {
      return await fetch(url, options)
    } catch (error) {
      if (i === retries - 1) throw error
      await new Promise(resolve => setTimeout(resolve, 1000 * (i + 1)))
    }
  }
}
```

---

## 相關文件

- [認證與授權 API](./auth.md)
- [資料模型](./data-models.md)
- [API 需求文件](../API-REQUIREMENTS.md)
