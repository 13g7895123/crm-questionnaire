# 會員中心與用戶管理 API (User Management)

## 概述

用戶管理模組負責處理使用者個人資料的查詢與更新，包含基本資料修改與密碼變更。

## API 端點

### 1. 取得目前使用者資訊

**端點**: `GET /api/auth/me`

**描述**: 取得目前登入使用者的詳細資訊。

**請求 Headers**:

```
Authorization: Bearer <jwt_token>
```

**回應 (200 OK)**:

```json
{
  "id": "user-123",
  "username": "john.doe",
  "email": "john.doe@example.com",
  "phone": "+886912345678",
  "departmentId": "dept-001",
  "department": {
    "id": "dept-001",
    "name": "採購部",
    "organizationId": "org-001",
    "createdAt": "2025-01-01T00:00:00.000Z",
    "updatedAt": "2025-01-01T00:00:00.000Z"
  },
  "role": "HOST",
  "organizationId": "org-001",
  "organization": {
    "id": "org-001",
    "name": "製造商 A",
    "type": "HOST",
    "createdAt": "2025-01-01T00:00:00.000Z",
    "updatedAt": "2025-01-01T00:00:00.000Z"
  }
}
```

---

### 2. 更新個人資料

**端點**: `PUT /api/users/{userId}`

**描述**: 更新使用者的個人資料（姓名、Email、電話、部門）。

**請求 Headers**:

```
Authorization: Bearer <jwt_token>
```

**路徑參數**:

- `userId`: 使用者 ID

**請求參數**:

```json
{
  "username": "john.doe",              // 選填，使用者名稱
  "email": "john.doe@example.com",     // 選填，Email
  "phone": "+886912345678",            // 選填，電話
  "departmentId": "dept-001"           // 選填，部門 ID
}
```

**回應 (200 OK)**:

```json
{
  "id": "user-123",
  "username": "john.doe",
  "email": "john.doe@example.com",
  "phone": "+886912345678",
  "departmentId": "dept-001",
  "department": {
    "id": "dept-001",
    "name": "採購部",
    "organizationId": "org-001"
  },
  "role": "HOST",
  "organizationId": "org-001"
}
```

**錯誤回應**:

- `400 Bad Request`: 參數格式錯誤
```json
{
  "error": "INVALID_EMAIL",
  "message": "Email 格式不正確"
}
```

- `403 Forbidden`: 無權限修改其他使用者資料
```json
{
  "error": "PERMISSION_DENIED",
  "message": "您無權限修改其他使用者的資料"
}
```

- `404 Not Found`: 部門不存在
```json
{
  "error": "DEPARTMENT_NOT_FOUND",
  "message": "指定的部門不存在"
}
```

**前端 Composable 使用**:

```typescript
import { useUser } from '~/composables/useUser'

const { updateProfile } = useUser()

try {
  const updatedUser = await updateProfile({
    username: 'new.username',
    email: 'new.email@example.com',
    phone: '+886987654321',
    departmentId: 'dept-002'
  })
  console.log('資料更新成功', updatedUser)
} catch (error) {
  console.error('資料更新失敗', error)
}
```

---

### 3. 修改密碼

**端點**: `POST /api/users/change-password`

**描述**: 使用者修改自己的密碼，需提供目前密碼驗證。

**請求 Headers**:

```
Authorization: Bearer <jwt_token>
```

**請求參數**:

```json
{
  "currentPassword": "string",    // 必填，目前密碼
  "newPassword": "string"         // 必填，新密碼
}
```

**回應 (200 OK)**:

```json
{
  "message": "密碼修改成功"
}
```

**錯誤回應**:

- `400 Bad Request`: 參數格式錯誤
```json
{
  "error": "INVALID_PASSWORD_FORMAT",
  "message": "密碼長度至少需要 8 個字元"
}
```

- `401 Unauthorized`: 目前密碼錯誤
```json
{
  "error": "INVALID_CURRENT_PASSWORD",
  "message": "目前密碼不正確"
}
```

- `400 Bad Request`: 新密碼與舊密碼相同
```json
{
  "error": "SAME_PASSWORD",
  "message": "新密碼不能與目前密碼相同"
}
```

**前端 Composable 使用**:

```typescript
import { useUser } from '~/composables/useUser'

const { changePassword } = useUser()

try {
  await changePassword('oldPassword123', 'newPassword456')
  console.log('密碼修改成功')
  // 可選：自動登出，要求使用者重新登入
} catch (error) {
  console.error('密碼修改失敗', error)
}
```

---

### 4. 取得使用者列表（管理員功能）

**端點**: `GET /api/users`

**描述**: 取得組織內的使用者列表（僅限管理員）。

**請求 Headers**:

```
Authorization: Bearer <jwt_token>
```

**查詢參數**:

- `page`: 頁碼（預設 1）
- `pageSize`: 每頁筆數（預設 20，最大 100）
- `role`: 角色篩選（HOST/SUPPLIER）
- `departmentId`: 部門篩選
- `search`: 搜尋關鍵字（搜尋姓名、Email）

**請求範例**:

```
GET /api/users?page=1&pageSize=20&role=HOST&search=john
```

**回應 (200 OK)**:

```json
{
  "items": [
    {
      "id": "user-123",
      "username": "john.doe",
      "email": "john.doe@example.com",
      "phone": "+886912345678",
      "departmentId": "dept-001",
      "department": {
        "id": "dept-001",
        "name": "採購部"
      },
      "role": "HOST",
      "organizationId": "org-001"
    }
  ],
  "total": 50,
  "page": 1,
  "pageSize": 20
}
```

**錯誤回應**:

- `403 Forbidden`: 無管理員權限

---

### 5. 取得特定使用者資訊（管理員功能）

**端點**: `GET /api/users/{userId}`

**描述**: 取得指定使用者的詳細資訊（僅限管理員）。

**請求 Headers**:

```
Authorization: Bearer <jwt_token>
```

**路徑參數**:

- `userId`: 使用者 ID

**回應 (200 OK)**:

```json
{
  "id": "user-123",
  "username": "john.doe",
  "email": "john.doe@example.com",
  "phone": "+886912345678",
  "departmentId": "dept-001",
  "department": {
    "id": "dept-001",
    "name": "採購部",
    "organizationId": "org-001"
  },
  "role": "HOST",
  "organizationId": "org-001",
  "organization": {
    "id": "org-001",
    "name": "製造商 A",
    "type": "HOST"
  },
  "createdAt": "2025-01-01T00:00:00.000Z",
  "updatedAt": "2025-01-01T00:00:00.000Z"
}
```

**錯誤回應**:

- `403 Forbidden`: 無管理員權限
- `404 Not Found`: 使用者不存在

---

## 資料驗證規則

### Email 驗證

- 必須符合標準 Email 格式
- 範例：`user@example.com`

### 電話驗證

- 支援國際格式：`+886912345678`
- 支援本地格式：`0912345678`

### 密碼規則

- 最小長度：8 個字元
- 建議包含：大小寫字母、數字、特殊符號
- 不可與使用者名稱相同

---

## 使用範例

### 完整的個人資料編輯流程

```vue
<script setup lang="ts">
import { ref } from 'vue'
import { useUser } from '~/composables/useUser'
import { useAuthStore } from '~/stores/auth'

const authStore = useAuthStore()
const { updateProfile } = useUser()

const form = ref({
  username: authStore.user?.username || '',
  email: authStore.user?.email || '',
  phone: authStore.user?.phone || '',
  departmentId: authStore.user?.departmentId || ''
})

const isLoading = ref(false)
const error = ref<string | null>(null)

const handleSubmit = async () => {
  isLoading.value = true
  error.value = null
  
  try {
    await updateProfile(form.value)
    // 顯示成功訊息
    alert('個人資料更新成功')
  } catch (err: any) {
    error.value = err.message
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <form @submit.prevent="handleSubmit">
    <input v-model="form.username" placeholder="使用者名稱" />
    <input v-model="form.email" type="email" placeholder="Email" />
    <input v-model="form.phone" type="tel" placeholder="電話" />
    <select v-model="form.departmentId">
      <!-- 部門選項 -->
    </select>
    
    <button type="submit" :disabled="isLoading">
      {{ isLoading ? '更新中...' : '儲存' }}
    </button>
    
    <div v-if="error" class="error">{{ error }}</div>
  </form>
</template>
```

### 修改密碼流程

```vue
<script setup lang="ts">
import { ref } from 'vue'
import { useUser } from '~/composables/useUser'
import { useAuth } from '~/composables/useAuth'

const { changePassword } = useUser()
const { logout } = useAuth()

const currentPassword = ref('')
const newPassword = ref('')
const confirmPassword = ref('')
const error = ref<string | null>(null)

const handleChangePassword = async () => {
  if (newPassword.value !== confirmPassword.value) {
    error.value = '新密碼與確認密碼不相符'
    return
  }
  
  try {
    await changePassword(currentPassword.value, newPassword.value)
    alert('密碼修改成功，請重新登入')
    logout()
    navigateTo('/login')
  } catch (err: any) {
    error.value = err.message
  }
}
</script>
```

---

## 權限控制

### 一般使用者

- 可查看自己的個人資料
- 可更新自己的個人資料
- 可修改自己的密碼
- **不可**查看其他使用者資料
- **不可**修改其他使用者資料

### 管理員

- 可查看所有使用者資料
- 可更新所有使用者資料（除了密碼）
- 可取得使用者列表

---

## 測試案例

```typescript
describe('useUser', () => {
  it('should update profile successfully', async () => {
    const { updateProfile } = useUser()
    
    const result = await updateProfile({
      email: 'new.email@example.com',
      phone: '+886987654321'
    })
    
    expect(result.email).toBe('new.email@example.com')
  })
  
  it('should change password successfully', async () => {
    const { changePassword } = useUser()
    
    await expect(
      changePassword('oldPassword', 'newPassword123')
    ).resolves.not.toThrow()
  })
  
  it('should throw error with invalid current password', async () => {
    const { changePassword } = useUser()
    
    await expect(
      changePassword('wrongPassword', 'newPassword123')
    ).rejects.toThrow('INVALID_CURRENT_PASSWORD')
  })
})
```

---

## 相關文件

- [認證與授權 API](./auth.md)
- [部門管理 API](./departments.md)
- [錯誤處理規範](./error-handling.md)
