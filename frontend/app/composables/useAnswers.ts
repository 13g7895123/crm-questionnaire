import { ref } from 'vue'
import { useApi } from './useApi'
import type { ProjectAnswers } from '~/types/index'

export const useAnswers = () => {
  const api = useApi()

  const getAnswers = async (projectSupplierId: string) => {
    return await api.get(`/project-suppliers/${projectSupplierId}/answers`)
  }

  const saveAnswers = async (projectSupplierId: string, answers: any) => {
    return await api.put(`/project-suppliers/${projectSupplierId}/answers`, { answers })
  }

  const submitAnswers = async (projectSupplierId: string, answers?: any) => {
    // First save answers if provided
    if (answers && Object.keys(answers).length > 0) {
      await api.put(`/project-suppliers/${projectSupplierId}/answers`, { answers })
    }
    // Then submit (no body needed per API doc)
    return await api.post(`/project-suppliers/${projectSupplierId}/submit`, {})
  }

  return {
    getAnswers,
    saveAnswers,
    submitAnswers
  }
}