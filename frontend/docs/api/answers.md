# 問卷填寫與答案 API (Questionnaire Answering)

## 概述

問卷填寫模組負責供應商填寫問卷、儲存答案、提交專案等功能。支援草稿暫存與最終提交。

## API 端點

### 1. 取得專案答案

**端點**: `GET /api/projects/{projectId}/answers`

**描述**: 取得指定專案的答案資料（包含已儲存的草稿）。

**請求 Headers**:

```
Authorization: Bearer <jwt_token>
```

**路徑參數**:

- `projectId`: 專案 ID

**回應 (200 OK)**:

```json
{
  "projectId": "project-001",
  "answers": {
    "q-001": {
      "questionId": "q-001",
      "value": true
    },
    "q-002": {
      "questionId": "q-002",
      "value": "電子業"
    },
    "q-003": {
      "questionId": "q-003",
      "value": {
        "filename": "certificate.pdf",
        "url": "https://storage.example.com/files/cert-123.pdf",
        "size": 2048576
      }
    }
  },
  "lastSavedAt": "2025-12-02T05:30:00.000Z"
}
```

**錯誤回應**:

- `403 Forbidden`: 無權限查看此專案答案（非指派的供應商）
- `404 Not Found`: 專案不存在

**前端 Composable 使用**:

```typescript
import { useAnswers } from '~/composables/useAnswers'

const { getAnswers } = useAnswers()

const answers = await getAnswers('project-001')
console.log(answers)
```

---

### 2. 儲存答案（草稿）

**端點**: `POST /api/projects/{projectId}/answers`

**描述**: 儲存問卷答案為草稿，不進行完整性驗證。

**請求 Headers**:

```
Authorization: Bearer <jwt_token>
```

**路徑參數**:

- `projectId`: 專案 ID

**請求參數**:

```json
{
  "answers": {
    "q-001": {
      "questionId": "q-001",
      "value": true
    },
    "q-002": {
      "questionId": "q-002",
      "value": "電子業"
    }
  }
}
```

**回應 (200 OK)**:

```json
{
  "message": "答案已儲存",
  "lastSavedAt": "2025-12-02T05:52:15.355Z"
}
```

**錯誤回應**:

- `400 Bad Request`: 專案狀態不允許編輯
```json
{
  "error": "PROJECT_NOT_EDITABLE",
  "message": "此專案目前不可編輯"
}
```

- `403 Forbidden`: 無權限編輯此專案

**前端 Composable 使用**:

```typescript
import { useAnswers } from '~/composables/useAnswers'

const { saveAnswers } = useAnswers()

await saveAnswers('project-001', {
  'q-001': { questionId: 'q-001', value: true },
  'q-002': { questionId: 'q-002', value: '電子業' }
})
```

---

### 3. 提交答案

**端點**: `POST /api/projects/{projectId}/submit`

**描述**: 提交問卷答案，進行完整性驗證，專案狀態變更為「已提交」並進入審核流程。

**請求 Headers**:

```
Authorization: Bearer <jwt_token>
```

**路徑參數**:

- `projectId`: 專案 ID

**請求參數**:

```json
{
  "answers": {
    "q-001": {
      "questionId": "q-001",
      "value": true
    },
    "q-002": {
      "questionId": "q-002",
      "value": "電子業"
    }
  }
}
```

**回應 (200 OK)**:

```json
{
  "message": "問卷已提交",
  "projectId": "project-001",
  "status": "SUBMITTED",
  "submittedAt": "2025-12-02T05:52:15.355Z"
}
```

**錯誤回應**:

- `400 Bad Request`: 答案驗證失敗
```json
{
  "error": "VALIDATION_FAILED",
  "message": "答案驗證失敗",
  "details": {
    "missingRequired": ["q-005", "q-007"],
    "invalidAnswers": [
      {
        "questionId": "q-003",
        "error": "檔案大小超過限制"
      }
    ]
  }
}
```

- `403 Forbidden`: 無權限提交此專案

**前端 Composable 使用**:

```typescript
import { useAnswers } from '~/composables/useAnswers'

const { submitAnswers } = useAnswers()

try {
  await submitAnswers('project-001', answers)
  alert('問卷提交成功')
} catch (error) {
  console.error('提交失敗', error)
}
```

---

### 4. 檔案上傳

**端點**: `POST /api/projects/{projectId}/upload`

**描述**: 上傳檔案作為問卷答案的一部分。

**請求 Headers**:

```
Authorization: Bearer <jwt_token>
Content-Type: multipart/form-data
```

**路徑參數**:

- `projectId`: 專案 ID

**請求參數** (FormData):

- `file`: 檔案
- `questionId`: 題目 ID

**回應 (200 OK)**:

```json
{
  "questionId": "q-003",
  "file": {
    "filename": "certificate.pdf",
    "url": "https://storage.example.com/files/cert-123.pdf",
    "size": 2048576,
    "type": "application/pdf",
    "uploadedAt": "2025-12-02T05:52:15.355Z"
  }
}
```

**錯誤回應**:

- `400 Bad Request`: 檔案類型不支援
```json
{
  "error": "INVALID_FILE_TYPE",
  "message": "不支援的檔案類型",
  "allowedTypes": ["pdf", "jpg", "png"]
}
```

- `400 Bad Request`: 檔案大小超過限制
```json
{
  "error": "FILE_TOO_LARGE",
  "message": "檔案大小超過限制",
  "maxSize": 10485760
}
```

**前端使用範例**:

```typescript
const uploadFile = async (projectId: string, questionId: string, file: File) => {
  const formData = new FormData()
  formData.append('file', file)
  formData.append('questionId', questionId)
  
  const response = await fetch(`/api/projects/${projectId}/upload`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`
    },
    body: formData
  })
  
  return await response.json()
}
```

---

## 答案資料格式

### 各題型答案格式

#### TEXT (簡答題)

```json
{
  "questionId": "q-001",
  "value": "這是答案文字"
}
```

#### NUMBER (數字題)

```json
{
  "questionId": "q-002",
  "value": 123
}
```

#### DATE (日期題)

```json
{
  "questionId": "q-003",
  "value": "2025-12-02"
}
```

#### BOOLEAN (是非題)

```json
{
  "questionId": "q-004",
  "value": true
}
```

#### SINGLE_CHOICE (單選題)

```json
{
  "questionId": "q-005",
  "value": "選項 A"
}
```

#### MULTI_CHOICE (多選題)

```json
{
  "questionId": "q-006",
  "value": ["選項 A", "選項 C"]
}
```

#### FILE (檔案上傳)

```json
{
  "questionId": "q-007",
  "value": {
    "filename": "document.pdf",
    "url": "https://storage.example.com/files/doc-123.pdf",
    "size": 2048576,
    "type": "application/pdf"
  }
}
```

#### RATING (評分量表)

```json
{
  "questionId": "q-008",
  "value": 4
}
```

---

## 答案驗證規則

### 必填題驗證

- 必填題目必須有答案
- 答案不可為空值（`null`, `undefined`, `""`, `[]`）

### 題型特定驗證

#### NUMBER 驗證

- 值必須在設定的 `numberMin` 和 `numberMax` 範圍內
- 值必須為有效數字

#### DATE 驗證

- 值必須為有效日期格式（ISO 8601）
- 值必須在設定的日期範圍內

#### SINGLE_CHOICE 驗證

- 值必須在選項列表中

#### MULTI_CHOICE 驗證

- 所有值必須在選項列表中
- 選擇數量必須符合 `minSelections` 和 `maxSelections` 限制

#### FILE 驗證

- 檔案類型必須在 `allowedFileTypes` 列表中
- 檔案大小必須小於 `maxFileSize`

#### RATING 驗證

- 值必須在 `ratingMin` 和 `ratingMax` 範圍內
- 值必須符合 `ratingStep` 的倍數

---

## 使用範例

### 完整的問卷填寫流程

```vue
<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useProjects } from '~/composables/useProjects'
import { useAnswers } from '~/composables/useAnswers'

const route = useRoute()
const projectId = route.params.id as string

const { getProject } = useProjects()
const { getAnswers, saveAnswers, submitAnswers } = useAnswers()

const project = ref(null)
const answers = ref({})
const isSaving = ref(false)
const isSubmitting = ref(false)

onMounted(async () => {
  // 載入專案與範本
  project.value = await getProject(projectId)
  
  // 載入已儲存的答案
  const savedAnswers = await getAnswers(projectId)
  answers.value = savedAnswers.answers || {}
})

// 自動儲存（每 30 秒）
let autoSaveTimer: NodeJS.Timeout
onMounted(() => {
  autoSaveTimer = setInterval(async () => {
    await handleSave()
  }, 30000)
})

onUnmounted(() => {
  clearInterval(autoSaveTimer)
})

const handleSave = async () => {
  isSaving.value = true
  try {
    await saveAnswers(projectId, answers.value)
  } catch (error) {
    console.error('儲存失敗', error)
  } finally {
    isSaving.value = false
  }
}

const handleSubmit = async () => {
  if (!confirm('確定要提交問卷嗎？提交後將無法修改。')) return
  
  isSubmitting.value = true
  try {
    await submitAnswers(projectId, answers.value)
    alert('問卷提交成功')
    navigateTo('/supplier/projects')
  } catch (error: any) {
    alert(`提交失敗：${error.message}`)
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <div v-if="project">
    <h1>{{ project.name }}</h1>
    
    <form @submit.prevent="handleSubmit">
      <!-- 根據題型渲染不同的輸入元件 -->
      <div 
        v-for="question in project.template.questions" 
        :key="question.id"
      >
        <label>
          {{ question.text }}
          <span v-if="question.required" class="required">*</span>
        </label>
        
        <!-- TEXT -->
        <input 
          v-if="question.type === 'TEXT'"
          v-model="answers[question.id].value"
          type="text"
          :required="question.required"
        />
        
        <!-- NUMBER -->
        <input 
          v-if="question.type === 'NUMBER'"
          v-model.number="answers[question.id].value"
          type="number"
          :min="question.config?.numberMin"
          :max="question.config?.numberMax"
          :required="question.required"
        />
        
        <!-- DATE -->
        <input 
          v-if="question.type === 'DATE'"
          v-model="answers[question.id].value"
          type="date"
          :required="question.required"
        />
        
        <!-- BOOLEAN -->
        <select 
          v-if="question.type === 'BOOLEAN'"
          v-model="answers[question.id].value"
          :required="question.required"
        >
          <option :value="null">請選擇</option>
          <option :value="true">是</option>
          <option :value="false">否</option>
        </select>
        
        <!-- SINGLE_CHOICE -->
        <select 
          v-if="question.type === 'SINGLE_CHOICE'"
          v-model="answers[question.id].value"
          :required="question.required"
        >
          <option value="">請選擇</option>
          <option 
            v-for="opt in question.options" 
            :key="opt" 
            :value="opt"
          >
            {{ opt }}
          </option>
        </select>
        
        <!-- MULTI_CHOICE -->
        <div v-if="question.type === 'MULTI_CHOICE'">
          <label v-for="opt in question.options" :key="opt">
            <input 
              v-model="answers[question.id].value"
              type="checkbox"
              :value="opt"
            />
            {{ opt }}
          </label>
        </div>
        
        <!-- FILE -->
        <input 
          v-if="question.type === 'FILE'"
          type="file"
          @change="e => handleFileUpload(question.id, e.target.files[0])"
          :required="question.required && !answers[question.id]?.value"
        />
        
        <!-- RATING -->
        <input 
          v-if="question.type === 'RATING'"
          v-model.number="answers[question.id].value"
          type="range"
          :min="question.config?.ratingMin"
          :max="question.config?.ratingMax"
          :step="question.config?.ratingStep"
        />
      </div>
      
      <div class="actions">
        <button type="button" @click="handleSave" :disabled="isSaving">
          {{ isSaving ? '儲存中...' : '儲存草稿' }}
        </button>
        
        <button type="submit" :disabled="isSubmitting">
          {{ isSubmitting ? '提交中...' : '提交問卷' }}
        </button>
      </div>
    </form>
  </div>
</template>
```

---

### 檔案上傳處理

```typescript
const handleFileUpload = async (questionId: string, file: File) => {
  if (!file) return
  
  const question = project.value.template.questions.find(q => q.id === questionId)
  
  // 驗證檔案大小
  if (file.size > question.config.maxFileSize) {
    alert('檔案大小超過限制')
    return
  }
  
  // 驗證檔案類型
  const fileExt = file.name.split('.').pop()?.toLowerCase()
  if (!question.config.allowedFileTypes.includes(fileExt)) {
    alert('不支援的檔案類型')
    return
  }
  
  // 上傳檔案
  try {
    const formData = new FormData()
    formData.append('file', file)
    formData.append('questionId', questionId)
    
    const response = await fetch(`/api/projects/${projectId}/upload`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${authStore.token}`
      },
      body: formData
    })
    
    const result = await response.json()
    
    // 更新答案
    answers.value[questionId] = {
      questionId,
      value: result.file
    }
    
    // 自動儲存
    await handleSave()
  } catch (error) {
    console.error('檔案上傳失敗', error)
  }
}
```

---

## 進度追蹤

```typescript
// 計算填寫進度
const calculateProgress = () => {
  const questions = project.value.template.questions
  const requiredQuestions = questions.filter(q => q.required)
  
  const answeredRequired = requiredQuestions.filter(q => {
    const answer = answers.value[q.id]
    return answer && answer.value !== null && answer.value !== ''
  })
  
  return {
    total: questions.length,
    required: requiredQuestions.length,
    answered: Object.keys(answers.value).length,
    answeredRequired: answeredRequired.length,
    percentage: Math.round((answeredRequired.length / requiredQuestions.length) * 100)
  }
}
```

---

## 測試案例

```typescript
describe('useAnswers', () => {
  it('should get answers successfully', async () => {
    const { getAnswers } = useAnswers()
    
    const result = await getAnswers('project-001')
    
    expect(result.projectId).toBe('project-001')
    expect(result.answers).toBeDefined()
  })
  
  it('should save answers successfully', async () => {
    const { saveAnswers } = useAnswers()
    
    await expect(saveAnswers('project-001', {
      'q-001': { questionId: 'q-001', value: true }
    })).resolves.not.toThrow()
  })
  
  it('should submit answers successfully', async () => {
    const { submitAnswers } = useAnswers()
    
    await expect(submitAnswers('project-001', {
      'q-001': { questionId: 'q-001', value: true },
      'q-002': { questionId: 'q-002', value: '電子業' }
    })).resolves.not.toThrow()
  })
  
  it('should throw error when submitting incomplete answers', async () => {
    const { submitAnswers } = useAnswers()
    
    await expect(submitAnswers('project-001', {
      'q-001': { questionId: 'q-001', value: true }
      // Missing required question q-002
    })).rejects.toThrow('VALIDATION_FAILED')
  })
})
```

---

## 相關文件

- [專案管理 API](./projects.md)
- [多階段審核 API](./reviews.md)
- [範本管理 API](./templates.md)
- [錯誤處理規範](./error-handling.md)
