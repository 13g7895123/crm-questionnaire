import { ref } from 'vue'
import { useApi } from './useApi'

/**
 * 責任礦產問卷系統 Composable
 * 支援 CMRT、EMRT、AMRT 三種範本
 */

export interface TemplateType {
    cmrt_required: boolean
    emrt_required: boolean
    amrt_required: boolean
    amrt_minerals?: string[]
}

export interface SupplierAssignment {
    id: number
    supplierId: number
    supplierName: string
    supplierEmail: string
    cmrt_required: boolean
    emrt_required: boolean
    amrt_required: boolean
    amrt_minerals: string[] | null
    status: 'not_assigned' | 'assigned' | 'in_progress' | 'completed'
    lastUpdated?: string
}

export interface ProgressData {
    summary: {
        totalSuppliers: number
        assignedSuppliers: number
        notAssignedSuppliers: number
        completedSuppliers: number
        inProgressSuppliers: number
        notStartedSuppliers: number
    }
    templateStats: {
        cmrt: { total: number; completed: number; percentage: number }
        emrt: { total: number; completed: number; percentage: number }
        amrt: { total: number; completed: number; percentage: number }
    }
    suppliers: Array<{
        supplierId: number
        supplierName: string
        assignedTemplates: string[]
        status: string
        completionRate: number
        lastUpdated: string
    }>
}

export const useResponsibleMinerals = () => {
    const api = useApi()
    const suppliers = ref<SupplierAssignment[]>([])
    const progressData = ref<ProgressData | null>(null)

    /**
     * 取得專案的供應商列表與範本狀態
     */
    const fetchSuppliersWithTemplates = async (projectId: number) => {
        try {
            const response = await api.get(`/rm/projects/${projectId}/suppliers`)
            suppliers.value = response.data.data || response.data || []
            return suppliers.value
        } catch (error) {
            throw error
        }
    }

    /**
     * 設定單一供應商的範本
     */
    const assignTemplateToSupplier = async (
        projectId: number,
        assignmentId: number,
        templates: TemplateType
    ) => {
        try {
            return await api.put(
                `/rm/projects/${projectId}/suppliers/${assignmentId}/templates`,
                templates
            )
        } catch (error) {
            throw error
        }
    }

    /**
     * 批量設定範本
     */
    const batchAssignTemplates = async (
        projectId: number,
        assignmentIds: number[],
        templates: TemplateType
    ) => {
        try {
            return await api.post(
                `/rm/projects/${projectId}/suppliers/batch-assign-templates`,
                {
                    assignmentIds,
                    templates
                }
            )
        } catch (error) {
            throw error
        }
    }

    /**
     * Excel 匯入範本指派
     */
    const importTemplateAssignments = async (projectId: number, file: File) => {
        try {
            const formData = new FormData()
            formData.append('file', file)

            return await api.post(
                `/rm/projects/${projectId}/suppliers/import`,
                formData,
                {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                }
            )
        } catch (error) {
            throw error
        }
    }

    /**
     * 通知單一供應商
     */
    const notifySupplier = async (projectId: number, assignmentId: number) => {
        try {
            return await api.post(
                `/rm/projects/${projectId}/suppliers/${assignmentId}/notify`
            )
        } catch (error) {
            throw error
        }
    }

    /**
     * 批量通知所有已指派範本的供應商
     */
    const notifyAllSuppliers = async (projectId: number) => {
        try {
            return await api.post(`/rm/projects/${projectId}/suppliers/notify-all`)
        } catch (error) {
            throw error
        }
    }

    /**
     * 取得專案進度統計
     */
    const fetchProgress = async (projectId: number) => {
        try {
            const response = await api.get(`/rm/projects/${projectId}/progress`)
            progressData.value = response.data.data || response.data
            return progressData.value
        } catch (error) {
            throw error
        }
    }

    /**
     * 取得供應商明細狀態
     */
    const fetchSuppliersStatus = async (projectId: number) => {
        try {
            return await api.get(`/rm/projects/${projectId}/suppliers/status`)
        } catch (error) {
            throw error
        }
    }

    /**
     * 匯出進度報表
     */
    const exportProgress = async (projectId: number) => {
        try {
            const response = await api.get(
                `/rm/projects/${projectId}/export`,
                {
                    responseType: 'blob'
                }
            )

            // 建立下載連結
            const url = window.URL.createObjectURL(new Blob([response.data]))
            const link = document.createElement('a')
            link.href = url
            link.setAttribute('download', `project_${projectId}_progress.xlsx`)
            document.body.appendChild(link)
            link.click()
            document.body.removeChild(link)
            window.URL.revokeObjectURL(url)

            return response
        } catch (error) {
            throw error
        }
    }

    /**
     * 匯出冶煉廠彙整報表 (Roll-up)
     */
    const exportConsolidatedReport = async (projectId: number) => {
        try {
            const response = await api.get(
                `/rm/projects/${projectId}/consolidated-report`,
                {
                    responseType: 'blob'
                }
            )

            const url = window.URL.createObjectURL(new Blob([response.data]))
            const link = document.createElement('a')
            link.href = url
            link.setAttribute('download', `Consolidated_Smelters_Project_${projectId}.xlsx`)
            document.body.appendChild(link)
            link.click()
            document.body.removeChild(link)
            window.URL.revokeObjectURL(url)

            return response
        } catch (error) {
            throw error
        }
    }

    /**
     * 下載範本指派 Excel 範本
     */
    const downloadTemplateAssignmentTemplate = () => {
        // 建立範本 CSV 內容
        const csvContent = [
            ['供應商編號', 'CMRT', 'EMRT', 'AMRT', 'AMRT礦產'],
            ['SUP001', 'Yes', 'Yes', 'No', ''],
            ['SUP002', 'Yes', 'No', 'Yes', 'Silver,Platinum'],
            ['SUP003', 'No', 'Yes', 'No', '']
        ]
            .map(row => row.join(','))
            .join('\n')

        const blob = new Blob(['\ufeff' + csvContent], {
            type: 'text/csv;charset=utf-8;'
        })
        const url = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', 'template_assignments_template.csv')
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        window.URL.revokeObjectURL(url)
    }

    /**
     * 取得指派資訊 (對供應商 Portal)
     */
    const getAssignmentInfo = async (assignmentId: number) => {
        try {
            const response = await api.get(`/rm/questionnaires/${assignmentId}`)
            return response.data.data
        } catch (error) {
            throw error
        }
    }

    /**
     * 匯入問卷 Excel
     */
    const importQuestionnaire = async (assignmentId: number, file: File) => {
        try {
            const formData = new FormData()
            formData.append('file', file)

            const response = await api.post(`/rm/questionnaires/${assignmentId}/import`, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            })
            return response.data
        } catch (error) {
            throw error
        }
    }

    /**
     * 手動儲存問卷 (線上表單)
     */
    const saveQuestionnaire = async (assignmentId: number, data: any) => {
        try {
            const response = await api.post(`/rm/questionnaires/${assignmentId}/save`, data)
            return response.data
        } catch (error) {
            throw error
        }
    }

    /**
     * 提交問卷
     */
    const submitQuestionnaire = async (assignmentId: number, templateType: string) => {
        try {
            const response = await api.post(`/rm/questionnaires/${assignmentId}/submit`, {
                template_type: templateType
            })
            return response.data
        } catch (error) {
            throw error
        }
    }

    /**
     * 取得待審核清單
     */
    const fetchPendingReviews = async () => {
        try {
            const response = await api.get('/rm/reviews/pending')
            return response.data.data
        } catch (error) {
            throw error
        }
    }

    /**
     * 執行審核 (核准/退回)
     */
    const reviewAssignment = async (assignmentId: number, action: 'approve' | 'return', comment: string) => {
        try {
            const response = await api.post(`/rm/reviews/${assignmentId}`, {
                action,
                comment
            })
            return response.data
        } catch (error) {
            throw error
        }
    }

    /**
     * 取得審核歷史歷程
     */
    const fetchReviewHistory = async (assignmentId: number) => {
        try {
            const response = await api.get(`/rm/reviews/${assignmentId}/history`)
            return response.data.data
        } catch (error) {
            throw error
        }
    }

    return {
        // 狀態
        suppliers,
        progressData,

        // 供應商範本管理
        fetchSuppliersWithTemplates,
        assignTemplateToSupplier,
        batchAssignTemplates,
        importTemplateAssignments,

        // 通知功能
        notifySupplier,
        notifyAllSuppliers,

        // 進度追蹤
        fetchProgress,
        fetchSuppliersStatus,
        exportProgress,
        exportConsolidatedReport,

        // 問卷填寫 (Portal)
        getAssignmentInfo,
        importQuestionnaire,
        submitQuestionnaire,
        saveQuestionnaire,

        // 審核管理
        fetchPendingReviews,
        reviewAssignment,
        fetchReviewHistory,

        // 工具函數
        downloadTemplateAssignmentTemplate
    }
}
