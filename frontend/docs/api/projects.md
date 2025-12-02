# 專案管理 API (Project Management)

## 概述

專案管理模組涵蓋 SAQ 與衝突資產（Conflict Minerals）兩種類型的專案管理。兩種專案類型的 API 結構相同，僅透過 `type` 參數區分。

## 專案類型

- **SAQ**: 供應商評估問卷 (Supplier Assessment Questionnaire)
- **CONFLICT**: 衝突資產問卷 (Conflict Minerals)

## API 端點

### 1. 取得專案列表

**端點**: `GET /api/projects`

**描述**: 取得專案列表，可依類型、狀態等條件篩選。

**請求 Headers**:

```
Authorization: Bearer <jwt_token>
```

**查詢參數**:

- `type`: 專案類型（必填，`SAQ` 或 `CONFLICT`）
- `status`: 專案狀態篩選（選填）
- `year`: 年份篩選（選填）
- `supplierId`: 供應商 ID 篩選（選填）
- `page`: 頁碼（預設 1）
- `pageSize`: 每頁筆數（預設 20）
- `search`: 搜尋關鍵字（搜尋專案名稱）

**請求範例**:

```
GET /api/projects?type=SAQ&status=IN_PROGRESS&year=2025
GET /api/projects?type=CONFLICT&supplierId=supplier-123
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
      "templateId": "template-001",
      "templateVersion": "v1.2.0",
      "supplierId": "supplier-123",
      "supplier": {
        "id": "supplier-123",
        "name": "供應商 A 公司",
        "organizationId": "org-002"
      },
      "status": "IN_PROGRESS",
      "currentStage": 0,
      "reviewConfig": [
        {
          "stageOrder": 1,
          "departmentId": "dept-001",
          "department": {
            "id": "dept-001",
            "name": "採購部"
          }
        },
        {
          "stageOrder": 2,
          "departmentId": "dept-002",
          "department": {
            "id": "dept-002",
            "name": "品質管理部"
          }
        }
      ],
      "createdAt": "2025-01-01T00:00:00.000Z",
      "updatedAt": "2025-01-15T10:30:00.000Z"
    }
  ]
}
```

**前端 Composable 使用**:

```typescript
import { useProjects } from '~/composables/useProjects'

const { projects, fetchProjects } = useProjects()

// 取得 SAQ 專案列表
await fetchProjects('SAQ')

// 取得衝突資產專案列表
await fetchProjects('CONFLICT')

console.log(projects.value)
```

---

### 2. 取得專案詳情

**端點**: `GET /api/projects/{projectId}`

**描述**: 取得指定專案的完整詳細資訊，包含範本、題目、審核設定等。

**請求 Headers**:

```
Authorization: Bearer <jwt_token>
```

**路徑參數**:

- `projectId`: 專案 ID

**回應 (200 OK)**:

```json
{
  "id": "project-001",
  "name": "2025 SAQ 第一季問卷",
  "year": 2025,
  "type": "SAQ",
  "templateId": "template-001",
  "templateVersion": "v1.2.0",
  "template": {
    "id": "template-001",
    "name": "標準 SAQ 範本",
    "type": "SAQ",
    "version": "v1.2.0",
    "questions": [
      {
        "id": "q-001",
        "text": "貴公司是否有通過 ISO 9001 認證？",
        "type": "BOOLEAN",
        "required": true
      },
      {
        "id": "q-002",
        "text": "請上傳最新的品質管理證書",
        "type": "FILE",
        "required": true,
        "config": {
          "maxFileSize": 10485760,
          "allowedFileTypes": ["pdf", "jpg", "png"]
        }
      }
    ]
  },
  "supplierId": "supplier-123",
  "supplier": {
    "id": "supplier-123",
    "name": "供應商 A 公司",
    "organizationId": "org-002"
  },
  "status": "IN_PROGRESS",
  "currentStage": 0,
  "reviewConfig": [
    {
      "stageOrder": 1,
      "departmentId": "dept-001",
      "department": {
        "id": "dept-001",
        "name": "採購部"
      }
    }
  ],
  "createdBy": "user-001",
  "createdAt": "2025-01-01T00:00:00.000Z",
  "updatedAt": "2025-01-15T10:30:00.000Z"
}
```

**錯誤回應**:

- `403 Forbidden`: 無權限查看此專案
- `404 Not Found`: 專案不存在

**前端 Composable 使用**:

```typescript
import { useProjects } from '~/composables/useProjects'

const { getProject } = useProjects()

const project = await getProject('project-001')
console.log(project)
```

---

### 3. 建立專案

**端點**: `POST /api/projects`

**描述**: 建立新的專案（僅限製造商管理員）。

**請求 Headers**:

```
Authorization: Bearer <jwt_token>
```

**請求參數**:

```json
{
  "name": "string",              // 必填，專案名稱
  "year": "number",              // 必填，年份
  "type": "SAQ | CONFLICT",      // 必填，專案類型
  "templateId": "string",        // 必填，範本 ID
  "supplierId": "string",        // 必填，指派的供應商 ID
  "reviewConfig": [              // 必填，審核流程設定（1-5 階段）
    {
      "stageOrder": 1,
      "departmentId": "string"
    }
  ]
}
```

**請求範例**:

```json
{
  "name": "2025 SAQ 第二季問卷",
  "year": 2025,
  "type": "SAQ",
  "templateId": "template-001",
  "supplierId": "supplier-123",
  "reviewConfig": [
    {
      "stageOrder": 1,
      "departmentId": "dept-001"
    },
    {
      "stageOrder": 2,
      "departmentId": "dept-002"
    }
  ]
}
```

**回應 (201 Created)**:

```json
{
  "id": "project-002",
  "name": "2025 SAQ 第二季問卷",
  "year": 2025,
  "type": "SAQ",
  "templateId": "template-001",
  "templateVersion": "v1.2.0",
  "supplierId": "supplier-123",
  "status": "DRAFT",
  "currentStage": 0,
  "reviewConfig": [
    {
      "stageOrder": 1,
      "departmentId": "dept-001"
    },
    {
      "stageOrder": 2,
      "departmentId": "dept-002"
    }
  ],
  "createdAt": "2025-12-02T05:52:15.355Z",
  "updatedAt": "2025-12-02T05:52:15.355Z"
}
```

**錯誤回應**:

- `400 Bad Request`: 參數驗證失敗
```json
{
  "error": "INVALID_REVIEW_CONFIG",
  "message": "審核階段數量必須在 1-5 之間"
}
```

- `403 Forbidden`: 無權限建立專案（非製造商管理員）
- `404 Not Found`: 範本或供應商不存在

**前端 Composable 使用**:

```typescript
import { useProjects } from '~/composables/useProjects'

const { createProject } = useProjects()

const newProject = await createProject({
  name: '2025 SAQ 第二季問卷',
  year: 2025,
  type: 'SAQ',
  templateId: 'template-001',
  supplierId: 'supplier-123',
  reviewConfig: [
    { stageOrder: 1, departmentId: 'dept-001' },
    { stageOrder: 2, departmentId: 'dept-002' }
  ]
})
```

---

### 4. 更新專案

**端點**: `PUT /api/projects/{projectId}`

**描述**: 更新專案資訊（僅限製造商管理員，且專案狀態為 DRAFT 時）。

**請求 Headers**:

```
Authorization: Bearer <jwt_token>
```

**路徑參數**:

- `projectId`: 專案 ID

**請求參數**:

```json
{
  "name": "string",              // 選填，專案名稱
  "year": "number",              // 選填，年份
  "supplierId": "string",        // 選填，供應商 ID
  "reviewConfig": [              // 選填，審核流程設定
    {
      "stageOrder": 1,
      "departmentId": "string"
    }
  ]
}
```

**回應 (200 OK)**:

```json
{
  "id": "project-001",
  "name": "2025 SAQ 第一季問卷（已修改）",
  "year": 2025,
  "type": "SAQ",
  "templateId": "template-001",
  "templateVersion": "v1.2.0",
  "supplierId": "supplier-456",
  "status": "DRAFT",
  "currentStage": 0,
  "reviewConfig": [...],
  "updatedAt": "2025-12-02T05:52:15.355Z"
}
```

**錯誤回應**:

- `400 Bad Request`: 專案狀態不允許修改
```json
{
  "error": "PROJECT_NOT_EDITABLE",
  "message": "只有草稿狀態的專案可以修改"
}
```

- `403 Forbidden`: 無權限修改專案
- `404 Not Found`: 專案不存在

**前端 Composable 使用**:

```typescript
import { useProjects } from '~/composables/useProjects'

const { updateProject } = useProjects()

const updated = await updateProject('project-001', {
  name: '新的專案名稱',
  supplierId: 'supplier-456'
})
```

---

### 5. 刪除專案

**端點**: `DELETE /api/projects/{projectId}`

**描述**: 刪除專案（僅限製造商管理員，且專案狀態為 DRAFT 時）。

**請求 Headers**:

```
Authorization: Bearer <jwt_token>
```

**路徑參數**:

- `projectId`: 專案 ID

**回應 (200 OK)**:

```json
{
  "message": "專案刪除成功"
}
```

**錯誤回應**:

- `400 Bad Request`: 專案狀態不允許刪除
```json
{
  "error": "PROJECT_NOT_DELETABLE",
  "message": "只有草稿狀態的專案可以刪除"
}
```

- `403 Forbidden`: 無權限刪除專案
- `404 Not Found`: 專案不存在

**前端 Composable 使用**:

```typescript
import { useProjects } from '~/composables/useProjects'

const { deleteProject } = useProjects()

await deleteProject('project-001')
```

---

### 6. 啟動專案

**端點**: `POST /api/projects/{projectId}/activate`

**描述**: 將專案從草稿狀態啟動為進行中，開放給供應商填寫。

**請求 Headers**:

```
Authorization: Bearer <jwt_token>
```

**路徑參數**:

- `projectId`: 專案 ID

**回應 (200 OK)**:

```json
{
  "id": "project-001",
  "status": "IN_PROGRESS",
  "updatedAt": "2025-12-02T05:52:15.355Z"
}
```

**錯誤回應**:

- `400 Bad Request`: 專案狀態不允許啟動
- `403 Forbidden`: 無權限啟動專案

---

## 專案狀態流程

```
DRAFT (草稿)
  ↓ [啟動專案]
IN_PROGRESS (進行中)
  ↓ [供應商提交]
SUBMITTED (已提交)
  ↓ [進入審核]
REVIEWING (審核中)
  ↓ [審核通過]
APPROVED (已核准)

REVIEWING (審核中)
  ↓ [審核退回]
RETURNED (已退回) → IN_PROGRESS (重新開放填寫)
```

---

## 權限控制

### 製造商（HOST）

- 可建立所有類型專案
- 可查看所有專案
- 可更新/刪除 DRAFT 狀態的專案
- 可啟動專案

### 供應商（SUPPLIER）

- 僅能查看被指派給自己的專案
- 可填寫 IN_PROGRESS 狀態的專案
- 可提交專案進入審核

---

## 審核流程設定

### ReviewConfig 結構

```typescript
interface ReviewStageConfig {
  stageOrder: number      // 審核階段順序（1-5）
  departmentId: string    // 負責審核的部門 ID
  department?: Department // 部門資訊（回應時包含）
}
```

### 審核流程範例

```typescript
// 單階段審核
const reviewConfig = [
  { stageOrder: 1, departmentId: 'dept-001' }
]

// 三階段審核
const reviewConfig = [
  { stageOrder: 1, departmentId: 'dept-001' },  // 第一階段：採購部
  { stageOrder: 2, departmentId: 'dept-002' },  // 第二階段：品質管理部
  { stageOrder: 3, departmentId: 'dept-003' }   // 第三階段：研發部
]
```

### 審核流程限制

- 審核階段數量：1-5 階段
- `stageOrder` 必須連續（1, 2, 3...）
- 每個部門在同一專案中只能出現一次

---

## 使用範例

### 完整的專案建立流程

```vue
<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useProjects } from '~/composables/useProjects'
import { useTemplates } from '~/composables/useTemplates'
import { useSuppliers } from '~/composables/useSuppliers'
import { useDepartments } from '~/composables/useDepartments'

const { createProject } = useProjects()
const { templates, fetchTemplates } = useTemplates()
const { suppliers, fetchSuppliers } = useSuppliers()
const { departments, fetchDepartments } = useDepartments()

const form = ref({
  name: '',
  year: new Date().getFullYear(),
  type: 'SAQ',
  templateId: '',
  supplierId: '',
  reviewConfig: [
    { stageOrder: 1, departmentId: '' }
  ]
})

onMounted(async () => {
  await Promise.all([
    fetchTemplates('SAQ'),
    fetchSuppliers(),
    fetchDepartments()
  ])
})

const addReviewStage = () => {
  if (form.value.reviewConfig.length < 5) {
    form.value.reviewConfig.push({
      stageOrder: form.value.reviewConfig.length + 1,
      departmentId: ''
    })
  }
}

const removeReviewStage = (index: number) => {
  form.value.reviewConfig.splice(index, 1)
  // 重新排序 stageOrder
  form.value.reviewConfig.forEach((stage, idx) => {
    stage.stageOrder = idx + 1
  })
}

const handleSubmit = async () => {
  try {
    const project = await createProject(form.value)
    alert('專案建立成功')
    navigateTo(`/saq/projects/${project.id}`)
  } catch (error) {
    console.error(error)
  }
}
</script>

<template>
  <form @submit.prevent="handleSubmit">
    <input v-model="form.name" placeholder="專案名稱" required />
    
    <input v-model.number="form.year" type="number" placeholder="年份" required />
    
    <select v-model="form.type" required>
      <option value="SAQ">SAQ</option>
      <option value="CONFLICT">衝突資產</option>
    </select>
    
    <select v-model="form.templateId" required>
      <option value="">選擇範本</option>
      <option v-for="t in templates" :key="t.id" :value="t.id">
        {{ t.name }}
      </option>
    </select>
    
    <select v-model="form.supplierId" required>
      <option value="">選擇供應商</option>
      <option v-for="s in suppliers" :key="s.id" :value="s.id">
        {{ s.name }}
      </option>
    </select>
    
    <div>
      <h3>審核流程設定</h3>
      <div v-for="(stage, index) in form.reviewConfig" :key="index">
        <label>階段 {{ stage.stageOrder }}</label>
        <select v-model="stage.departmentId" required>
          <option value="">選擇審核部門</option>
          <option v-for="d in departments" :key="d.id" :value="d.id">
            {{ d.name }}
          </option>
        </select>
        <button type="button" @click="removeReviewStage(index)">移除</button>
      </div>
      <button type="button" @click="addReviewStage">新增審核階段</button>
    </div>
    
    <button type="submit">建立專案</button>
  </form>
</template>
```

---

### 專案列表頁面

```vue
<script setup lang="ts">
import { useProjects } from '~/composables/useProjects'

const { projects, fetchProjects, deleteProject } = useProjects()
const projectType = ref<'SAQ' | 'CONFLICT'>('SAQ')

onMounted(async () => {
  await fetchProjects(projectType.value)
})

watch(projectType, async (newType) => {
  await fetchProjects(newType)
})

const handleDelete = async (id: string) => {
  if (!confirm('確定要刪除此專案嗎？')) return
  
  try {
    await deleteProject(id)
    await fetchProjects(projectType.value)
  } catch (error) {
    console.error(error)
  }
}
</script>

<template>
  <div>
    <h1>專案管理</h1>
    
    <div>
      <button @click="projectType = 'SAQ'">SAQ</button>
      <button @click="projectType = 'CONFLICT'">衝突資產</button>
    </div>
    
    <table>
      <thead>
        <tr>
          <th>專案名稱</th>
          <th>年份</th>
          <th>供應商</th>
          <th>狀態</th>
          <th>操作</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="p in projects" :key="p.id">
          <td>{{ p.name }}</td>
          <td>{{ p.year }}</td>
          <td>{{ p.supplier?.name }}</td>
          <td>{{ p.status }}</td>
          <td>
            <NuxtLink :to="`/${projectType.toLowerCase()}/projects/${p.id}`">
              查看
            </NuxtLink>
            <button v-if="p.status === 'DRAFT'" @click="handleDelete(p.id)">
              刪除
            </button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
```

---

## 測試案例

```typescript
describe('useProjects', () => {
  it('should fetch SAQ projects', async () => {
    const { fetchProjects, projects } = useProjects()
    
    await fetchProjects('SAQ')
    
    expect(projects.value).toBeInstanceOf(Array)
    expect(projects.value.every(p => p.type === 'SAQ')).toBe(true)
  })
  
  it('should create project successfully', async () => {
    const { createProject } = useProjects()
    
    const result = await createProject({
      name: '測試專案',
      year: 2025,
      type: 'SAQ',
      templateId: 'template-001',
      supplierId: 'supplier-123',
      reviewConfig: [
        { stageOrder: 1, departmentId: 'dept-001' }
      ]
    })
    
    expect(result.id).toBeDefined()
    expect(result.name).toBe('測試專案')
  })
  
  it('should validate review config', async () => {
    const { createProject } = useProjects()
    
    await expect(createProject({
      name: '測試專案',
      year: 2025,
      type: 'SAQ',
      templateId: 'template-001',
      supplierId: 'supplier-123',
      reviewConfig: [] // 空的審核設定
    })).rejects.toThrow()
  })
})
```

---

## 相關文件

- [範本管理 API](./templates.md)
- [問卷填寫與答案 API](./answers.md)
- [多階段審核 API](./reviews.md)
- [供應商管理 API](./suppliers.md)
- [部門管理 API](./departments.md)
