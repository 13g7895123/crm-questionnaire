import { ref } from 'vue'
import { useApi } from './useApi'
import type { Template } from '~/types/index'

export const useTemplates = () => {
  const api = useApi()
  const templates = ref<Template[]>([])
  const loading = ref(false)

  const fetchTemplates = async (type: 'SAQ' | 'CONFLICT' = 'SAQ') => {
    loading.value = true
    try {
      const response = await api.get(`/templates?type=${type}`)
      templates.value = response.data || []
      return templates.value
    } catch (error) {
      throw error
    } finally {
      loading.value = false
    }
  }

  const getTemplate = async (id: string) => {
    loading.value = true
    try {
      return await api.get(`/templates/${id}`)
    } finally {
      loading.value = false
    }
  }

  const createTemplate = async (data: any) => {
    loading.value = true
    try {
      const response = await api.post('/templates', data)
      templates.value.push(response.data)
      return response
    } finally {
      loading.value = false
    }
  }

  const updateTemplate = async (id: string, data: any) => {
    loading.value = true
    try {
      const response = await api.put(`/templates/${id}`, data)
      const index = templates.value.findIndex(t => t.id === id)
      if (index !== -1) {
        templates.value[index] = response.data
      }
      return response
    } finally {
      loading.value = false
    }
  }

  const getTemplateStructure = async (id: string) => {
    loading.value = true
    try {
      return await api.get(`/templates/${id}/structure`)
    } finally {
      loading.value = false
    }
  }

  const saveTemplateStructure = async (id: string, data: any) => {
    loading.value = true
    try {
      return await api.put(`/templates/${id}/structure`, data)
    } finally {
      loading.value = false
    }
  }

  const publishVersion = async (id: string, data: any) => {
    return await api.post(`/templates/${id}/versions`, data)
  }

  const deleteTemplate = async (id: string) => {
    loading.value = true
    try {
      await api.delete(`/templates/${id}`)
      templates.value = templates.value.filter(t => t.id !== id)
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
    getTemplateStructure,
    saveTemplateStructure,
    publishVersion,
    deleteTemplate
  }
}