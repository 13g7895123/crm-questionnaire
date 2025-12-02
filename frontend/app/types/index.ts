/**
 * TypeScript Types for CRM Questionnaire System
 */

export type OrganizationType = 'HOST' | 'SUPPLIER'
export type ProjectStatus = 'DRAFT' | 'IN_PROGRESS' | 'SUBMITTED' | 'REVIEWING' | 'APPROVED' | 'RETURNED'
export type QuestionType = 'TEXT' | 'NUMBER' | 'DATE' | 'BOOLEAN' | 'SINGLE_CHOICE' | 'MULTI_CHOICE' | 'FILE' | 'RATING'
export type ReviewAction = 'APPROVE' | 'RETURN'

// Organization & Department
export interface Organization {
  id: string
  name: string
  type: OrganizationType
  createdAt: string
  updatedAt: string
}

export interface Department {
  id: string
  name: string
  organizationId: string
  createdAt: string
  updatedAt: string
}

// User & Auth
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

export interface AuthState {
  user: User | null
  token: string | null
  isAuthenticated: boolean
}

// Project
export interface ReviewStageConfig {
  stageOrder: number
  departmentId: string
  department?: Department
  approverId?: string
}

export interface Project {
  id: string
  name: string
  year: number
  type: 'SAQ' | 'CONFLICT'
  templateId: string
  templateVersion: string
  supplierId: string
  status: ProjectStatus
  currentStage: number
  reviewConfig: ReviewStageConfig[]
  createdAt: string
  updatedAt: string
}

// Template & Questions
export interface QuestionConfig {
  maxFileSize?: number
  allowedFileTypes?: string[]
  ratingMin?: number
  ratingMax?: number
  ratingStep?: number
  numberMin?: number
  numberMax?: number
  maxLength?: number
}

export interface Question {
  id: string
  text: string
  type: QuestionType
  required: boolean
  options?: string[]
  config?: QuestionConfig
}

export interface TemplateVersion {
  version: string
  questions: Question[]
  createdAt: string
}

export interface Template {
  id: string
  name: string
  type: 'SAQ' | 'CONFLICT'
  versions: TemplateVersion[]
  latestVersion: string
}

// Answering & Review
export interface Answer {
  questionId: string
  value: any
}

export interface ProjectAnswers {
  projectId: string
  answers: Record<string, Answer>
  lastSavedAt: string
}

export interface ReviewLog {
  id: string
  projectId: string
  reviewerId: string
  reviewerName: string
  stage: number
  action: ReviewAction
  comment: string
  timestamp: string
}

// API Response types
export interface ApiResponse<T> {
  data?: T
  error?: string
  message?: string
}

export interface PaginatedResponse<T> {
  items: T[]
  total: number
  page: number
  pageSize: number
}
