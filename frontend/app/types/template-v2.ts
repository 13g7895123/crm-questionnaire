/**
 * Template v2.0 TypeScript Type Definitions
 * Complete type definitions for SAQ template structure, answers, and scoring
 */

// ============================================================================
// Template Structure Types
// ============================================================================

export type QuestionType =
  | 'BOOLEAN'
  | 'TEXT'
  | 'NUMBER'
  | 'RADIO'
  | 'CHECKBOX'
  | 'SELECT'
  | 'DATE'
  | 'FILE'
  | 'TABLE';

export type ConditionalOperator =
  | 'equals'
  | 'notEquals'
  | 'contains'
  | 'greaterThan'
  | 'lessThan'
  | 'greaterThanOrEqual'
  | 'lessThanOrEqual'
  | 'isEmpty'
  | 'isNotEmpty';

export interface Condition {
  operator: ConditionalOperator;
  value: any;
}

export interface FollowUpRule {
  condition: Condition;
  questions: Question[];
}

export interface ConditionalLogic {
  showWhen?: {
    questionId: string;
    condition: Condition;
  } | null;
  followUpQuestions?: FollowUpRule[];
}

export interface QuestionConfig {
  maxLength?: number;
  minValue?: number;
  maxValue?: number;
  options?: Array<{
    value: string;
    label: string;
  }>;
  placeholder?: string;
  helpText?: string;
}

export interface TableColumn {
  id: string;
  label: string;
  label_en?: string;  // 英文標籤
  label_zh?: string;  // 中文標籤
  type: 'text' | 'number' | 'date' | 'email';
  required?: boolean;
}

export interface TableConfig {
  columns: TableColumn[];
  minRows?: number;
  maxRows?: number;
  prefilledRows?: string[]; // 用於預填第一欄 (例如年份: ['2024', '2023', '2022'])
}

export interface Question {
  id: string;
  order: number;
  text: string;
  type: QuestionType;
  required: boolean;
  config?: QuestionConfig;
  conditionalLogic?: ConditionalLogic;
  tableConfig?: TableConfig;
}

export interface Subsection {
  id: string;
  order: number;
  title: string;
  description?: string;
  questions: Question[];
}

export interface Section {
  id: string;
  order: number;
  title: string;
  description?: string;
  subsections: Subsection[];
}

export interface TemplateStructure {
  includeBasicInfo: boolean;
  sections: Section[];
}

export interface Template {
  id: string | number;
  name: string;
  type: 'SAQ' | 'CONFLICT';
  latestVersion: string;
  hasV2Structure: boolean;
  structure?: TemplateStructure;
  createdAt: string;
  updatedAt: string;
}

// ============================================================================
// Basic Info Types (Step 1 for SAQ)
// ============================================================================

export interface Facility {
  location: string;
  address: string;
  area?: string;
  type?: string;
}

export interface Contact {
  name: string;
  title: string;
  department?: string;
  email: string;
  phone: string;
}

export interface EmployeeStatistics {
  total: number;
  male: number;
  female: number;
  foreign: number;
}

export interface BasicInfo {
  companyName: string;
  companyAddress?: string;
  employees: EmployeeStatistics;
  facilities: Facility[];
  certifications: string[];
  rbaOnlineMember?: boolean | null;
  contacts: Contact[];
}

// ============================================================================
// Answer Types
// ============================================================================

export interface TableAnswer {
  [key: string]: string | number; // Dynamic columns based on table config
}

export type AnswerValue =
  | boolean
  | string
  | number
  | string[]
  | TableAnswer[]
  | null;

export interface Answer {
  questionId: string;
  value: AnswerValue;
}

export interface Answers {
  [questionId: string]: {
    questionId: string;
    value: AnswerValue;
  };
}

// ============================================================================
// Review Types
// ============================================================================

export interface QuestionReview {
  questionId: string;
  approved: boolean; // true = Yes, false = No
  comment?: string;
}

export interface Reviews {
  [questionId: string]: QuestionReview;
}

// ============================================================================
// Scoring Types
// ============================================================================

export interface SectionScore {
  score: number;
  maxScore: number;
  weight: number;
  answeredCount: number;
  totalCount: number;
  completionRate: number;
}

export interface ScoreBreakdown {
  sectionId: string;
  sectionName: string;
  score: number;
  maxScore: number;
  weight: number;
  answeredCount: number;
  totalCount: number;
  completionRate: number;
}

export interface ScoreData {
  breakdown: {
    [sectionId: string]: ScoreBreakdown;
  };
  totalScore: number;
  grade: string;
  calculatedAt: string;
}

// ============================================================================
// Validation Types
// ============================================================================

export interface ValidationError {
  [field: string]: string | string[] | ValidationError;
}

export interface MissingRequiredQuestion {
  questionId: string;
  questionText: string;
}

export interface ValidationResult {
  valid: boolean;
  errors: {
    basicInfo?: ValidationError;
    missingRequired?: MissingRequiredQuestion[];
    conditionalLogic?: ValidationError;
    [key: string]: any;
  };
}

// ============================================================================
// API Response Types
// ============================================================================

export interface ApiResponse<T = any> {
  success: boolean;
  data?: T;
  error?: {
    code: string;
    message: string;
  };
  timestamp?: string;
}

export interface PaginatedResponse<T = any> {
  data: T[];
  total: number;
  page: number;
  limit: number;
  totalPages: number;
}

// Template API Responses
export interface GetTemplateStructureResponse {
  templateId: string | number;
  hasV2Structure: boolean;
  structure: TemplateStructure;
}

// Answer API Responses
export interface GetAnswersResponse {
  projectSupplierId: number;
  answers: Answers;
  lastSavedAt?: string;
}

export interface GetBasicInfoResponse {
  projectSupplierId: number;
  basicInfo: BasicInfo | null;
}

export interface GetVisibleQuestionsResponse {
  projectSupplierId: number;
  visibleQuestions: string[];
}

export interface ValidateAnswersResponse {
  projectSupplierId: number;
  valid: boolean;
  errors: ValidationResult['errors'];
}

// ============================================================================
// Form Data Types (for frontend use)
// ============================================================================

export interface QuestionnaireStep {
  stepNumber: number;
  title: string;
  description?: string;
  type: 'basic-info' | 'section' | 'results';
  sectionId?: string;
}

export interface FormState {
  currentStep: number;
  totalSteps: number;
  basicInfo: Partial<BasicInfo>;
  answers: Answers;
  visibleQuestions: Set<string>;
  isDirty: boolean;
  isSaving: boolean;
  lastSavedAt?: string;
}

// ============================================================================
// Utility Types
// ============================================================================

export type DeepPartial<T> = {
  [P in keyof T]?: T[P] extends object ? DeepPartial<T[P]> : T[P];
};

export type RequireAtLeastOne<T, Keys extends keyof T = keyof T> = Pick<
  T,
  Exclude<keyof T, Keys>
> &
  {
    [K in Keys]-?: Required<Pick<T, K>> & Partial<Pick<T, Exclude<Keys, K>>>;
  }[Keys];

// ============================================================================
// Constants
// ============================================================================

export const SECTION_NAMES: Record<string, string> = {
  A: '勞工 (Labor)',
  B: '健康與安全 (Health & Safety)',
  C: '環境 (Environment)',
  D: '道德規範 (Ethics)',
  E: '管理系統 (Management System)',
};

export const GRADE_THRESHOLDS = {
  EXCELLENT: 90,
  GOOD: 80,
  PASS: 70,
  NEEDS_IMPROVEMENT: 60,
} as const;

export const GRADES: Record<string, string> = {
  優秀: 'EXCELLENT',
  良好: 'GOOD',
  合格: 'PASS',
  待改進: 'NEEDS_IMPROVEMENT',
  不合格: 'FAIL',
};
