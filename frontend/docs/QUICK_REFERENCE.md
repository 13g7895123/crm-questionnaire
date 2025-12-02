# API Quick Reference Guide

快速查找常用 API 操作的指南。

## 🔐 認證 (Authentication)

### 登入
```typescript
const { login } = useAuth()
await login('username', 'password')
```

### 登出
```typescript
const { logout } = useAuth()
logout()
```

### 取得當前使用者
```typescript
const authStore = useAuthStore()
console.log(authStore.user)
```

---

## 👤 使用者管理 (User Management)

### 更新個人資料
```typescript
const { updateProfile } = useUser()
await updateProfile({
  email: 'new@email.com',
  phone: '0912345678',
  departmentId: 'dept-id'
})
```

### 變更密碼
```typescript
const { changePassword } = useUser()
await changePassword('currentPassword', 'newPassword')
```

---

## 🏢 部門管理 (Department Management)

### 取得部門列表
```typescript
const { departments, fetchDepartments } = useDepartments()
await fetchDepartments()
```

### 建立部門
```typescript
const { createDepartment } = useDepartments()
await createDepartment('研發部')
```

---

## 📋 專案管理 (Project Management)

### 取得專案列表
```typescript
const { projects, fetchProjects } = useProjects()
await fetchProjects('SAQ') // or 'CONFLICT'
```

### 建立專案
```typescript
const { createProject } = useProjects()
await createProject({
  name: '2024 年度 SAQ',
  year: 2024,
  type: 'SAQ',
  templateId: 'template-id',
  supplierId: 'supplier-id',
  reviewConfig: [
    { stageOrder: 1, departmentId: 'dept-1' },
    { stageOrder: 2, departmentId: 'dept-2' }
  ]
})
```

### 取得專案詳情
```typescript
const { getProject } = useProjects()
const project = await getProject('project-id')
```

### 更新專案
```typescript
const { updateProject } = useProjects()
await updateProject('project-id', {
  name: '2024 年度 SAQ (更新)',
  year: 2024
})
```

### 刪除專案
```typescript
const { deleteProject } = useProjects()
await deleteProject('project-id')
```

---

## 📝 範本管理 (Template Management)

### 取得範本列表
```typescript
const { templates, fetchTemplates } = useTemplates()
await fetchTemplates('SAQ') // or 'CONFLICT'
```

### 建立範本
```typescript
const { createTemplate } = useTemplates()
await createTemplate({
  name: 'SAQ 標準範本',
  type: 'SAQ',
  questions: [
    {
      text: '請問貴公司的產品是否符合環保標準？',
      type: 'BOOLEAN',
      required: true
    },
    {
      text: '請詳細說明',
      type: 'TEXT',
      required: false,
      config: { maxLength: 1000 }
    }
  ]
})
```

### 發布新版本
```typescript
const { publishVersion } = useTemplates()
await publishVersion('template-id')
```

---

## ✍️ 問卷填寫 (Answering)

### 取得專案答案
```typescript
const { getAnswers } = useAnswers()
const answers = await getAnswers('project-id')
```

### 儲存答案 (草稿)
```typescript
const { saveAnswers } = useAnswers()
await saveAnswers('project-id', {
  'question-1': { questionId: 'question-1', value: true },
  'question-2': { questionId: 'question-2', value: '詳細說明內容' }
})
```

### 提交問卷
```typescript
const { submitAnswers } = useAnswers()
await submitAnswers('project-id', {
  'question-1': { questionId: 'question-1', value: true },
  'question-2': { questionId: 'question-2', value: '詳細說明內容' }
})
```

---

## ✅ 審核流程 (Review)

### 取得待審核專案
```typescript
const { getPendingReviews } = useReview()
const pending = await getPendingReviews()
```

### 取得審核歷程
```typescript
const { reviewLogs, getReviewLogs } = useReview()
await getReviewLogs('project-id')
```

### 核准專案
```typescript
const { approveProject } = useReview()
await approveProject('project-id', '資料完整，核准通過')
```

### 退回專案
```typescript
const { returnProject } = useReview()
await returnProject('project-id', '部分資料不完整，請重新填寫')
```

---

## 📁 檔案上傳

### 上傳檔案
```typescript
const api = useApi()

const formData = new FormData()
formData.append('file', file)
formData.append('projectId', 'project-id')
formData.append('questionId', 'question-id')

const result = await fetch('/api/files/upload', {
  method: 'POST',
  body: formData,
  headers: {
    'Authorization': `Bearer ${authStore.token}`
  }
})

const data = await result.json()
// 使用 data.fileId 作為答案的值
```

---

## 🔍 常見操作組合

### 完整的專案建立流程

```typescript
// 1. 取得範本列表
const { templates, fetchTemplates } = useTemplates()
await fetchTemplates('SAQ')

// 2. 取得供應商列表
const { suppliers, fetchSuppliers } = useSuppliers()
await fetchSuppliers()

// 3. 取得部門列表 (用於設定審核流程)
const { departments, fetchDepartments } = useDepartments()
await fetchDepartments()

// 4. 建立專案
const { createProject } = useProjects()
const newProject = await createProject({
  name: '2024 Q1 SAQ',
  year: 2024,
  type: 'SAQ',
  templateId: templates.value[0].id,
  supplierId: suppliers.value[0].id,
  reviewConfig: [
    { stageOrder: 1, departmentId: departments.value[0].id },
    { stageOrder: 2, departmentId: departments.value[1].id }
  ]
})

// 5. 發布專案
await api.post(`/projects/${newProject.id}/publish`)
```

### 完整的問卷填寫流程

```typescript
// 1. 取得專案詳情
const { getProject } = useProjects()
const project = await getProject('project-id')

// 2. 取得已儲存的答案 (如果有)
const { getAnswers, saveAnswers, submitAnswers } = useAnswers()
const existingAnswers = await getAnswers('project-id')

// 3. 準備答案資料
const answers = {}
project.template.questions.forEach(question => {
  answers[question.id] = {
    questionId: question.id,
    value: null // 使用者輸入的值
  }
})

// 4. 定期儲存 (自動儲存)
setInterval(async () => {
  await saveAnswers('project-id', answers)
}, 30000) // 每 30 秒自動儲存

// 5. 最終提交
await submitAnswers('project-id', answers)
```

### 完整的審核流程

```typescript
// 1. 取得待審核專案列表
const { getPendingReviews } = useReview()
const pending = await getPendingReviews()

// 2. 選擇一個專案查看詳情
const { getProject } = useProjects()
const project = await getProject(pending.data[0].id)

// 3. 查看專案答案
const { getAnswers } = useAnswers()
const answers = await getAnswers(project.id)

// 4. 查看審核歷程
const { getReviewLogs } = useReview()
await getReviewLogs(project.id)

// 5. 做出審核決定
const { approveProject, returnProject } = useReview()

// 核准
await approveProject(project.id, '資料完整正確')

// 或退回
await returnProject(project.id, '請補充說明第 3 題的內容')
```

---

## 🎯 權限控制

### 檢查使用者角色

```typescript
const authStore = useAuthStore()

if (authStore.user?.role === 'HOST') {
  // 製造商功能
  // - 建立/管理專案
  // - 建立/管理範本
  // - 查看所有供應商
  // - 審核專案
} else if (authStore.user?.role === 'SUPPLIER') {
  // 供應商功能
  // - 查看被指派的專案
  // - 填寫問卷
  // - 查看審核結果
}
```

### 檢查審核權限

```typescript
const authStore = useAuthStore()
const project = await getProject('project-id')

// 檢查是否為當前階段的審核者
const currentStageConfig = project.reviewConfig.find(
  config => config.stageOrder === project.currentStage
)

const canReview = 
  authStore.user?.departmentId === currentStageConfig?.departmentId &&
  project.status === 'REVIEWING'

if (canReview) {
  // 顯示審核按鈕
}
```

---

## 🐛 錯誤處理

### 標準錯誤處理模式

```typescript
try {
  await createProject(data)
  // 成功訊息
  toast.success('專案建立成功')
} catch (error) {
  // 錯誤處理
  if (error.status === 400) {
    toast.error('資料格式錯誤: ' + error.message)
  } else if (error.status === 403) {
    toast.error('無權限執行此操作')
  } else if (error.status === 404) {
    toast.error('資源不存在')
  } else {
    toast.error('操作失敗: ' + error.message)
  }
}
```

### 使用 Loading 狀態

```typescript
const api = useApi()

// api.isLoading 會自動更新
const projects = await api.get('/projects')

// 在模板中使用
<template>
  <div v-if="api.isLoading.value">載入中...</div>
  <div v-else>
    <!-- 內容 -->
  </div>
</template>
```

---

## 📊 資料格式範例

### 題目類型範例

```typescript
// 文字題
{
  text: '請說明',
  type: 'TEXT',
  required: true,
  config: { maxLength: 500 }
}

// 數字題
{
  text: '請輸入數量',
  type: 'NUMBER',
  required: true,
  config: { numberMin: 0, numberMax: 1000 }
}

// 日期題
{
  text: '請選擇日期',
  type: 'DATE',
  required: true
}

// 布林題 (是/否)
{
  text: '是否同意',
  type: 'BOOLEAN',
  required: true
}

// 單選題
{
  text: '請選擇一項',
  type: 'SINGLE_CHOICE',
  required: true,
  options: ['選項 A', '選項 B', '選項 C']
}

// 多選題
{
  text: '請選擇多項',
  type: 'MULTI_CHOICE',
  required: false,
  options: ['選項 1', '選項 2', '選項 3']
}

// 檔案上傳
{
  text: '請上傳文件',
  type: 'FILE',
  required: true,
  config: {
    maxFileSize: 10485760, // 10MB
    allowedFileTypes: ['application/pdf', 'image/jpeg', 'image/png']
  }
}

// 評分量表
{
  text: '請評分',
  type: 'RATING',
  required: true,
  config: {
    ratingMin: 1,
    ratingMax: 5,
    ratingStep: 1
  }
}
```

### 答案格式範例

```typescript
// 對應上述題目的答案
{
  'question-1': { questionId: 'question-1', value: '這是文字回答' },
  'question-2': { questionId: 'question-2', value: 100 },
  'question-3': { questionId: 'question-3', value: '2024-03-15' },
  'question-4': { questionId: 'question-4', value: true },
  'question-5': { questionId: 'question-5', value: '選項 B' },
  'question-6': { questionId: 'question-6', value: ['選項 1', '選項 3'] },
  'question-7': { questionId: 'question-7', value: 'file-id-123' },
  'question-8': { questionId: 'question-8', value: 4 }
}
```

---

## 🔗 相關文件

- [API Requirements](./API_REQUIREMENTS.md) - 完整 API 文件
- [API Mapping](./API_MAPPING.md) - Composables 對應
- [Documentation Index](./README.md) - 文件總覽

---

最後更新: 2024-12-02
