# Frontend Composables to API Mapping

本文件說明 Frontend Composables 與 API 端點的對應關係，方便開發者快速查找相關實作。

## 目錄

1. [useAuth.ts - 認證相關](#useauthts)
2. [useUser.ts - 使用者管理](#useuserts)
3. [useDepartments.ts - 部門管理](#usedepartmentsts)
4. [useSuppliers.ts - 供應商管理](#usesuppliersts)
5. [useProjects.ts - 專案管理](#useprojectsts)
6. [useTemplates.ts - 範本管理](#usetemplatests)
7. [useAnswers.ts - 問卷填寫](#useanswersts)
8. [useReview.ts - 審核流程](#usereviewts)

---

## useAuth.ts

**檔案位置**: `frontend/app/composables/useAuth.ts`

### 方法對應

| Composable 方法 | API 端點 | HTTP 方法 | 說明 |
|----------------|---------|----------|------|
| `login(username, password)` | `/auth/login` | POST | 使用者登入 |
| `logout()` | `/auth/logout` | POST | 使用者登出 |

### 使用範例

```typescript
const { login, logout } = useAuth()

// 登入
await login('username', 'password')

// 登出
logout()
```

### 相關 API 文件
- [1.1 使用者登入](./API_REQUIREMENTS.md#11-使用者登入-user-login)
- [1.2 登出](./API_REQUIREMENTS.md#12-登出-logout)

---

## useUser.ts

**檔案位置**: `frontend/app/composables/useUser.ts`

### 方法對應

| Composable 方法 | API 端點 | HTTP 方法 | 說明 |
|----------------|---------|----------|------|
| `updateProfile(data)` | `/users/{userId}` | PUT | 更新使用者資料 |
| `changePassword(currentPassword, newPassword)` | `/users/change-password` | POST | 變更密碼 |

### 使用範例

```typescript
const { updateProfile, changePassword } = useUser()

// 更新個人資料
await updateProfile({
  email: 'new@email.com',
  phone: '0912345678',
  departmentId: 'dept-123'
})

// 變更密碼
await changePassword('oldPassword', 'newPassword')
```

### 相關 API 文件
- [2.1 更新使用者資料](./API_REQUIREMENTS.md#21-更新使用者資料-update-user-profile)
- [2.2 變更密碼](./API_REQUIREMENTS.md#22-變更密碼-change-password)

---

## useDepartments.ts

**檔案位置**: `frontend/app/composables/useDepartments.ts`

### 方法對應

| Composable 方法 | API 端點 | HTTP 方法 | 說明 |
|----------------|---------|----------|------|
| `fetchDepartments()` | `/departments` | GET | 取得部門列表 |
| `createDepartment(name)` | `/departments` | POST | 建立新部門 |
| `updateDepartment(id, name)` | `/departments/{departmentId}` | PUT | 更新部門資訊 |
| `deleteDepartment(id)` | `/departments/{departmentId}` | DELETE | 刪除部門 |

### 使用範例

```typescript
const { departments, fetchDepartments, createDepartment, updateDepartment, deleteDepartment } = useDepartments()

// 取得部門列表
await fetchDepartments()
console.log(departments.value)

// 建立新部門
await createDepartment('研發部')

// 更新部門
await updateDepartment('dept-123', '研發一部')

// 刪除部門
await deleteDepartment('dept-123')
```

### 相關 API 文件
- [3.1 取得部門列表](./API_REQUIREMENTS.md#31-取得部門列表-get-departments)
- [3.2 建立部門](./API_REQUIREMENTS.md#32-建立部門-create-department)
- [3.3 更新部門](./API_REQUIREMENTS.md#33-更新部門-update-department)
- [3.4 刪除部門](./API_REQUIREMENTS.md#34-刪除部門-delete-department)

---

## useSuppliers.ts

**檔案位置**: `frontend/app/composables/useSuppliers.ts`

### 方法對應

| Composable 方法 | API 端點 | HTTP 方法 | 說明 |
|----------------|---------|----------|------|
| `fetchSuppliers()` | `/suppliers` | GET | 取得供應商列表 |

### 使用範例

```typescript
const { suppliers, fetchSuppliers } = useSuppliers()

// 取得供應商列表
await fetchSuppliers()
console.log(suppliers.value)
```

### 相關 API 文件
- [4.1 取得供應商列表](./API_REQUIREMENTS.md#41-取得供應商列表-get-suppliers)
- [4.2 取得供應商詳情](./API_REQUIREMENTS.md#42-取得供應商詳情-get-supplier-detail)

---

## useProjects.ts

**檔案位置**: `frontend/app/composables/useProjects.ts`

### 方法對應

| Composable 方法 | API 端點 | HTTP 方法 | 說明 |
|----------------|---------|----------|------|
| `fetchProjects(type)` | `/projects?type={type}` | GET | 取得專案列表 |
| `getProject(id)` | `/projects/{projectId}` | GET | 取得專案詳情 |
| `createProject(data)` | `/projects` | POST | 建立新專案 |
| `updateProject(id, data)` | `/projects/{projectId}` | PUT | 更新專案資訊 |
| `deleteProject(id)` | `/projects/{projectId}` | DELETE | 刪除專案 |

### 使用範例

```typescript
const { projects, fetchProjects, getProject, createProject, updateProject, deleteProject } = useProjects()

// 取得 SAQ 專案列表
await fetchProjects('SAQ')
console.log(projects.value)

// 取得特定專案詳情
const project = await getProject('proj-123')

// 建立新專案
await createProject({
  name: '2024 年度 SAQ',
  year: 2024,
  type: 'SAQ',
  templateId: 'template-123',
  supplierId: 'supplier-456',
  reviewConfig: [
    { stageOrder: 1, departmentId: 'dept-1' },
    { stageOrder: 2, departmentId: 'dept-2' }
  ]
})

// 更新專案
await updateProject('proj-123', {
  name: '2024 年度 SAQ (更新)',
  year: 2024
})

// 刪除專案
await deleteProject('proj-123')
```

### 相關 API 文件
- [5.1 取得專案列表](./API_REQUIREMENTS.md#51-取得專案列表-get-projects)
- [5.2 取得專案詳情](./API_REQUIREMENTS.md#52-取得專案詳情-get-project-detail)
- [5.3 建立專案](./API_REQUIREMENTS.md#53-建立專案-create-project)
- [5.4 更新專案](./API_REQUIREMENTS.md#54-更新專案-update-project)
- [5.5 刪除專案](./API_REQUIREMENTS.md#55-刪除專案-delete-project)
- [5.6 發布專案](./API_REQUIREMENTS.md#56-發布專案-publish-project)

---

## useTemplates.ts

**檔案位置**: `frontend/app/composables/useTemplates.ts`

### 方法對應

| Composable 方法 | API 端點 | HTTP 方法 | 說明 |
|----------------|---------|----------|------|
| `fetchTemplates(type)` | `/templates?type={type}` | GET | 取得範本列表 |
| `getTemplate(id)` | `/templates/{templateId}` | GET | 取得範本詳情 |
| `createTemplate(data)` | `/templates` | POST | 建立新範本 |
| `updateTemplate(id, data)` | `/templates/{templateId}` | PUT | 更新範本資訊 |
| `publishVersion(id)` | `/templates/{templateId}/publish` | POST | 發布新版本 |
| `deleteTemplate(id)` | `/templates/{templateId}` | DELETE | 刪除範本 |

### 使用範例

```typescript
const { templates, fetchTemplates, getTemplate, createTemplate, updateTemplate, publishVersion, deleteTemplate } = useTemplates()

// 取得 SAQ 範本列表
await fetchTemplates('SAQ')
console.log(templates.value)

// 取得特定範本詳情
const template = await getTemplate('template-123')

// 建立新範本
await createTemplate({
  name: 'SAQ 標準範本',
  type: 'SAQ',
  questions: [
    {
      text: '請問貴公司的產品是否符合環保標準？',
      type: 'BOOLEAN',
      required: true
    }
  ]
})

// 更新範本名稱
await updateTemplate('template-123', {
  name: 'SAQ 標準範本 V2'
})

// 發布新版本
await publishVersion('template-123')

// 刪除範本
await deleteTemplate('template-123')
```

### 相關 API 文件
- [6.1 取得範本列表](./API_REQUIREMENTS.md#61-取得範本列表-get-templates)
- [6.2 取得範本詳情](./API_REQUIREMENTS.md#62-取得範本詳情-get-template-detail)
- [6.3 建立範本](./API_REQUIREMENTS.md#63-建立範本-create-template)
- [6.4 更新範本](./API_REQUIREMENTS.md#64-更新範本-update-template)
- [6.5 發布新版本](./API_REQUIREMENTS.md#65-發布新版本-publish-new-version)
- [6.6 刪除範本](./API_REQUIREMENTS.md#66-刪除範本-delete-template)

---

## useAnswers.ts

**檔案位置**: `frontend/app/composables/useAnswers.ts`

### 方法對應

| Composable 方法 | API 端點 | HTTP 方法 | 說明 |
|----------------|---------|----------|------|
| `getAnswers(projectId)` | `/projects/{projectId}/answers` | GET | 取得專案答案 |
| `saveAnswers(projectId, answers)` | `/projects/{projectId}/answers` | POST | 儲存答案 (草稿) |
| `submitAnswers(projectId, answers)` | `/projects/{projectId}/submit` | POST | 提交問卷 |

### 使用範例

```typescript
const { getAnswers, saveAnswers, submitAnswers } = useAnswers()

// 取得專案答案
const answers = await getAnswers('proj-123')

// 儲存答案 (暫存)
await saveAnswers('proj-123', {
  'question-1': { questionId: 'question-1', value: true },
  'question-2': { questionId: 'question-2', value: '符合標準' }
})

// 提交問卷
await submitAnswers('proj-123', {
  'question-1': { questionId: 'question-1', value: true },
  'question-2': { questionId: 'question-2', value: '符合標準' }
})
```

### 相關 API 文件
- [8.1 取得專案答案](./API_REQUIREMENTS.md#81-取得專案答案-get-project-answers)
- [8.2 儲存答案 (草稿)](./API_REQUIREMENTS.md#82-儲存答案-草稿-save-answers---draft)
- [8.3 提交問卷](./API_REQUIREMENTS.md#83-提交問卷-submit-questionnaire)

---

## useReview.ts

**檔案位置**: `frontend/app/composables/useReview.ts`

### 方法對應

| Composable 方法 | API 端點 | HTTP 方法 | 說明 |
|----------------|---------|----------|------|
| `getPendingReviews()` | `/review/pending` | GET | 取得待審核專案列表 |
| `getReviewLogs(projectId)` | `/projects/{projectId}/review-logs` | GET | 取得審核歷程 |
| `approveProject(projectId, comment)` | `/projects/{projectId}/approve` | POST | 核准專案 |
| `returnProject(projectId, comment)` | `/projects/{projectId}/return` | POST | 退回專案 |

### 使用範例

```typescript
const { reviewLogs, getPendingReviews, getReviewLogs, approveProject, returnProject } = useReview()

// 取得待審核專案列表
const pendingReviews = await getPendingReviews()

// 取得審核歷程
await getReviewLogs('proj-123')
console.log(reviewLogs.value)

// 核准專案
await approveProject('proj-123', '資料完整，核准進入下一階段')

// 退回專案
await returnProject('proj-123', '部分資料不完整，請重新填寫')
```

### 相關 API 文件
- [9.1 取得待審核專案列表](./API_REQUIREMENTS.md#91-取得待審核專案列表-get-pending-reviews)
- [9.2 取得專案審核歷程](./API_REQUIREMENTS.md#92-取得專案審核歷程-get-review-logs)
- [9.3 核准專案](./API_REQUIREMENTS.md#93-核准專案-approve-project)
- [9.4 退回專案](./API_REQUIREMENTS.md#94-退回專案-return-project)

---

## API 基礎設施

### useApi.ts

**檔案位置**: `frontend/app/composables/useApi.ts`

這是所有 API composables 的基礎層，提供統一的 HTTP 請求處理。

#### 主要功能

1. **自動注入 JWT Token**: 從 Auth Store 取得 token 並注入到請求標頭
2. **錯誤處理**: 統一處理 API 錯誤並轉換為友善的錯誤訊息
3. **Loading 狀態**: 提供 `isLoading` 狀態供 UI 使用
4. **HTTP 方法包裝**: 提供 `get`, `post`, `put`, `delete`, `patch` 方法

#### 使用範例

```typescript
const api = useApi()

// GET 請求
const data = await api.get('/some-endpoint')

// POST 請求
const result = await api.post('/some-endpoint', { key: 'value' })

// PUT 請求
const updated = await api.put('/some-endpoint/123', { key: 'new-value' })

// DELETE 請求
await api.delete('/some-endpoint/123')

// 檢查 Loading 狀態
if (api.isLoading.value) {
  console.log('Loading...')
}

// 檢查錯誤
if (api.error.value) {
  console.error('Error:', api.error.value.message)
}
```

---

## 型別定義

所有 API 相關的 TypeScript 型別定義位於:

**檔案位置**: `frontend/app/types/index.ts`

### 主要型別

- `User`: 使用者資料結構
- `Department`: 部門資料結構
- `Organization`: 組織資料結構
- `Project`: 專案資料結構
- `Template`: 範本資料結構
- `Question`: 題目資料結構
- `Answer`: 答案資料結構
- `ReviewLog`: 審核紀錄資料結構
- `ProjectStatus`: 專案狀態列舉
- `QuestionType`: 題目類型列舉
- `ReviewAction`: 審核動作列舉

---

## 開發注意事項

### 1. 錯誤處理

所有 composable 方法都會拋出錯誤，建議在使用時加上 try-catch:

```typescript
try {
  await createProject(data)
} catch (error) {
  console.error('Failed to create project:', error)
  // 顯示錯誤訊息給使用者
}
```

### 2. 權限控制

某些 API 端點有角色限制 (HOST vs SUPPLIER)，前端應根據使用者角色顯示/隱藏對應功能。

```typescript
const authStore = useAuthStore()

if (authStore.user?.role === 'HOST') {
  // 顯示製造商功能
} else if (authStore.user?.role === 'SUPPLIER') {
  // 顯示供應商功能
}
```

### 3. 資料重新整理

執行修改操作 (POST, PUT, DELETE) 後，記得重新取得資料以確保畫面顯示最新狀態:

```typescript
// 建立專案後重新載入列表
await createProject(data)
await fetchProjects('SAQ')
```

### 4. 分頁處理

取得列表時注意分頁參數的使用:

```typescript
// 取得第二頁，每頁 50 筆
const projects = await api.get('/projects?type=SAQ&page=2&pageSize=50')
```

---

## 測試相關

測試檔案位於: `frontend/tests/`

在撰寫測試時，可以使用 mock 資料來模擬 API 回應:

```typescript
// 範例：測試 useProjects
import { describe, it, expect, vi } from 'vitest'
import { useProjects } from '~/composables/useProjects'

// Mock useApi
vi.mock('~/composables/useApi', () => ({
  useApi: () => ({
    get: vi.fn().mockResolvedValue({
      data: [{ id: '1', name: 'Test Project' }]
    })
  })
}))

describe('useProjects', () => {
  it('should fetch projects', async () => {
    const { fetchProjects, projects } = useProjects()
    await fetchProjects('SAQ')
    expect(projects.value).toHaveLength(1)
  })
})
```

---

## 相關文件

- [API Requirements Document](./API_REQUIREMENTS.md) - 完整的 API 需求文件
- [Feature Specification](../../specs/003-crm-questionnaire/spec.md) - 功能規格文件
- [OpenAPI Contract](../../specs/003-crm-questionnaire/contracts/openapi.yaml) - OpenAPI 規格

---

## 版本歷史

| 版本 | 日期 | 變更說明 |
|:------|:------|:----------|
| 1.0.0 | 2024-12-02 | 初始版本，對應所有現有 composables |
