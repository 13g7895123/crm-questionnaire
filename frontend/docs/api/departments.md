# 部門管理 API (Department Management)

## 概述

部門管理模組提供組織內部門的 CRUD 操作，供管理員維護部門清單，並供使用者在個人資料中選擇所屬部門。

## API 端點

### 1. 取得部門列表

**端點**: `GET /api/departments`

**描述**: 取得組織內所有部門列表。

**請求 Headers**:

```
Authorization: Bearer <jwt_token>
```

**查詢參數**:

- `organizationId`: 組織 ID（選填，預設為目前使用者所屬組織）
- `search`: 搜尋關鍵字（搜尋部門名稱）

**請求範例**:

```
GET /api/departments?search=採購
```

**回應 (200 OK)**:

```json
{
  "data": [
    {
      "id": "dept-001",
      "name": "採購部",
      "organizationId": "org-001",
      "createdAt": "2025-01-01T00:00:00.000Z",
      "updatedAt": "2025-01-01T00:00:00.000Z"
    },
    {
      "id": "dept-002",
      "name": "品質管理部",
      "organizationId": "org-001",
      "createdAt": "2025-01-01T00:00:00.000Z",
      "updatedAt": "2025-01-01T00:00:00.000Z"
    },
    {
      "id": "dept-003",
      "name": "研發部",
      "organizationId": "org-001",
      "createdAt": "2025-01-01T00:00:00.000Z",
      "updatedAt": "2025-01-01T00:00:00.000Z"
    }
  ]
}
```

**前端 Composable 使用**:

```typescript
import { useDepartments } from '~/composables/useDepartments'

const { departments, fetchDepartments } = useDepartments()

// 取得部門列表
await fetchDepartments()

// departments.value 會包含所有部門
console.log(departments.value)
```

---

### 2. 建立部門（管理員功能）

**端點**: `POST /api/departments`

**描述**: 建立新的部門（僅限管理員）。

**請求 Headers**:

```
Authorization: Bearer <jwt_token>
```

**請求參數**:

```json
{
  "name": "string",              // 必填，部門名稱
  "organizationId": "string"     // 選填，組織 ID（預設為目前使用者所屬組織）
}
```

**回應 (201 Created)**:

```json
{
  "id": "dept-004",
  "name": "法務部",
  "organizationId": "org-001",
  "createdAt": "2025-12-02T05:52:15.355Z",
  "updatedAt": "2025-12-02T05:52:15.355Z"
}
```

**錯誤回應**:

- `400 Bad Request`: 部門名稱重複
```json
{
  "error": "DEPARTMENT_NAME_EXISTS",
  "message": "部門名稱已存在"
}
```

- `403 Forbidden`: 無管理員權限
```json
{
  "error": "PERMISSION_DENIED",
  "message": "您無權限建立部門"
}
```

**前端 Composable 使用**:

```typescript
import { useDepartments } from '~/composables/useDepartments'

const { createDepartment } = useDepartments()

try {
  const newDepartment = await createDepartment('法務部')
  console.log('部門建立成功', newDepartment)
} catch (error) {
  console.error('部門建立失敗', error)
}
```

---

### 3. 更新部門（管理員功能）

**端點**: `PUT /api/departments/{departmentId}`

**描述**: 更新指定部門的資訊（僅限管理員）。

**請求 Headers**:

```
Authorization: Bearer <jwt_token>
```

**路徑參數**:

- `departmentId`: 部門 ID

**請求參數**:

```json
{
  "name": "string"    // 必填，部門新名稱
}
```

**回應 (200 OK)**:

```json
{
  "id": "dept-001",
  "name": "採購管理部",
  "organizationId": "org-001",
  "createdAt": "2025-01-01T00:00:00.000Z",
  "updatedAt": "2025-12-02T05:52:15.355Z"
}
```

**錯誤回應**:

- `400 Bad Request`: 部門名稱重複
```json
{
  "error": "DEPARTMENT_NAME_EXISTS",
  "message": "部門名稱已存在"
}
```

- `403 Forbidden`: 無管理員權限
- `404 Not Found`: 部門不存在
```json
{
  "error": "DEPARTMENT_NOT_FOUND",
  "message": "指定的部門不存在"
}
```

**前端 Composable 使用**:

```typescript
import { useDepartments } from '~/composables/useDepartments'

const { updateDepartment } = useDepartments()

try {
  const updated = await updateDepartment('dept-001', '採購管理部')
  console.log('部門更新成功', updated)
} catch (error) {
  console.error('部門更新失敗', error)
}
```

---

### 4. 刪除部門（管理員功能）

**端點**: `DELETE /api/departments/{departmentId}`

**描述**: 刪除指定部門（僅限管理員）。若部門下有使用者，則無法刪除。

**請求 Headers**:

```
Authorization: Bearer <jwt_token>
```

**路徑參數**:

- `departmentId`: 部門 ID

**回應 (200 OK)**:

```json
{
  "message": "部門刪除成功"
}
```

**錯誤回應**:

- `400 Bad Request`: 部門下有使用者
```json
{
  "error": "DEPARTMENT_IN_USE",
  "message": "此部門下尚有使用者，無法刪除",
  "details": {
    "userCount": 5
  }
}
```

- `403 Forbidden`: 無管理員權限
- `404 Not Found`: 部門不存在

**前端 Composable 使用**:

```typescript
import { useDepartments } from '~/composables/useDepartments'

const { deleteDepartment } = useDepartments()

try {
  await deleteDepartment('dept-001')
  console.log('部門刪除成功')
} catch (error) {
  console.error('部門刪除失敗', error)
}
```

---

### 5. 取得部門詳情

**端點**: `GET /api/departments/{departmentId}`

**描述**: 取得指定部門的詳細資訊。

**請求 Headers**:

```
Authorization: Bearer <jwt_token>
```

**路徑參數**:

- `departmentId`: 部門 ID

**回應 (200 OK)**:

```json
{
  "id": "dept-001",
  "name": "採購部",
  "organizationId": "org-001",
  "organization": {
    "id": "org-001",
    "name": "製造商 A",
    "type": "HOST"
  },
  "userCount": 12,
  "createdAt": "2025-01-01T00:00:00.000Z",
  "updatedAt": "2025-01-01T00:00:00.000Z"
}
```

**錯誤回應**:

- `404 Not Found`: 部門不存在

---

## 使用場景

### 1. 部門列表展示

```vue
<script setup lang="ts">
import { useDepartments } from '~/composables/useDepartments'

const { departments, fetchDepartments } = useDepartments()

onMounted(async () => {
  await fetchDepartments()
})
</script>

<template>
  <div>
    <h2>部門列表</h2>
    <ul>
      <li v-for="dept in departments" :key="dept.id">
        {{ dept.name }}
      </li>
    </ul>
  </div>
</template>
```

---

### 2. 部門管理介面（CRUD）

```vue
<script setup lang="ts">
import { ref } from 'vue'
import { useDepartments } from '~/composables/useDepartments'

const { 
  departments, 
  fetchDepartments, 
  createDepartment, 
  updateDepartment, 
  deleteDepartment 
} = useDepartments()

const newDepartmentName = ref('')
const editingDepartment = ref<{ id: string, name: string } | null>(null)

onMounted(async () => {
  await fetchDepartments()
})

const handleCreate = async () => {
  try {
    await createDepartment(newDepartmentName.value)
    newDepartmentName.value = ''
    await fetchDepartments() // 重新載入列表
  } catch (error) {
    console.error(error)
  }
}

const handleUpdate = async () => {
  if (!editingDepartment.value) return
  
  try {
    await updateDepartment(
      editingDepartment.value.id, 
      editingDepartment.value.name
    )
    editingDepartment.value = null
    await fetchDepartments()
  } catch (error) {
    console.error(error)
  }
}

const handleDelete = async (id: string) => {
  if (!confirm('確定要刪除此部門嗎？')) return
  
  try {
    await deleteDepartment(id)
    await fetchDepartments()
  } catch (error) {
    console.error(error)
  }
}
</script>

<template>
  <div>
    <!-- 新增部門 -->
    <div>
      <input v-model="newDepartmentName" placeholder="部門名稱" />
      <button @click="handleCreate">新增部門</button>
    </div>
    
    <!-- 部門列表 -->
    <table>
      <thead>
        <tr>
          <th>部門名稱</th>
          <th>操作</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="dept in departments" :key="dept.id">
          <td>{{ dept.name }}</td>
          <td>
            <button @click="editingDepartment = { ...dept }">編輯</button>
            <button @click="handleDelete(dept.id)">刪除</button>
          </td>
        </tr>
      </tbody>
    </table>
    
    <!-- 編輯對話框 -->
    <div v-if="editingDepartment">
      <h3>編輯部門</h3>
      <input v-model="editingDepartment.name" />
      <button @click="handleUpdate">儲存</button>
      <button @click="editingDepartment = null">取消</button>
    </div>
  </div>
</template>
```

---

### 3. 在個人資料表單中使用部門選單

```vue
<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useDepartments } from '~/composables/useDepartments'
import { useAuthStore } from '~/stores/auth'

const authStore = useAuthStore()
const { departments, fetchDepartments } = useDepartments()

const selectedDepartmentId = ref(authStore.user?.departmentId || '')

onMounted(async () => {
  await fetchDepartments()
})
</script>

<template>
  <div>
    <label>部門</label>
    <select v-model="selectedDepartmentId">
      <option value="">請選擇部門</option>
      <option 
        v-for="dept in departments" 
        :key="dept.id" 
        :value="dept.id"
      >
        {{ dept.name }}
      </option>
    </select>
  </div>
</template>
```

---

## 業務規則

### 部門名稱規則

- 部門名稱在同一組織內必須唯一
- 部門名稱不可為空
- 部門名稱長度限制：2-50 字元

### 刪除限制

- 部門下若有使用者，則無法刪除
- 建議：提供「停用」功能作為替代方案

### 權限控制

- **一般使用者**: 僅能查看部門列表
- **管理員**: 可執行完整的 CRUD 操作

---

## 審核流程中的部門角色

部門在多階段審核流程中扮演重要角色：

```typescript
// 專案的審核流程設定
interface ReviewStageConfig {
  stageOrder: number      // 審核階段順序（1, 2, 3...）
  departmentId: string    // 負責審核的部門 ID
  department?: Department // 部門資訊
}

// 範例：設定三階段審核流程
const reviewConfig: ReviewStageConfig[] = [
  { stageOrder: 1, departmentId: 'dept-001' },  // 第一階段：採購部
  { stageOrder: 2, departmentId: 'dept-002' },  // 第二階段：品質管理部
  { stageOrder: 3, departmentId: 'dept-003' }   // 第三階段：研發部
]
```

---

## 測試案例

```typescript
describe('useDepartments', () => {
  it('should fetch departments successfully', async () => {
    const { fetchDepartments, departments } = useDepartments()
    
    await fetchDepartments()
    
    expect(departments.value).toBeInstanceOf(Array)
    expect(departments.value.length).toBeGreaterThan(0)
  })
  
  it('should create department successfully', async () => {
    const { createDepartment } = useDepartments()
    
    const result = await createDepartment('測試部門')
    
    expect(result.name).toBe('測試部門')
    expect(result.id).toBeDefined()
  })
  
  it('should update department successfully', async () => {
    const { updateDepartment } = useDepartments()
    
    const result = await updateDepartment('dept-001', '新部門名稱')
    
    expect(result.name).toBe('新部門名稱')
  })
  
  it('should delete department successfully', async () => {
    const { deleteDepartment } = useDepartments()
    
    await expect(deleteDepartment('dept-001')).resolves.not.toThrow()
  })
  
  it('should throw error when deleting department with users', async () => {
    const { deleteDepartment } = useDepartments()
    
    await expect(deleteDepartment('dept-with-users'))
      .rejects.toThrow('DEPARTMENT_IN_USE')
  })
})
```

---

## 資料快取策略

部門資料相對穩定，建議實作快取策略：

```typescript
// 在 Pinia Store 中實作快取
import { defineStore } from 'pinia'
import { useDepartments } from '~/composables/useDepartments'

export const useDepartmentStore = defineStore('departments', () => {
  const departments = ref<Department[]>([])
  const lastFetchTime = ref<number>(0)
  const CACHE_DURATION = 5 * 60 * 1000 // 5 分鐘

  const fetchDepartments = async (forceRefresh = false) => {
    const now = Date.now()
    
    if (!forceRefresh && departments.value.length > 0 && 
        now - lastFetchTime.value < CACHE_DURATION) {
      return departments.value
    }
    
    const { fetchDepartments: fetch } = useDepartments()
    const data = await fetch()
    
    departments.value = data
    lastFetchTime.value = now
    
    return data
  }

  return {
    departments,
    fetchDepartments
  }
})
```

---

## 相關文件

- [使用者管理 API](./users.md)
- [多階段審核 API](./reviews.md)
- [錯誤處理規範](./error-handling.md)
- [資料模型](./data-models.md)
