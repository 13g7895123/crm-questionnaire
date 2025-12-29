import { ref } from 'vue'
import { useApi } from './useApi'
import type { ReviewLog } from '~/types/index'

export const useReview = () => {
  const api = useApi()
  const reviewLogs = ref<ReviewLog[]>([])

  const getPendingReviews = async () => {
    return await api.get('/reviews/pending')
  }

  const getReviewLogs = async (projectSupplierId: string) => {
    try {
      const response = await api.get(`/project-suppliers/${projectSupplierId}/reviews`)
      reviewLogs.value = response.data?.reviews || []
      // Return the full data object so we can access projectId/meta info
      return response.data
    } catch (error) {
      throw error
    }
  }

  const approveProject = async (projectSupplierId: string, comment: string, details?: any) => {
    return await api.post(`/project-suppliers/${projectSupplierId}/review`, {
      action: 'APPROVE',
      comment,
      details
    })
  }

  const returnProject = async (projectSupplierId: string, comment: string, details?: any) => {
    return await api.post(`/project-suppliers/${projectSupplierId}/review`, {
      action: 'RETURN',
      comment,
      details
    })
  }

  return {
    reviewLogs,
    getPendingReviews,
    getReviewLogs,
    approveProject,
    returnProject
  }
}