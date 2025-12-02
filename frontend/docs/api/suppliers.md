# 供應商管理 API (Supplier Management)

## 概述

供應商管理模組提供供應商資訊的查詢功能，用於專案指派時選擇供應商。

## API 端點

### 1. 取得供應商列表

**端點**: `GET /api/suppliers`

**描述**: 取得所有供應商列表（僅限製造商）。

**請求 Headers**:

```
Authorization: Bearer <jwt_token>
```

**查詢參數**:

- `search`: 搜尋關鍵字（搜尋供應商名稱）
- `page`: 頁碼（預設 1）
- `pageSize`: 每頁筆數（預設 20）
- `active`: 是否僅顯示活躍供應商（`true`/`false`，預設 `true`）

**請求範例**:

```
GET /api/suppliers
GET /api/suppliers?search=電子&page=1&pageSize=10
```

**回應 (200 OK)**:

```json
{
  "data": [
    {
      "id": "supplier-001",
      "name": "供應商 A 公司",
      "organizationId": "org-002",
      "organization": {
        "id": "org-002",
        "name": "供應商 A 公司",
        "type": "SUPPLIER"
      },
      "contactName": "王經理",
      "contactEmail": "wang@supplier-a.com",
      "contactPhone": "+886912345678",
      "industry": "電子業",
      "active": true,
      "createdAt": "2024-01-01T00:00:00.000Z",
      "updatedAt": "2025-01-15T00:00:00.000Z"
    },
    {
      "id": "supplier-002",
      "name": "供應商 B 公司",
      "organizationId": "org-003",
      "organization": {
        "id": "org-003",
        "name": "供應商 B 公司",
        "type": "SUPPLIER"
      },
      "contactName": "李經理",
      "contactEmail": "lee@supplier-b.com",
      "contactPhone": "+886987654321",
      "industry": "機械業",
      "active": true,
      "createdAt": "2024-02-01T00:00:00.000Z",
      "updatedAt": "2025-01-20T00:00:00.000Z"
    }
  ]
}
```

**錯誤回應**:

- `403 Forbidden`: 無權限查看供應商列表（非製造商）

**前端 Composable 使用**:

```typescript
import { useSuppliers } from '~/composables/useSuppliers'

const { suppliers, fetchSuppliers } = useSuppliers()

// 取得供應商列表
await fetchSuppliers()

console.log(suppliers.value)
```

---

### 2. 取得供應商詳情

**端點**: `GET /api/suppliers/{supplierId}`

**描述**: 取得指定供應商的詳細資訊。

**請求 Headers**:

```
Authorization: Bearer <jwt_token>
```

**路徑參數**:

- `supplierId`: 供應商 ID

**回應 (200 OK)**:

```json
{
  "id": "supplier-001",
  "name": "供應商 A 公司",
  "organizationId": "org-002",
  "organization": {
    "id": "org-002",
    "name": "供應商 A 公司",
    "type": "SUPPLIER",
    "createdAt": "2024-01-01T00:00:00.000Z"
  },
  "contactName": "王經理",
  "contactEmail": "wang@supplier-a.com",
  "contactPhone": "+886912345678",
  "address": "台北市信義區信義路五段7號",
  "industry": "電子業",
  "employeeCount": 150,
  "certifications": ["ISO 9001", "ISO 14001"],
  "active": true,
  "projectCount": {
    "total": 12,
    "inProgress": 2,
    "completed": 10
  },
  "createdAt": "2024-01-01T00:00:00.000Z",
  "updatedAt": "2025-01-15T00:00:00.000Z"
}
```

**錯誤回應**:

- `403 Forbidden`: 無權限查看供應商詳情
- `404 Not Found`: 供應商不存在

---

### 3. 取得供應商的專案列表

**端點**: `GET /api/suppliers/{supplierId}/projects`

**描述**: 取得指定供應商的專案列表（僅限製造商）。

**請求 Headers**:

```
Authorization: Bearer <jwt_token>
```

**路徑參數**:

- `supplierId`: 供應商 ID

**查詢參數**:

- `type`: 專案類型（選填，`SAQ` 或 `CONFLICT`）
- `status`: 專案狀態（選填）
- `year`: 年份（選填）

**請求範例**:

```
GET /api/suppliers/supplier-001/projects?type=SAQ&year=2025
```

**回應 (200 OK)**:

```json
{
  "data": [
    {
      "id": "project-001",
      "name": "2025 SAQ 第一季問卷",
      "year": 2025,
      "type": "SAQ",
      "status": "APPROVED",
      "createdAt": "2025-01-01T00:00:00.000Z",
      "updatedAt": "2025-02-15T00:00:00.000Z"
    }
  ]
}
```

---

### 4. 建立供應商（管理員功能）

**端點**: `POST /api/suppliers`

**描述**: 建立新的供應商（僅限製造商管理員）。

**請求 Headers**:

```
Authorization: Bearer <jwt_token>
```

**請求參數**:

```json
{
  "name": "string",              // 必填，供應商名稱
  "contactName": "string",       // 必填，聯絡人姓名
  "contactEmail": "string",      // 必填，聯絡人 Email
  "contactPhone": "string",      // 必填，聯絡人電話
  "address": "string",           // 選填，地址
  "industry": "string",          // 選填，產業類別
  "employeeCount": "number",     // 選填，員工人數
  "certifications": ["string"]   // 選填，認證列表
}
```

**請求範例**:

```json
{
  "name": "供應商 C 公司",
  "contactName": "陳經理",
  "contactEmail": "chen@supplier-c.com",
  "contactPhone": "+886923456789",
  "address": "新竹市東區光復路二段101號",
  "industry": "化工業",
  "employeeCount": 80,
  "certifications": ["ISO 9001"]
}
```

**回應 (201 Created)**:

```json
{
  "id": "supplier-003",
  "name": "供應商 C 公司",
  "organizationId": "org-004",
  "contactName": "陳經理",
  "contactEmail": "chen@supplier-c.com",
  "contactPhone": "+886923456789",
  "active": true,
  "createdAt": "2025-12-02T05:52:15.355Z",
  "updatedAt": "2025-12-02T05:52:15.355Z"
}
```

**錯誤回應**:

- `400 Bad Request`: 參數驗證失敗
- `403 Forbidden`: 無權限建立供應商

---

### 5. 更新供應商（管理員功能）

**端點**: `PUT /api/suppliers/{supplierId}`

**描述**: 更新供應商資訊（僅限製造商管理員）。

**請求 Headers**:

```
Authorization: Bearer <jwt_token>
```

**路徑參數**:

- `supplierId`: 供應商 ID

**請求參數**:

```json
{
  "name": "string",              // 選填
  "contactName": "string",       // 選填
  "contactEmail": "string",      // 選填
  "contactPhone": "string",      // 選填
  "address": "string",           // 選填
  "industry": "string",          // 選填
  "employeeCount": "number",     // 選填
  "certifications": ["string"],  // 選填
  "active": "boolean"            // 選填，啟用/停用
}
```

**回應 (200 OK)**:

```json
{
  "id": "supplier-001",
  "name": "供應商 A 公司（已更新）",
  "contactName": "新聯絡人",
  "active": true,
  "updatedAt": "2025-12-02T05:52:15.355Z"
}
```

**錯誤回應**:

- `403 Forbidden`: 無權限更新供應商
- `404 Not Found`: 供應商不存在

---

### 6. 停用/啟用供應商（管理員功能）

**端點**: `PATCH /api/suppliers/{supplierId}/status`

**描述**: 停用或啟用供應商（僅限製造商管理員）。

**請求 Headers**:

```
Authorization: Bearer <jwt_token>
```

**路徑參數**:

- `supplierId`: 供應商 ID

**請求參數**:

```json
{
  "active": "boolean"    // 必填，true=啟用，false=停用
}
```

**回應 (200 OK)**:

```json
{
  "id": "supplier-001",
  "active": false,
  "message": "供應商已停用"
}
```

**錯誤回應**:

- `403 Forbidden`: 無權限修改供應商狀態
- `404 Not Found`: 供應商不存在

---

## 資料結構

### Supplier (供應商)

```typescript
interface Supplier {
  id: string
  name: string
  organizationId: string
  organization?: Organization
  contactName: string
  contactEmail: string
  contactPhone: string
  address?: string
  industry?: string
  employeeCount?: number
  certifications?: string[]
  active: boolean
  createdAt: string
  updatedAt: string
}
```

---

## 使用範例

### 供應商選擇下拉選單

```vue
<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useSuppliers } from '~/composables/useSuppliers'

const { suppliers, fetchSuppliers } = useSuppliers()
const selectedSupplierId = ref('')

onMounted(async () => {
  await fetchSuppliers()
})
</script>

<template>
  <div>
    <label>選擇供應商</label>
    <select v-model="selectedSupplierId" required>
      <option value="">請選擇供應商</option>
      <option 
        v-for="supplier in suppliers" 
        :key="supplier.id" 
        :value="supplier.id"
      >
        {{ supplier.name }}
      </option>
    </select>
  </div>
</template>
```

---

### 供應商管理列表

```vue
<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useSuppliers } from '~/composables/useSuppliers'

const { suppliers, fetchSuppliers } = useSuppliers()
const searchKeyword = ref('')

onMounted(async () => {
  await fetchSuppliers()
})

const filteredSuppliers = computed(() => {
  if (!searchKeyword.value) return suppliers.value
  
  return suppliers.value.filter(s => 
    s.name.toLowerCase().includes(searchKeyword.value.toLowerCase())
  )
})
</script>

<template>
  <div>
    <h1>供應商管理</h1>
    
    <div>
      <input 
        v-model="searchKeyword" 
        placeholder="搜尋供應商名稱"
        type="search"
      />
      <NuxtLink to="/admin/suppliers/new">
        <button>新增供應商</button>
      </NuxtLink>
    </div>
    
    <table>
      <thead>
        <tr>
          <th>供應商名稱</th>
          <th>聯絡人</th>
          <th>Email</th>
          <th>電話</th>
          <th>產業</th>
          <th>狀態</th>
          <th>操作</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="s in filteredSuppliers" :key="s.id">
          <td>{{ s.name }}</td>
          <td>{{ s.contactName }}</td>
          <td>{{ s.contactEmail }}</td>
          <td>{{ s.contactPhone }}</td>
          <td>{{ s.industry }}</td>
          <td>
            <span :class="{ active: s.active, inactive: !s.active }">
              {{ s.active ? '啟用' : '停用' }}
            </span>
          </td>
          <td>
            <NuxtLink :to="`/admin/suppliers/${s.id}`">
              查看
            </NuxtLink>
            <NuxtLink :to="`/admin/suppliers/${s.id}/edit`">
              編輯
            </NuxtLink>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
```

---

### 供應商詳情頁面

```vue
<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const supplierId = route.params.id as string

const supplier = ref(null)
const projects = ref([])

onMounted(async () => {
  // 取得供應商詳情
  const response = await fetch(`/api/suppliers/${supplierId}`, {
    headers: {
      'Authorization': `Bearer ${token}`
    }
  })
  supplier.value = await response.json()
  
  // 取得供應商的專案列表
  const projectsResponse = await fetch(`/api/suppliers/${supplierId}/projects`, {
    headers: {
      'Authorization': `Bearer ${token}`
    }
  })
  const projectsData = await projectsResponse.json()
  projects.value = projectsData.data
})
</script>

<template>
  <div v-if="supplier">
    <h1>{{ supplier.name }}</h1>
    
    <div class="info">
      <h2>基本資訊</h2>
      <p><strong>聯絡人：</strong>{{ supplier.contactName }}</p>
      <p><strong>Email：</strong>{{ supplier.contactEmail }}</p>
      <p><strong>電話：</strong>{{ supplier.contactPhone }}</p>
      <p><strong>地址：</strong>{{ supplier.address }}</p>
      <p><strong>產業：</strong>{{ supplier.industry }}</p>
      <p><strong>員工人數：</strong>{{ supplier.employeeCount }}</p>
      <p><strong>認證：</strong>{{ supplier.certifications?.join(', ') }}</p>
    </div>
    
    <div class="stats">
      <h2>專案統計</h2>
      <p><strong>總專案數：</strong>{{ supplier.projectCount?.total }}</p>
      <p><strong>進行中：</strong>{{ supplier.projectCount?.inProgress }}</p>
      <p><strong>已完成：</strong>{{ supplier.projectCount?.completed }}</p>
    </div>
    
    <div class="projects">
      <h2>專案列表</h2>
      <table>
        <thead>
          <tr>
            <th>專案名稱</th>
            <th>類型</th>
            <th>年份</th>
            <th>狀態</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in projects" :key="p.id">
            <td>{{ p.name }}</td>
            <td>{{ p.type }}</td>
            <td>{{ p.year }}</td>
            <td>{{ p.status }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
```

---

## 業務規則

### 供應商狀態

- **active = true**: 供應商可被指派至新專案
- **active = false**: 供應商已停用，無法被指派至新專案，但既有專案不受影響

### 供應商刪除限制

- 若供應商已有專案，則無法刪除
- 建議使用「停用」功能代替刪除

---

## 測試案例

```typescript
describe('useSuppliers', () => {
  it('should fetch suppliers successfully', async () => {
    const { fetchSuppliers, suppliers } = useSuppliers()
    
    await fetchSuppliers()
    
    expect(suppliers.value).toBeInstanceOf(Array)
  })
  
  it('should filter suppliers by search keyword', async () => {
    const { fetchSuppliers, suppliers } = useSuppliers()
    
    await fetchSuppliers()
    
    const filtered = suppliers.value.filter(s => 
      s.name.includes('電子')
    )
    
    expect(filtered.length).toBeGreaterThan(0)
  })
})
```

---

## 相關文件

- [專案管理 API](./projects.md)
- [使用者管理 API](./users.md)
- [資料模型](./data-models.md)
