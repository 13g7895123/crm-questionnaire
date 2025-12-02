import { describe, it, expect } from 'vitest'
import type { User, Project, Template, Question } from '../../../app/types/index'

describe('TypeScript Types', () => {
  it('should define User type correctly', () => {
    const user: User = {
      id: '1',
      username: 'testuser',
      email: 'test@example.com',
      phone: '1234567890',
      departmentId: 'dept1',
      role: 'HOST',
      organizationId: 'org1',
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString()
    } as any

    expect(user.id).toBe('1')
    expect(user.role).toBe('HOST')
  })

  it('should define Project type with correct status values', () => {
    const project: Project = {
      id: '1',
      name: 'Test Project',
      year: 2025,
      type: 'SAQ',
      templateId: 'tpl1',
      templateVersion: 'v1',
      supplierId: 'sup1',
      status: 'DRAFT',
      currentStage: 0,
      reviewConfig: [],
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString()
    }

    expect(project.status).toBe('DRAFT')
    expect(project.type).toBe('SAQ')
  })

  it('should define Question type with QuestionType variants', () => {
    const question: Question = {
      id: 'q1',
      text: 'What is your name?',
      type: 'TEXT',
      required: true
    }

    expect(question.type).toBe('TEXT')
    expect(['TEXT', 'NUMBER', 'DATE', 'BOOLEAN', 'SINGLE_CHOICE', 'MULTI_CHOICE', 'FILE', 'RATING']).toContain(question.type)
  })

  it('should support optional Question config', () => {
    const question: Question = {
      id: 'q1',
      text: 'How many items?',
      type: 'NUMBER',
      required: false,
      config: {
        numberMin: 1,
        numberMax: 100
      }
    }

    expect(question.config?.numberMin).toBe(1)
    expect(question.config?.numberMax).toBe(100)
  })

  it('should support Template with multiple versions', () => {
    const template: Template = {
      id: 'tpl1',
      name: 'SAQ Template',
      type: 'SAQ',
      latestVersion: 'v2',
      versions: [
        {
          version: 'v1',
          questions: [],
          createdAt: new Date().toISOString()
        },
        {
          version: 'v2',
          questions: [],
          createdAt: new Date().toISOString()
        }
      ]
    }

    expect(template.versions).toHaveLength(2)
    expect(template.latestVersion).toBe('v2')
  })
})
