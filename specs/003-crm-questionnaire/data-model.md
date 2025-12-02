# Data Model (Frontend Types)

## Organization & Department

```typescript
type OrganizationType = 'HOST' | 'SUPPLIER'; // Manufacturer (Host) or Supplier

interface Organization {
  id: string;
  name: string;
  type: OrganizationType;
  createdAt: string;
  updatedAt: string;
}

interface Department {
  id: string;
  name: string;
  organizationId: string;
  createdAt: string;
  updatedAt: string;
}
```

## User & Auth

```typescript
interface User {
  id: string;
  username: string;
  email: string;
  phone: string;
  departmentId: string; // Reference to Department.id
  department?: Department; // Populated Department object
  role: 'HOST' | 'SUPPLIER'; // Manufacturer (Host) or Supplier
  organizationId: string;
  organization?: Organization; // Populated Organization object
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
  departmentId: string; // Reference to Department.id
  department?: Department; // Populated Department object
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
  config?: QuestionConfig; // Additional config
}

interface QuestionConfig {
  maxFileSize?: number; // For FILE type, in bytes
  allowedFileTypes?: string[]; // For FILE type, e.g., ['pdf', 'jpg', 'png']
  ratingMin?: number; // For RATING type, default: 1
  ratingMax?: number; // For RATING type, default: 5
  ratingStep?: number; // For RATING type, default: 1
  numberMin?: number; // For NUMBER type
  numberMax?: number; // For NUMBER type
  maxLength?: number; // For TEXT type
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
