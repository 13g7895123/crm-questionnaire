import { ref } from 'vue'
import { useApi } from './useApi'
import type { Template } from '~/types/index'

export const useTemplates = () => {
  const api = useApi()
  const templates = ref<Template[]>([])

  const fetchTemplates = async (type: 'SAQ' | 'CONFLICT' = 'SAQ') => {
    try {
      const response = await api.get(`/templates?type=${type}`)
      templates.value = response.data || []
      return templates.value
    } catch (error) {
      throw error
    }
  }

  const getTemplate = async (id: string) => {
    return await api.get(`/templates/${id}`)
  }

  const createTemplate = async (data: any) => {
    return await api.post('/templates', data)
  }

  const updateTemplate = async (id: string, data: any) => {
    return await api.put(`/templates/${id}`, data)
  }

  const publishVersion = async (id: string) => {
    return await api.post(`/templates/${id}/publish`, {})
  }

  const deleteTemplate = async (id: string) => {
    return await api.delete(`/templates/${id}`)
  }

  return {
    templates,
    fetchTemplates,
    getTemplate,
    createTemplate,
    updateTemplate,
    publishVersion,
    deleteTemplate
  }
}