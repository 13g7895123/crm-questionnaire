import { useApi } from './useApi'
import type { Reviews } from '~/types/template-v2'

export const useQuestionReviews = () => {
    const api = useApi()

    /**
     * Get question reviews for a project supplier
     */
    const getQuestionReviews = async (projectSupplierId: string) => {
        return await api.get(`/project-suppliers/${projectSupplierId}/question-reviews`)
    }

    /**
     * Save/update question reviews (batch)
     */
    const saveQuestionReviews = async (projectSupplierId: string, reviews: Reviews) => {
        return await api.put(`/project-suppliers/${projectSupplierId}/question-reviews`, { reviews })
    }

    return {
        getQuestionReviews,
        saveQuestionReviews
    }
}
