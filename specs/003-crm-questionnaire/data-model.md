# Data Model (Frontend Types)

## User & Auth

```typescript
interface User {
  id: string;
  username: string;
  email: string;
  phone: string;
  department: string; // Department ID or Name
  role: 'HOST' | 'SUPPLIER'; // Manufacturer (Host) or Supplier
  organizationId: string;
}

interface AuthState {
  user: User | null;
  token: string | null;
  isAuthenticated: boolean;
}
```

## Project (SAQ & Conflict Minerals)

```typescript
type ProjectStatus = 'DRAFT' | 'IN_PROGRESS' | 'SUBMITTED' | 'REVIEWING' | 'APPROVED' | 'RETURNED';

interface Project {
  id: string;
  name: string;
  year: number;
  type: 'SAQ' | 'CONFLICT';
  templateId: string;
  templateVersion: string;
  supplierId: string;
  status: ProjectStatus;
  currentStage: number; // Current review stage index
  reviewConfig: ReviewStageConfig[];
  createdAt: string;
  updatedAt: string;
}

interface ReviewStageConfig {
  stageOrder: number;
  department: string; // Department responsible
  approverId?: string; // Optional specific approver
}
```

## Template & Questions

```typescript
interface Template {
  id: string;
  name: string;
  type: 'SAQ' | 'CONFLICT';
  versions: TemplateVersion[];
  latestVersion: string;
}

interface TemplateVersion {
  version: string;
  questions: Question[];
  createdAt: string;
}

type QuestionType = 'TEXT' | 'NUMBER' | 'DATE' | 'BOOLEAN' | 'SINGLE_CHOICE' | 'MULTI_CHOICE' | 'FILE' | 'RATING';

interface Question {
  id: string;
  text: string;
  type: QuestionType;
  required: boolean;
  options?: string[]; // For choice types
  config?: any; // Additional config (e.g., max file size, rating range)
}
```

## Answering & Review

```typescript
interface Answer {
  questionId: string;
  value: any; // String, number, boolean, or file URL
}

interface ProjectAnswers {
  projectId: string;
  answers: Record<string, Answer>; // Map questionId to Answer
  lastSavedAt: string;
}

interface ReviewLog {
  id: string;
  projectId: string;
  reviewerId: string;
  reviewerName: string;
  stage: number;
  action: 'APPROVE' | 'RETURN';
  comment: string;
  timestamp: string;
}
```
