# 資料模型 (Data Models)

## 概述

本文件定義 CRM 問卷系統的所有資料模型與型別定義，所有型別定義存放於 `~/types/index.ts`。

## 核心實體模型

### Organization (組織)

```typescript
export type OrganizationType = 'HOST' | 'SUPPLIER'

export interface Organization {
  id: string
  name: string
  type: OrganizationType
  createdAt: string
  updatedAt: string
}
```

**說明**:
- `HOST`: 製造商（主辦方）
- `SUPPLIER`: 供應商

---

### Department (部門)

```typescript
export interface Department {
  id: string
  name: string
  organizationId: string
  createdAt: string
  updatedAt: string
}
```

**關聯**:
- 屬於一個 `Organization`

---

### User (使用者)

```typescript
export interface User {
  id: string
  username: string
  email: string
  phone: string
  departmentId: string
  department?: Department
  role: 'HOST' | 'SUPPLIER'
  organizationId: string
  organization?: Organization
}
```

**關聯**:
- 屬於一個 `Organization`
- 屬於一個 `Department`

**角色**:
- `HOST`: 製造商使用者，可管理專案、範本、審核
- `SUPPLIER`: 供應商使用者，僅能填寫被指派的問卷

---

### AuthState (認證狀態)

```typescript
export interface AuthState {
  user: User | null
  token: string | null
  isAuthenticated: boolean
}
```

**說明**: 用於 Pinia Store 管理認證狀態

---

## 專案相關模型

### Project (專案)

```typescript
export type ProjectStatus = 
  | 'DRAFT'        // 草稿
  | 'IN_PROGRESS'  // 進行中
  | 'SUBMITTED'    // 已提交
  | 'REVIEWING'    // 審核中
  | 'APPROVED'     // 已核准
  | 'RETURNED'     // 已退回

export interface Project {
  id: string
  name: string
  year: number
  type: 'SAQ' | 'CONFLICT'
  templateId: string
  templateVersion: string
  supplierId: string
  supplier?: Supplier
  status: ProjectStatus
  currentStage: number
  reviewConfig: ReviewStageConfig[]
  createdAt: string
  updatedAt: string
  submittedAt?: string
  approvedAt?: string
  returnedAt?: string
}
```

**關聯**:
- 使用一個 `Template` 的特定版本
- 指派給一個 `Supplier`
- 包含多個 `ReviewStageConfig`（審核流程設定）

---

### ReviewStageConfig (審核階段設定)

```typescript
export interface ReviewStageConfig {
  stageOrder: number      // 審核階段順序（1-5）
  departmentId: string    // 負責審核的部門 ID
  department?: Department // 部門資訊（查詢時包含）
  approverId?: string     // 審核者 ID（選填）
}
```

**說明**:
- 定義專案的多階段審核流程
- `stageOrder` 必須連續（1, 2, 3...）
- 每個專案可設定 1-5 個審核階段

---

### Supplier (供應商)

```typescript
export interface Supplier {
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

**關聯**:
- 屬於一個 `Organization` (type = 'SUPPLIER')

---

## 範本與題目模型

### Template (範本)

```typescript
export interface Template {
  id: string
  name: string
  type: 'SAQ' | 'CONFLICT'
  versions: TemplateVersion[]
  latestVersion: string
  createdAt: string
  updatedAt: string
}
```

**關聯**:
- 包含多個 `TemplateVersion`

---

### TemplateVersion (範本版本)

```typescript
export interface TemplateVersion {
  version: string           // 版本號，如 "v1.2.0"
  questions: Question[]
  createdAt: string
}
```

**說明**:
- 範本的特定版本快照
- 版本號格式：`v{major}.{minor}.{patch}`

---

### Question (題目)

```typescript
export type QuestionType = 
  | 'TEXT'          // 簡答題
  | 'NUMBER'        // 數字題
  | 'DATE'          // 日期題
  | 'BOOLEAN'       // 是非題
  | 'SINGLE_CHOICE' // 單選題
  | 'MULTI_CHOICE'  // 多選題
  | 'FILE'          // 檔案上傳
  | 'RATING'        // 評分量表

export interface Question {
  id: string
  text: string
  type: QuestionType
  required: boolean
  options?: string[]        // 單選/多選題的選項
  config?: QuestionConfig   // 題目特定設定
}
```

---

### QuestionConfig (題目設定)

```typescript
export interface QuestionConfig {
  // 檔案上傳設定
  maxFileSize?: number           // 最大檔案大小（bytes）
  allowedFileTypes?: string[]    // 允許的檔案類型（副檔名）
  
  // 評分量表設定
  ratingMin?: number             // 最小分數
  ratingMax?: number             // 最大分數
  ratingStep?: number            // 分數間隔
  
  // 數字題設定
  numberMin?: number             // 最小值
  numberMax?: number             // 最大值
  
  // 文字題設定
  maxLength?: number             // 最大字元數
  
  // 多選題設定
  minSelections?: number         // 最少選擇數
  maxSelections?: number         // 最多選擇數
  
  // 日期題設定
  minDate?: string               // 最小日期
  maxDate?: string               // 最大日期
}
```

---

## 答案相關模型

### Answer (答案)

```typescript
export interface Answer {
  questionId: string
  value: any  // 值的型別依題目類型而定
}
```

**值的型別**:
- `TEXT`: `string`
- `NUMBER`: `number`
- `DATE`: `string` (ISO 8601 格式)
- `BOOLEAN`: `boolean`
- `SINGLE_CHOICE`: `string`
- `MULTI_CHOICE`: `string[]`
- `FILE`: `FileAnswer`
- `RATING`: `number`

---

### FileAnswer (檔案答案)

```typescript
export interface FileAnswer {
  filename: string
  url: string
  size: number
  type: string
  uploadedAt: string
}
```

---

### ProjectAnswers (專案答案)

```typescript
export interface ProjectAnswers {
  projectId: string
  answers: Record<string, Answer>  // key 為 questionId
  lastSavedAt: string
}
```

**範例**:
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
        "filename": "cert.pdf",
        "url": "https://...",
        "size": 2048576,
        "type": "application/pdf"
      }
    }
  },
  "lastSavedAt": "2025-12-02T05:52:15.355Z"
}
```

---

## 審核相關模型

### ReviewLog (審核記錄)

```typescript
export type ReviewAction = 'APPROVE' | 'RETURN'

export interface ReviewLog {
  id: string
  projectId: string
  reviewerId: string
  reviewerName: string
  reviewerDepartment: string
  stage: number           // 審核階段
  action: ReviewAction    // 審核動作
  comment: string         // 審核意見
  timestamp: string
}
```

---

## API 回應型別

### ApiResponse (通用 API 回應)

```typescript
export interface ApiResponse<T> {
  data?: T
  error?: string
  message?: string
}
```

**使用範例**:
```typescript
// 單一資源
ApiResponse<Project>

// 資源列表
ApiResponse<Project[]>
```

---

### PaginatedResponse (分頁回應)

```typescript
export interface PaginatedResponse<T> {
  items: T[]
  total: number
  page: number
  pageSize: number
}
```

**使用範例**:
```typescript
// 分頁的專案列表
PaginatedResponse<Project>
```

---

## 型別使用範例

### 在 Composable 中使用

```typescript
import type { Project, ProjectStatus } from '~/types'

export const useProjects = () => {
  const projects = ref<Project[]>([])
  
  const fetchProjects = async (type: 'SAQ' | 'CONFLICT'): Promise<Project[]> => {
    const response = await api.get<{ data: Project[] }>(`/projects?type=${type}`)
    projects.value = response.data || []
    return projects.value
  }
  
  const filterByStatus = (status: ProjectStatus): Project[] => {
    return projects.value.filter(p => p.status === status)
  }
  
  return {
    projects,
    fetchProjects,
    filterByStatus
  }
}
```

---

### 在 Vue 元件中使用

```vue
<script setup lang="ts">
import type { Project, Question, Answer } from '~/types'

const project = ref<Project | null>(null)
const answers = ref<Record<string, Answer>>({})

const handleAnswerChange = (question: Question, value: any) => {
  answers.value[question.id] = {
    questionId: question.id,
    value
  }
}
</script>

<template>
  <div v-if="project">
    <h1>{{ project.name }}</h1>
    <p>狀態：{{ project.status }}</p>
  </div>
</template>
```

---

### 型別守衛 (Type Guards)

```typescript
// 檢查是否為檔案答案
export const isFileAnswer = (value: any): value is FileAnswer => {
  return value && 
    typeof value === 'object' && 
    'filename' in value && 
    'url' in value
}

// 檢查專案狀態
export const isProjectEditable = (project: Project): boolean => {
  return ['DRAFT', 'IN_PROGRESS', 'RETURNED'].includes(project.status)
}

export const isProjectReviewable = (project: Project): boolean => {
  return project.status === 'REVIEWING'
}
```

---

## 列舉值 (Enums)

### ProjectStatus 流程

```
DRAFT (草稿)
  ↓
IN_PROGRESS (進行中)
  ↓
SUBMITTED (已提交)
  ↓
REVIEWING (審核中)
  ↓
APPROVED (已核准)

REVIEWING (審核中)
  ↓
RETURNED (已退回) → IN_PROGRESS
```

---

### QuestionType 說明

| 型別 | 說明 | 值型別 | 範例 |
|------|------|--------|------|
| TEXT | 簡答題 | string | "這是答案" |
| NUMBER | 數字題 | number | 123 |
| DATE | 日期題 | string | "2025-12-02" |
| BOOLEAN | 是非題 | boolean | true |
| SINGLE_CHOICE | 單選題 | string | "選項 A" |
| MULTI_CHOICE | 多選題 | string[] | ["選項 A", "選項 C"] |
| FILE | 檔案上傳 | FileAnswer | { filename, url, ... } |
| RATING | 評分量表 | number | 4 |

---

## 資料驗證

### Zod Schema (建議)

```typescript
import { z } from 'zod'

// Project Schema
export const ProjectSchema = z.object({
  id: z.string(),
  name: z.string().min(1).max(100),
  year: z.number().min(2020).max(2100),
  type: z.enum(['SAQ', 'CONFLICT']),
  status: z.enum(['DRAFT', 'IN_PROGRESS', 'SUBMITTED', 'REVIEWING', 'APPROVED', 'RETURNED']),
  // ...
})

// Question Schema
export const QuestionSchema = z.object({
  id: z.string(),
  text: z.string().min(1),
  type: z.enum(['TEXT', 'NUMBER', 'DATE', 'BOOLEAN', 'SINGLE_CHOICE', 'MULTI_CHOICE', 'FILE', 'RATING']),
  required: z.boolean(),
  options: z.array(z.string()).optional(),
  config: z.any().optional()
})
```

---

## 關聯圖

### 實體關聯圖 (ER Diagram)

```
Organization (組織)
  ├── type: HOST | SUPPLIER
  ├── 1:N → Department (部門)
  ├── 1:N → User (使用者)
  └── (if SUPPLIER) 1:N → Project (專案, as supplier)

Department (部門)
  ├── N:1 → Organization
  ├── 1:N → User
  └── N:M → Project (透過 ReviewStageConfig)

User (使用者)
  ├── N:1 → Organization
  ├── N:1 → Department
  └── role: HOST | SUPPLIER

Template (範本)
  ├── type: SAQ | CONFLICT
  ├── 1:N → TemplateVersion
  └── 1:N → Project (使用範本)

TemplateVersion (範本版本)
  ├── N:1 → Template
  └── 1:N → Question

Question (題目)
  ├── N:1 → TemplateVersion
  └── type: TEXT | NUMBER | DATE | ...

Project (專案)
  ├── N:1 → Template (+ version)
  ├── N:1 → Supplier
  ├── 1:N → ReviewStageConfig
  ├── 1:N → Answer
  └── 1:N → ReviewLog

Answer (答案)
  ├── N:1 → Project
  └── N:1 → Question

ReviewLog (審核記錄)
  ├── N:1 → Project
  └── N:1 → User (reviewer)
```

---

## 資料庫考量

### 索引建議

```sql
-- Projects
CREATE INDEX idx_projects_type ON projects(type);
CREATE INDEX idx_projects_status ON projects(status);
CREATE INDEX idx_projects_supplier ON projects(supplier_id);
CREATE INDEX idx_projects_year ON projects(year);

-- Answers
CREATE INDEX idx_answers_project ON answers(project_id);
CREATE INDEX idx_answers_question ON answers(question_id);

-- ReviewLogs
CREATE INDEX idx_review_logs_project ON review_logs(project_id);
CREATE INDEX idx_review_logs_reviewer ON review_logs(reviewer_id);

-- Users
CREATE INDEX idx_users_department ON users(department_id);
CREATE INDEX idx_users_organization ON users(organization_id);
```

---

## TypeScript 最佳實踐

### 1. 使用 Type 而非 Any

```typescript
// ❌ 不好
const data: any = await api.get('/projects')

// ✅ 好
const data: Project[] = await api.get<Project[]>('/projects')
```

### 2. 使用 Optional Chaining

```typescript
// ❌ 不好
const deptName = project.supplier.organization.name

// ✅ 好
const deptName = project.supplier?.organization?.name
```

### 3. 使用 Type Guards

```typescript
// ✅ 好
if (isFileAnswer(answer.value)) {
  console.log(answer.value.filename)
}
```

### 4. 匯出型別

```typescript
// ~/types/index.ts
export type { Project, Template, Question, Answer }
export type { QuestionType, ProjectStatus, ReviewAction }
```

---

## 測試型別

```typescript
import type { Project, ProjectStatus } from '~/types'

describe('Type Guards', () => {
  it('should identify editable projects', () => {
    const project: Project = {
      id: 'p-001',
      status: 'DRAFT',
      // ...
    }
    
    expect(isProjectEditable(project)).toBe(true)
  })
  
  it('should identify file answers', () => {
    const fileAnswer = {
      filename: 'test.pdf',
      url: 'https://...',
      size: 1024,
      type: 'application/pdf'
    }
    
    expect(isFileAnswer(fileAnswer)).toBe(true)
  })
})
```

---

## 相關文件

- [API 需求文件](../API-REQUIREMENTS.md)
- [專案管理 API](./projects.md)
- [範本管理 API](./templates.md)
- [問卷填寫與答案 API](./answers.md)
- [錯誤處理規範](./error-handling.md)
