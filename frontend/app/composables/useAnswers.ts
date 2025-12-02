import { ref } from 'vue'
import { useApi } from './useApi'
import type { ProjectAnswers } from '~/types/index'

export const useAnswers = () => {
  const api = useApi()

  const getAnswers = async (projectId: string) => {
    return await api.get(`/projects/${projectId}/answers`)
  }

  const saveAnswers = async (projectId: string, answers: any) => {
    return await api.post(`/projects/${projectId}/answers`, { answers })
  }

  const submitAnswers = async (projectId: string, answers: any) => {
    return await api.post(`/projects/${projectId}/submit`, { answers })
  }

  return {
    getAnswers,
    saveAnswers,
    submitAnswers
  }
}