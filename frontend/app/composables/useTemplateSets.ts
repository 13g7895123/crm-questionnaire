import { ref } from 'vue'
import { useApi } from './useApi'

/**
 * 範本組管理 Composable
 * Template Set = 一組 CMRT/EMRT/AMRT 範本的組合
 */

export interface TemplateSetTemplate {
    enabled: boolean
    version: string
    minerals?: string[]  // 僅用於 AMRT
}

export interface TemplateSet {
    id: string
    name: string
    year: number
    description?: string
    templates: {
        cmrt?: TemplateSetTemplate
        emrt?: TemplateSetTemplate
        amrt?: TemplateSetTemplate
    }
    createdAt?: string
    updatedAt?: string
}

export interface TemplateSetCreateInput {
    name: string
    year: number
    description?: string
    templates: {
        cmrt?: TemplateSetTemplate
        emrt?: TemplateSetTemplate
        amrt?: TemplateSetTemplate
    }
}

export const useTemplateSets = () => {
    const api = useApi()
    const templateSets = ref<TemplateSet[]>([])
    const loading = ref(false)

    /**
     * 取得範本組列表
     */
    const fetchTemplateSets = async () => {
        loading.value = true
        try {
            const response = await api.get('/rm/template-sets')
            templateSets.value = response.data || []
            return templateSets.value
        } catch (error) {
            throw error
        } finally {
            loading.value = false
        }
    }

    /**
     * 取得單一範本組
     */
    const getTemplateSet = async (id: string) => {
        try {
            return await api.get(`/rm/template-sets/${id}`)
        } catch (error) {
            throw error
        }
    }

    /**
     * 建立範本組
     */
    const createTemplateSet = async (data: TemplateSetCreateInput) => {
        try {
            return await api.post('/rm/template-sets', data)
        } catch (error) {
            throw error
        }
    }

    /**
     * 更新範本組
     */
    const updateTemplateSet = async (id: string, data: Partial<TemplateSetCreateInput>) => {
        try {
            return await api.put(`/rm/template-sets/${id}`, data)
        } catch (error) {
            throw error
        }
    }

    /**
     * 刪除範本組
     */
    const deleteTemplateSet = async (id: string) => {
        try {
            return await api.delete(`/rm/template-sets/${id}`)
        } catch (error) {
            throw error
        }
    }

    /**
     * 取得範本組的摘要資訊（用於顯示）
     */
    const getTemplateSetSummary = (templateSet: TemplateSet): string => {
        const enabledTemplates: string[] = []

        if (templateSet.templates.cmrt?.enabled) {
            enabledTemplates.push(`CMRT ${templateSet.templates.cmrt.version}`)
        }
        if (templateSet.templates.emrt?.enabled) {
            enabledTemplates.push(`EMRT ${templateSet.templates.emrt.version}`)
        }
        if (templateSet.templates.amrt?.enabled) {
            const mineralCount = templateSet.templates.amrt.minerals?.length || 0
            enabledTemplates.push(`AMRT ${templateSet.templates.amrt.version} (${mineralCount} 種礦產)`)
        }

        return enabledTemplates.length > 0
            ? enabledTemplates.join(' + ')
            : '未設定任何範本'
    }

    /**
     * 取得範本組中已啟用的範本類型
     */
    const getEnabledTemplateTypes = (templateSet: TemplateSet): Array<'CMRT' | 'EMRT' | 'AMRT'> => {
        const types: Array<'CMRT' | 'EMRT' | 'AMRT'> = []

        if (templateSet.templates.cmrt?.enabled) types.push('CMRT')
        if (templateSet.templates.emrt?.enabled) types.push('EMRT')
        if (templateSet.templates.amrt?.enabled) types.push('AMRT')

        return types
    }

    return {
        // 狀態
        templateSets,
        loading,

        // 方法
        fetchTemplateSets,
        getTemplateSet,
        createTemplateSet,
        updateTemplateSet,
        deleteTemplateSet,

        // 工具函數
        getTemplateSetSummary,
        getEnabledTemplateTypes
    }
}
