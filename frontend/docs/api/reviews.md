# 多階段審核 API (Multi-stage Review)

## 概述

多階段審核模組負責處理專案的審核流程，包含待審核專案查詢、審核歷程記錄、核准與退回操作。

## API 端點

### 1. 取得待審核專案列表

**端點**: `GET /api/review/pending`

**描述**: 取得目前使用者所屬部門待審核的專案列表。

**請求 Headers**:

```
Authorization: Bearer <jwt_token>
```

**查詢參數**:

- `type`: 專案類型（選填，`SAQ` 或 `CONFLICT`）
- `page`: 頁碼（預設 1）
- `pageSize`: 每頁筆數（預設 20）

**請求範例**:

```
GET /api/review/pending
GET /api/review/pending?type=SAQ
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
      "supplier": {
        "id": "supplier-123",
        "name": "供應商 A 公司"
      },
      "status": "REVIEWING",
      "currentStage": 1,
      "myStage": 1,
      "submittedAt": "2025-11-30T10:00:00.000Z",
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
      ]
    }
  ]
}
```

**前端 Composable 使用**:

```typescript
import { useReview } from '~/composables/useReview'

const { getPendingReviews } = useReview()

const pendingProjects = await getPendingReviews()
console.log(pendingProjects)
```

---

### 2. 取得審核歷程

**端點**: `GET /api/projects/{projectId}/review-logs`

**描述**: 取得指定專案的完整審核歷程記錄。

**請求 Headers**:

```
Authorization: Bearer <jwt_token>
```

**路徑參數**:

- `projectId`: 專案 ID

**回應 (200 OK)**:

```json
{
  "data": [
    {
      "id": "log-001",
      "projectId": "project-001",
      "reviewerId": "user-001",
      "reviewerName": "張三",
      "reviewerDepartment": "採購部",
      "stage": 1,
      "action": "APPROVE",
      "comment": "資料審核通過",
      "timestamp": "2025-12-01T14:30:00.000Z"
    },
    {
      "id": "log-002",
      "projectId": "project-001",
      "reviewerId": "user-002",
      "reviewerName": "李四",
      "reviewerDepartment": "品質管理部",
      "stage": 2,
      "action": "RETURN",
      "comment": "品質證書已過期，請重新上傳",
      "timestamp": "2025-12-02T09:15:00.000Z"
    }
  ]
}
```

**前端 Composable 使用**:

```typescript
import { useReview } from '~/composables/useReview'

const { getReviewLogs, reviewLogs } = useReview()

await getReviewLogs('project-001')
console.log(reviewLogs.value)
```

---

### 3. 核准專案

**端點**: `POST /api/projects/{projectId}/approve`

**描述**: 核准專案，進入下一階段審核或結案。

**請求 Headers**:

```
Authorization: Bearer <jwt_token>
```

**路徑參數**:

- `projectId`: 專案 ID

**請求參數**:

```json
{
  "comment": "string"    // 必填，審核意見
}
```

**請求範例**:

```json
{
  "comment": "資料審核通過，符合要求"
}
```

**回應 (200 OK)**:

```json
{
  "message": "專案已核准",
  "projectId": "project-001",
  "status": "REVIEWING",
  "currentStage": 2,
  "nextDepartment": {
    "id": "dept-002",
    "name": "品質管理部"
  }
}
```

或當為最後階段時：

```json
{
  "message": "專案已核准並結案",
  "projectId": "project-001",
  "status": "APPROVED",
  "approvedAt": "2025-12-02T05:52:15.355Z"
}
```

**錯誤回應**:

- `400 Bad Request`: 專案狀態不允許審核
```json
{
  "error": "PROJECT_NOT_REVIEWABLE",
  "message": "此專案目前不可審核"
}
```

- `403 Forbidden`: 無權限審核（非目前階段的審核部門）
```json
{
  "error": "NOT_YOUR_TURN",
  "message": "目前不是您的部門審核階段"
}
```

**前端 Composable 使用**:

```typescript
import { useReview } from '~/composables/useReview'

const { approveProject } = useReview()

try {
  await approveProject('project-001', '資料審核通過')
  alert('專案已核准')
} catch (error) {
  console.error('核准失敗', error)
}
```

---

### 4. 退回專案

**端點**: `POST /api/projects/{projectId}/return`

**描述**: 退回專案給供應商重新填寫。

**請求 Headers**:

```
Authorization: Bearer <jwt_token>
```

**路徑參數**:

- `projectId`: 專案 ID

**請求參數**:

```json
{
  "comment": "string"    // 必填，退回原因
}
```

**請求範例**:

```json
{
  "comment": "品質證書已過期，請重新上傳有效證書"
}
```

**回應 (200 OK)**:

```json
{
  "message": "專案已退回",
  "projectId": "project-001",
  "status": "RETURNED",
  "returnedAt": "2025-12-02T05:52:15.355Z"
}
```

**錯誤回應**:

- `400 Bad Request`: 專案狀態不允許退回
- `403 Forbidden`: 無權限退回

**前端 Composable 使用**:

```typescript
import { useReview } from '~/composables/useReview'

const { returnProject } = useReview()

try {
  await returnProject('project-001', '品質證書已過期，請重新上傳')
  alert('專案已退回')
} catch (error) {
  console.error('退回失敗', error)
}
```

---

### 5. 取得審核統計

**端點**: `GET /api/review/statistics`

**描述**: 取得目前使用者所屬部門的審核統計資訊。

**請求 Headers**:

```
Authorization: Bearer <jwt_token>
```

**查詢參數**:

- `year`: 年份（選填，預設為當年）
- `type`: 專案類型（選填）

**回應 (200 OK)**:

```json
{
  "pending": 5,
  "approved": 23,
  "returned": 3,
  "total": 31,
  "byType": {
    "SAQ": {
      "pending": 3,
      "approved": 15,
      "returned": 2
    },
    "CONFLICT": {
      "pending": 2,
      "approved": 8,
      "returned": 1
    }
  }
}
```

---

## 審核流程狀態

### 專案狀態流程

```
IN_PROGRESS (進行中)
  ↓ [供應商提交]
SUBMITTED (已提交)
  ↓ [進入審核]
REVIEWING (審核中 - 階段 1)
  ↓ [第一階段核准]
REVIEWING (審核中 - 階段 2)
  ↓ [第二階段核准]
REVIEWING (審核中 - 階段 3)
  ↓ [第三階段核准 - 若為最後階段]
APPROVED (已核准)

任何審核階段
  ↓ [審核退回]
RETURNED (已退回)
  ↓ [供應商重新編輯並提交]
REVIEWING (審核中 - 重新從第一階段開始)
```

### currentStage 說明

- `0`: 專案尚未提交
- `1`: 第一階段審核中
- `2`: 第二階段審核中
- `3`: 第三階段審核中
- ...（最多 5 階段）

---

## 審核權限控制

### 部門權限檢查

審核者必須滿足以下條件才能執行審核操作：

1. 專案狀態為 `REVIEWING`
2. 審核者所屬部門 = 專案目前審核階段的部門
3. 專案的 `currentStage` 與審核者的階段一致

```typescript
// 權限檢查範例
const canReview = (project: Project, user: User) => {
  if (project.status !== 'REVIEWING') return false
  
  const currentStageConfig = project.reviewConfig.find(
    c => c.stageOrder === project.currentStage
  )
  
  return currentStageConfig?.departmentId === user.departmentId
}
```

---

## 使用範例

### 待審核專案列表頁面

```vue
<script setup lang="ts">
import { useReview } from '~/composables/useReview'
import { useAuthStore } from '~/stores/auth'

const { getPendingReviews } = useReview()
const authStore = useAuthStore()

const pendingProjects = ref([])

onMounted(async () => {
  pendingProjects.value = await getPendingReviews()
})
</script>

<template>
  <div>
    <h1>待審核專案</h1>
    <p>部門：{{ authStore.user?.department?.name }}</p>
    
    <table>
      <thead>
        <tr>
          <th>專案名稱</th>
          <th>類型</th>
          <th>供應商</th>
          <th>提交時間</th>
          <th>目前階段</th>
          <th>操作</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="p in pendingProjects" :key="p.id">
          <td>{{ p.name }}</td>
          <td>{{ p.type }}</td>
          <td>{{ p.supplier.name }}</td>
          <td>{{ formatDate(p.submittedAt) }}</td>
          <td>階段 {{ p.currentStage }}</td>
          <td>
            <NuxtLink :to="`/review/projects/${p.id}`">
              審核
            </NuxtLink>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
```

---

### 審核詳情頁面

```vue
<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useProjects } from '~/composables/useProjects'
import { useAnswers } from '~/composables/useAnswers'
import { useReview } from '~/composables/useReview'

const route = useRoute()
const projectId = route.params.id as string

const { getProject } = useProjects()
const { getAnswers } = useAnswers()
const { getReviewLogs, approveProject, returnProject, reviewLogs } = useReview()

const project = ref(null)
const answers = ref({})
const comment = ref('')
const isProcessing = ref(false)

onMounted(async () => {
  await Promise.all([
    getProject(projectId).then(p => project.value = p),
    getAnswers(projectId).then(a => answers.value = a.answers),
    getReviewLogs(projectId)
  ])
})

const handleApprove = async () => {
  if (!comment.value.trim()) {
    alert('請輸入審核意見')
    return
  }
  
  if (!confirm('確定要核准此專案嗎？')) return
  
  isProcessing.value = true
  try {
    await approveProject(projectId, comment.value)
    alert('專案已核准')
    navigateTo('/review/pending')
  } catch (error: any) {
    alert(`核准失敗：${error.message}`)
  } finally {
    isProcessing.value = false
  }
}

const handleReturn = async () => {
  if (!comment.value.trim()) {
    alert('請輸入退回原因')
    return
  }
  
  if (!confirm('確定要退回此專案嗎？')) return
  
  isProcessing.value = true
  try {
    await returnProject(projectId, comment.value)
    alert('專案已退回')
    navigateTo('/review/pending')
  } catch (error: any) {
    alert(`退回失敗：${error.message}`)
  } finally {
    isProcessing.value = false
  }
}
</script>

<template>
  <div v-if="project">
    <h1>審核專案：{{ project.name }}</h1>
    
    <div class="info">
      <p>供應商：{{ project.supplier.name }}</p>
      <p>專案類型：{{ project.type }}</p>
      <p>目前階段：{{ project.currentStage }}</p>
      <p>提交時間：{{ formatDate(project.submittedAt) }}</p>
    </div>
    
    <!-- 顯示問卷答案 -->
    <div class="answers">
      <h2>問卷答案</h2>
      <div 
        v-for="question in project.template.questions" 
        :key="question.id"
      >
        <h3>{{ question.text }}</h3>
        <div class="answer">
          {{ formatAnswer(answers[question.id]?.value, question.type) }}
        </div>
      </div>
    </div>
    
    <!-- 審核歷程 -->
    <div class="review-logs">
      <h2>審核歷程</h2>
      <div v-for="log in reviewLogs" :key="log.id" class="log-item">
        <p>
          <strong>階段 {{ log.stage }}</strong> - 
          {{ log.reviewerName }} ({{ log.reviewerDepartment }})
        </p>
        <p>
          {{ log.action === 'APPROVE' ? '✓ 核准' : '✗ 退回' }} - 
          {{ formatDate(log.timestamp) }}
        </p>
        <p class="comment">{{ log.comment }}</p>
      </div>
    </div>
    
    <!-- 審核操作 -->
    <div class="actions">
      <textarea 
        v-model="comment" 
        placeholder="請輸入審核意見或退回原因"
        rows="4"
      ></textarea>
      
      <div class="buttons">
        <button 
          @click="handleApprove" 
          :disabled="isProcessing"
          class="approve"
        >
          核准
        </button>
        
        <button 
          @click="handleReturn" 
          :disabled="isProcessing"
          class="return"
        >
          退回
        </button>
      </div>
    </div>
  </div>
</template>
```

---

### 審核歷程時間軸元件

```vue
<script setup lang="ts">
import type { ReviewLog } from '~/types'

interface Props {
  logs: ReviewLog[]
  currentStage: number
}

const props = defineProps<Props>()
</script>

<template>
  <div class="review-timeline">
    <div 
      v-for="log in logs" 
      :key="log.id" 
      class="timeline-item"
      :class="{ approved: log.action === 'APPROVE', returned: log.action === 'RETURN' }"
    >
      <div class="timeline-marker">
        <span v-if="log.action === 'APPROVE'">✓</span>
        <span v-else>✗</span>
      </div>
      
      <div class="timeline-content">
        <div class="timeline-header">
          <strong>階段 {{ log.stage }}</strong>
          <span class="time">{{ formatDate(log.timestamp) }}</span>
        </div>
        
        <div class="timeline-body">
          <p class="reviewer">
            {{ log.reviewerName }} ({{ log.reviewerDepartment }})
          </p>
          <p class="action">
            {{ log.action === 'APPROVE' ? '核准' : '退回' }}
          </p>
          <p class="comment">{{ log.comment }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.review-timeline {
  position: relative;
  padding: 20px 0;
}

.timeline-item {
  display: flex;
  margin-bottom: 30px;
}

.timeline-marker {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  margin-right: 20px;
}

.timeline-item.approved .timeline-marker {
  background-color: #10b981;
  color: white;
}

.timeline-item.returned .timeline-marker {
  background-color: #ef4444;
  color: white;
}

.timeline-content {
  flex: 1;
  background: #f3f4f6;
  padding: 15px;
  border-radius: 8px;
}

.timeline-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 10px;
}

.comment {
  margin-top: 10px;
  font-style: italic;
  color: #6b7280;
}
</style>
```

---

## 通知機制

### 審核通知時機

1. **供應商提交專案**：通知第一階段審核部門
2. **審核核准**：通知下一階段審核部門（若有）
3. **審核退回**：通知供應商
4. **專案核准結案**：通知供應商與相關人員

### 通知內容範例

```typescript
// 供應商提交專案通知
{
  type: 'PROJECT_SUBMITTED',
  recipients: ['dept-001'], // 第一階段審核部門
  message: '供應商 A 公司已提交「2025 SAQ 第一季問卷」，請盡快審核'
}

// 審核核准通知
{
  type: 'REVIEW_APPROVED',
  recipients: ['dept-002'], // 下一階段審核部門
  message: '「2025 SAQ 第一季問卷」已通過第一階段審核，請進行第二階段審核'
}

// 審核退回通知
{
  type: 'REVIEW_RETURNED',
  recipients: ['supplier-123'],
  message: '「2025 SAQ 第一季問卷」已被退回，原因：品質證書已過期'
}
```

---

## 測試案例

```typescript
describe('useReview', () => {
  it('should get pending reviews', async () => {
    const { getPendingReviews } = useReview()
    
    const result = await getPendingReviews()
    
    expect(result).toBeInstanceOf(Array)
  })
  
  it('should get review logs', async () => {
    const { getReviewLogs, reviewLogs } = useReview()
    
    await getReviewLogs('project-001')
    
    expect(reviewLogs.value).toBeInstanceOf(Array)
  })
  
  it('should approve project successfully', async () => {
    const { approveProject } = useReview()
    
    await expect(approveProject('project-001', '審核通過'))
      .resolves.not.toThrow()
  })
  
  it('should return project successfully', async () => {
    const { returnProject } = useReview()
    
    await expect(returnProject('project-001', '資料不完整'))
      .resolves.not.toThrow()
  })
  
  it('should throw error when non-reviewer tries to review', async () => {
    const { approveProject } = useReview()
    
    await expect(approveProject('project-001', '審核通過'))
      .rejects.toThrow('NOT_YOUR_TURN')
  })
})
```

---

## 相關文件

- [專案管理 API](./projects.md)
- [問卷填寫與答案 API](./answers.md)
- [部門管理 API](./departments.md)
- [錯誤處理規範](./error-handling.md)
