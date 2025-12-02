import { ref } from 'vue'
import type { Template } from '~/types/index'

// Mock data
const mockTemplates: Template[] = [
  {
    id: 'tpl_saq_2025',
    name: 'SAQ 2025 Standard',
    type: 'SAQ',
    latestVersion: '1.0',
    versions: [
      {
        version: '1.0',
        questions: [],
        createdAt: new Date().toISOString()
      }
    ]
  },
  {
    id: 'tpl_conflict_2025',
    name: 'CMRT 2025',
    type: 'CONFLICT',
    latestVersion: '1.0',
    versions: [
      {
        version: '1.0',
        questions: [],
        createdAt: new Date().toISOString()
      }
    ]
  }
]

export const useTemplates = () => {
  const templates = ref<Template[]>([])
  const loading = ref(false)

  const fetchTemplates = async (type: 'SAQ' | 'CONFLICT' = 'SAQ') => {
    loading.value = true
    try {
      await new Promise(resolve => setTimeout(resolve, 500))
      templates.value = mockTemplates.filter(t => t.type === type)
      return templates.value
    } finally {
      loading.value = false
    }
  }

  const getTemplate = async (id: string) => {
    loading.value = true
    try {
      await new Promise(resolve => setTimeout(resolve, 500))
      return { data: mockTemplates.find(t => t.id === id) }
    } finally {
      loading.value = false
    }
  }

  const createTemplate = async (data: any) => {
    loading.value = true
    try {
      await new Promise(resolve => setTimeout(resolve, 500))
      const newTemplate: Template = {
        id: `tpl_${Date.now()}`,
        name: data.name,
        type: data.type,
        latestVersion: '1.0',
        versions: [
          {
            version: '1.0',
            questions: [],
            createdAt: new Date().toISOString()
          }
        ]
      }
      mockTemplates.push(newTemplate)
      if (data.type === templates.value[0]?.type) {
        templates.value.push(newTemplate)
      }
      return { data: newTemplate }
    } finally {
      loading.value = false
    }
  }

  const updateTemplate = async (id: string, data: any) => {
    loading.value = true
    try {
      await new Promise(resolve => setTimeout(resolve, 500))
      const index = mockTemplates.findIndex(t => t.id === id)
      if (index !== -1) {
        mockTemplates[index] = { ...mockTemplates[index], ...data }
        const tplIndex = templates.value.findIndex(t => t.id === id)
        if (tplIndex !== -1) {
          templates.value[tplIndex] = { ...templates.value[tplIndex], ...data }
        }
        return { data: mockTemplates[index] }
      }
    } finally {
      loading.value = false
    }
  }

  const publishVersion = async (id: string) => {
    // Mock publish logic
    return { success: true }
  }

  const deleteTemplate = async (id: string) => {
    loading.value = true
    try {
      await new Promise(resolve => setTimeout(resolve, 500))
      const index = mockTemplates.findIndex(t => t.id === id)
      if (index !== -1) {
        mockTemplates.splice(index, 1)
        templates.value = templates.value.filter(t => t.id !== id)
      }
    } finally {
      loading.value = false
    }
  }

  return {
    templates,
    loading,
    fetchTemplates,
    getTemplate,
    createTemplate,
    updateTemplate,
    publishVersion,
    deleteTemplate
  }
}