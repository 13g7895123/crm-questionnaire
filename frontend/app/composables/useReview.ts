import { ref } from 'vue'
import { useApi } from './useApi'
import type { ReviewLog } from '~/types/index'

export const useReview = () => {
  const api = useApi()
  const reviewLogs = ref<ReviewLog[]>([])

  const getPendingReviews = async () => {
    return await api.get('/review/pending')
  }

  const getReviewLogs = async (projectId: string) => {
    try {
      const response = await api.get(`/projects/${projectId}/review-logs`)
      reviewLogs.value = response.data || []
      return reviewLogs.value
    } catch (error) {
      throw error
    }
  }

  const approveProject = async (projectId: string, comment: string) => {
    return await api.post(`/projects/${projectId}/approve`, { comment })
  }

  const returnProject = async (projectId: string, comment: string) => {
    return await api.post(`/projects/${projectId}/return`, { comment })
  }

  return {
    reviewLogs,
    getPendingReviews,
    getReviewLogs,
    approveProject,
    returnProject
  }
}