# 範本管理 API (Template Management)

## 概述

範本管理模組負責管理問卷範本，包含範本的 CRUD 操作、版本控制、題目管理等功能。範本分為 SAQ 與衝突資產兩種類型。

## API 端點

### 1. 取得範本列表

**端點**: `GET /api/templates`

**描述**: 取得範本列表，可依類型篩選。

**請求 Headers**:

```
Authorization: Bearer <jwt_token>
```

**查詢參數**:

- `type`: 範本類型（必填，`SAQ` 或 `CONFLICT`）
- `search`: 搜尋關鍵字（搜尋範本名稱）
- `page`: 頁碼（預設 1）
- `pageSize`: 每頁筆數（預設 20）

**請求範例**:

```
GET /api/templates?type=SAQ
GET /api/templates?type=CONFLICT&search=2025
```

**回應 (200 OK)**:

```json
{
  "data": [
    {
      "id": "template-001",
      "name": "標準 SAQ 範本",
      "type": "SAQ",
      "latestVersion": "v1.2.0",
      "versions": [
        {
          "version": "v1.2.0",
          "createdAt": "2025-01-15T00:00:00.000Z",
          "questionCount": 25
        },
        {
          "version": "v1.1.0",
          "createdAt": "2024-12-01T00:00:00.000Z",
          "questionCount": 22
        }
      ],
      "createdAt": "2024-01-01T00:00:00.000Z",
      "updatedAt": "2025-01-15T00:00:00.000Z"
    }
  ]
}
```

**前端 Composable 使用**:

```typescript
import { useTemplates } from '~/composables/useTemplates'

const { templates, fetchTemplates } = useTemplates()

// 取得 SAQ 範本列表
await fetchTemplates('SAQ')

// 取得衝突資產範本列表
await fetchTemplates('CONFLICT')

console.log(templates.value)
```

---

### 2. 取得範本詳情

**端點**: `GET /api/templates/{templateId}`

**描述**: 取得指定範本的完整資訊，包含所有版本與題目。

**請求 Headers**:

```
Authorization: Bearer <jwt_token>
```

**路徑參數**:

- `templateId`: 範本 ID

**查詢參數**:

- `version`: 指定版本號（選填，預設為最新版本）

**請求範例**:

```
GET /api/templates/template-001
GET /api/templates/template-001?version=v1.1.0
```

**回應 (200 OK)**:

```json
{
  "id": "template-001",
  "name": "標準 SAQ 範本",
  "type": "SAQ",
  "latestVersion": "v1.2.0",
  "versions": [
    {
      "version": "v1.2.0",
      "questions": [
        {
          "id": "q-001",
          "text": "貴公司是否有通過 ISO 9001 認證？",
          "type": "BOOLEAN",
          "required": true,
          "options": null,
          "config": null
        },
        {
          "id": "q-002",
          "text": "請選擇貴公司的主要產業",
          "type": "SINGLE_CHOICE",
          "required": true,
          "options": ["電子業", "機械業", "化工業", "其他"],
          "config": null
        },
        {
          "id": "q-003",
          "text": "請上傳最新的品質管理證書",
          "type": "FILE",
          "required": true,
          "options": null,
          "config": {
            "maxFileSize": 10485760,
            "allowedFileTypes": ["pdf", "jpg", "png"]
          }
        },
        {
          "id": "q-004",
          "text": "請評估貴公司的品質管理成熟度",
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
      "createdAt": "2025-01-15T00:00:00.000Z"
    }
  ],
  "createdAt": "2024-01-01T00:00:00.000Z",
  "updatedAt": "2025-01-15T00:00:00.000Z"
}
```

**錯誤回應**:

- `404 Not Found`: 範本不存在
- `404 Not Found`: 指定版本不存在

**前端 Composable 使用**:

```typescript
import { useTemplates } from '~/composables/useTemplates'

const { getTemplate } = useTemplates()

// 取得最新版本
const template = await getTemplate('template-001')

// 取得特定版本
const template = await getTemplate('template-001?version=v1.1.0')
```

---

### 3. 建立範本

**端點**: `POST /api/templates`

**描述**: 建立新的範本（僅限製造商管理員）。

**請求 Headers**:

```
Authorization: Bearer <jwt_token>
```

**請求參數**:

```json
{
  "name": "string",              // 必填，範本名稱
  "type": "SAQ | CONFLICT",      // 必填，範本類型
  "questions": [                 // 選填，初始題目（可為空陣列）
    {
      "text": "string",
      "type": "QUESTION_TYPE",
      "required": "boolean",
      "options": ["string"],     // 選填，選項（單選/多選題必填）
      "config": {}               // 選填，題目設定
    }
  ]
}
```

**題目類型 (QuestionType)**:

- `TEXT`: 簡答題
- `NUMBER`: 數字題
- `DATE`: 日期題
- `BOOLEAN`: 是非題
- `SINGLE_CHOICE`: 單選題
- `MULTI_CHOICE`: 多選題
- `FILE`: 檔案上傳
- `RATING`: 評分量表

**請求範例**:

```json
{
  "name": "2025 供應商評估範本",
  "type": "SAQ",
  "questions": [
    {
      "text": "貴公司名稱",
      "type": "TEXT",
      "required": true
    },
    {
      "text": "員工人數",
      "type": "NUMBER",
      "required": true,
      "config": {
        "numberMin": 1,
        "numberMax": 100000
      }
    }
  ]
}
```

**回應 (201 Created)**:

```json
{
  "id": "template-002",
  "name": "2025 供應商評估範本",
  "type": "SAQ",
  "latestVersion": "v1.0.0",
  "versions": [
    {
      "version": "v1.0.0",
      "questions": [...],
      "createdAt": "2025-12-02T05:52:15.355Z"
    }
  ],
  "createdAt": "2025-12-02T05:52:15.355Z",
  "updatedAt": "2025-12-02T05:52:15.355Z"
}
```

**錯誤回應**:

- `400 Bad Request`: 參數驗證失敗
- `403 Forbidden`: 無權限建立範本

**前端 Composable 使用**:

```typescript
import { useTemplates } from '~/composables/useTemplates'

const { createTemplate } = useTemplates()

const newTemplate = await createTemplate({
  name: '2025 供應商評估範本',
  type: 'SAQ',
  questions: [
    {
      text: '貴公司名稱',
      type: 'TEXT',
      required: true
    }
  ]
})
```

---

### 4. 更新範本

**端點**: `PUT /api/templates/{templateId}`

**描述**: 更新範本的基本資訊與題目（會產生新版本）。

**請求 Headers**:

```
Authorization: Bearer <jwt_token>
```

**路徑參數**:

- `templateId`: 範本 ID

**請求參數**:

```json
{
  "name": "string",              // 選填，範本名稱
  "questions": [                 // 選填，題目列表（會產生新版本）
    {
      "id": "string",            // 選填，現有題目 ID（更新現有題目）
      "text": "string",
      "type": "QUESTION_TYPE",
      "required": "boolean",
      "options": ["string"],
      "config": {}
    }
  ]
}
```

**回應 (200 OK)**:

```json
{
  "id": "template-001",
  "name": "標準 SAQ 範本（已更新）",
  "type": "SAQ",
  "latestVersion": "v1.3.0",
  "versions": [...],
  "updatedAt": "2025-12-02T05:52:15.355Z"
}
```

**錯誤回應**:

- `403 Forbidden`: 無權限更新範本
- `404 Not Found`: 範本不存在

**前端 Composable 使用**:

```typescript
import { useTemplates } from '~/composables/useTemplates'

const { updateTemplate } = useTemplates()

const updated = await updateTemplate('template-001', {
  name: '新的範本名稱',
  questions: [...]
})
```

---

### 5. 發布新版本

**端點**: `POST /api/templates/{templateId}/publish`

**描述**: 發布範本的新版本（將草稿版本發布為正式版本）。

**請求 Headers**:

```
Authorization: Bearer <jwt_token>
```

**路徑參數**:

- `templateId`: 範本 ID

**請求參數**: 無

**回應 (200 OK)**:

```json
{
  "id": "template-001",
  "latestVersion": "v1.3.0",
  "message": "版本 v1.3.0 發布成功"
}
```

**錯誤回應**:

- `400 Bad Request`: 無草稿版本可發布
- `403 Forbidden`: 無權限發布版本

**前端 Composable 使用**:

```typescript
import { useTemplates } from '~/composables/useTemplates'

const { publishVersion } = useTemplates()

await publishVersion('template-001')
```

---

### 6. 刪除範本

**端點**: `DELETE /api/templates/{templateId}`

**描述**: 刪除範本（僅限管理員，且範本未被專案使用）。

**請求 Headers**:

```
Authorization: Bearer <jwt_token>
```

**路徑參數**:

- `templateId`: 範本 ID

**回應 (200 OK)**:

```json
{
  "message": "範本刪除成功"
}
```

**錯誤回應**:

- `400 Bad Request`: 範本已被專案使用
```json
{
  "error": "TEMPLATE_IN_USE",
  "message": "此範本已被專案使用，無法刪除",
  "details": {
    "projectCount": 3
  }
}
```

- `403 Forbidden`: 無權限刪除範本
- `404 Not Found`: 範本不存在

**前端 Composable 使用**:

```typescript
import { useTemplates } from '~/composables/useTemplates'

const { deleteTemplate } = useTemplates()

await deleteTemplate('template-001')
```

---

### 7. 題目管理 API

#### 7.1 新增題目

**端點**: `POST /api/templates/{templateId}/questions`

**請求參數**:

```json
{
  "text": "string",
  "type": "QUESTION_TYPE",
  "required": "boolean",
  "options": ["string"],
  "config": {}
}
```

#### 7.2 更新題目

**端點**: `PUT /api/templates/{templateId}/questions/{questionId}`

#### 7.3 刪除題目

**端點**: `DELETE /api/templates/{templateId}/questions/{questionId}`

#### 7.4 調整題目順序

**端點**: `PUT /api/templates/{templateId}/questions/reorder`

**請求參數**:

```json
{
  "questionIds": ["q-003", "q-001", "q-002"]
}
```

---

## 題目類型與設定

### 1. TEXT (簡答題)

```json
{
  "type": "TEXT",
  "config": {
    "maxLength": 500        // 最大字元數
  }
}
```

### 2. NUMBER (數字題)

```json
{
  "type": "NUMBER",
  "config": {
    "numberMin": 0,
    "numberMax": 1000
  }
}
```

### 3. DATE (日期題)

```json
{
  "type": "DATE",
  "config": {
    "minDate": "2020-01-01",
    "maxDate": "2025-12-31"
  }
}
```

### 4. BOOLEAN (是非題)

```json
{
  "type": "BOOLEAN"
}
```

### 5. SINGLE_CHOICE (單選題)

```json
{
  "type": "SINGLE_CHOICE",
  "options": ["選項 A", "選項 B", "選項 C"]
}
```

### 6. MULTI_CHOICE (多選題)

```json
{
  "type": "MULTI_CHOICE",
  "options": ["選項 A", "選項 B", "選項 C"],
  "config": {
    "minSelections": 1,
    "maxSelections": 3
  }
}
```

### 7. FILE (檔案上傳)

```json
{
  "type": "FILE",
  "config": {
    "maxFileSize": 10485760,                    // 10MB (bytes)
    "allowedFileTypes": ["pdf", "jpg", "png"]
  }
}
```

### 8. RATING (評分量表)

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

## 版本控制

### 版本號規則

- 格式：`v{major}.{minor}.{patch}`
- 範例：`v1.0.0`, `v1.2.3`, `v2.0.0`

### 版本變更規則

- **Major**: 重大變更，不相容舊版
- **Minor**: 新增題目或功能
- **Patch**: 錯誤修正、文字調整

### 版本鎖定

專案建立時會鎖定範本的特定版本，即使範本後續有新版本，專案仍使用建立時的版本。

```typescript
// 專案鎖定範本版本
interface Project {
  templateId: string
  templateVersion: string  // 例如 "v1.2.0"
}
```

---

## 使用範例

### 完整的範本建立流程

```vue
<script setup lang="ts">
import { ref } from 'vue'
import { useTemplates } from '~/composables/useTemplates'
import type { QuestionType } from '~/types'

const { createTemplate } = useTemplates()

const form = ref({
  name: '',
  type: 'SAQ' as 'SAQ' | 'CONFLICT',
  questions: [] as Array<{
    text: string
    type: QuestionType
    required: boolean
    options?: string[]
    config?: any
  }>
})

const questionTypes: QuestionType[] = [
  'TEXT', 'NUMBER', 'DATE', 'BOOLEAN',
  'SINGLE_CHOICE', 'MULTI_CHOICE', 'FILE', 'RATING'
]

const addQuestion = () => {
  form.value.questions.push({
    text: '',
    type: 'TEXT',
    required: false
  })
}

const removeQuestion = (index: number) => {
  form.value.questions.splice(index, 1)
}

const handleSubmit = async () => {
  try {
    const template = await createTemplate(form.value)
    alert('範本建立成功')
    navigateTo(`/admin/templates/${template.id}`)
  } catch (error) {
    console.error(error)
  }
}
</script>

<template>
  <form @submit.prevent="handleSubmit">
    <h2>建立新範本</h2>
    
    <input v-model="form.name" placeholder="範本名稱" required />
    
    <select v-model="form.type" required>
      <option value="SAQ">SAQ</option>
      <option value="CONFLICT">衝突資產</option>
    </select>
    
    <div>
      <h3>題目</h3>
      <div v-for="(q, index) in form.questions" :key="index">
        <input v-model="q.text" placeholder="題目內容" required />
        
        <select v-model="q.type" required>
          <option v-for="type in questionTypes" :key="type" :value="type">
            {{ type }}
          </option>
        </select>
        
        <label>
          <input v-model="q.required" type="checkbox" />
          必填
        </label>
        
        <!-- 選項設定（單選/多選） -->
        <div v-if="q.type === 'SINGLE_CHOICE' || q.type === 'MULTI_CHOICE'">
          <input 
            v-model="q.options" 
            placeholder="選項（逗號分隔）" 
            @input="e => q.options = e.target.value.split(',')"
          />
        </div>
        
        <button type="button" @click="removeQuestion(index)">移除</button>
      </div>
      
      <button type="button" @click="addQuestion">新增題目</button>
    </div>
    
    <button type="submit">建立範本</button>
  </form>
</template>
```

---

### 範本列表與管理

```vue
<script setup lang="ts">
import { useTemplates } from '~/composables/useTemplates'

const { templates, fetchTemplates, deleteTemplate } = useTemplates()
const templateType = ref<'SAQ' | 'CONFLICT'>('SAQ')

onMounted(async () => {
  await fetchTemplates(templateType.value)
})

watch(templateType, async (newType) => {
  await fetchTemplates(newType)
})

const handleDelete = async (id: string) => {
  if (!confirm('確定要刪除此範本嗎？')) return
  
  try {
    await deleteTemplate(id)
    await fetchTemplates(templateType.value)
  } catch (error) {
    console.error(error)
  }
}
</script>

<template>
  <div>
    <h1>範本管理</h1>
    
    <div>
      <button @click="templateType = 'SAQ'">SAQ</button>
      <button @click="templateType = 'CONFLICT'">衝突資產</button>
    </div>
    
    <NuxtLink to="/admin/templates/new">
      <button>建立新範本</button>
    </NuxtLink>
    
    <table>
      <thead>
        <tr>
          <th>範本名稱</th>
          <th>最新版本</th>
          <th>題目數量</th>
          <th>操作</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="t in templates" :key="t.id">
          <td>{{ t.name }}</td>
          <td>{{ t.latestVersion }}</td>
          <td>{{ t.versions[0]?.questionCount }}</td>
          <td>
            <NuxtLink :to="`/admin/templates/${t.id}`">編輯</NuxtLink>
            <button @click="handleDelete(t.id)">刪除</button>
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
describe('useTemplates', () => {
  it('should fetch SAQ templates', async () => {
    const { fetchTemplates, templates } = useTemplates()
    
    await fetchTemplates('SAQ')
    
    expect(templates.value).toBeInstanceOf(Array)
    expect(templates.value.every(t => t.type === 'SAQ')).toBe(true)
  })
  
  it('should create template successfully', async () => {
    const { createTemplate } = useTemplates()
    
    const result = await createTemplate({
      name: '測試範本',
      type: 'SAQ',
      questions: [
        { text: '測試題目', type: 'TEXT', required: true }
      ]
    })
    
    expect(result.id).toBeDefined()
    expect(result.name).toBe('測試範本')
  })
  
  it('should publish new version', async () => {
    const { publishVersion } = useTemplates()
    
    await expect(publishVersion('template-001')).resolves.not.toThrow()
  })
})
```

---

## 相關文件

- [專案管理 API](./projects.md)
- [問卷填寫與答案 API](./answers.md)
- [資料模型](./data-models.md)
