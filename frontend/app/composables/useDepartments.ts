import { ref } from 'vue'
import { useApi } from './useApi'
import { useAuthStore } from '~/stores/auth'
import type { Department } from '~/types/index'

export const useDepartments = () => {
  const api = useApi()
  const departments = ref<Department[]>([])
  const loading = ref(false)

  const fetchDepartments = async () => {
    loading.value = true
    try {
      const response = await api.get('/departments')
      departments.value = response.data || []
      return departments.value
    } catch (error) {
      throw error
    } finally {
      loading.value = false
    }
  }

  const createDepartment = async (name: string, organizationId?: string) => {
    loading.value = true
    try {
      const payload: any = { name }
      if (organizationId) {
        payload.organizationId = organizationId
      }
      const response = await api.post('/departments', payload)
      departments.value.push(response.data)
      return response
    } finally {
      loading.value = false
    }
  }

  const updateDepartment = async (id: string, name: string) => {
    loading.value = true
    try {
      const response = await api.put(`/departments/${id}`, { name })
      const index = departments.value.findIndex(d => d.id === id)
      if (index !== -1) {
        departments.value[index] = response.data
      }
      return response
    } finally {
      loading.value = false
    }
  }

  const deleteDepartment = async (id: string) => {
    loading.value = true
    try {
      await api.delete(`/departments/${id}`)
      departments.value = departments.value.filter(d => d.id !== id)
    } finally {
      loading.value = false
    }
  }

  const downloadImportTemplate = async () => {
    loading.value = true
    try {
      const authStore = useAuthStore()
      const config = useRuntimeConfig()
      const apiBase = config.public.apiBase || ''

      let url = ''
      if (apiBase.startsWith('http')) {
        url = `${apiBase}/departments/import-template`
      } else {
        const baseUrl = process.client ? '' : process.env.API_BASE_URL || 'http://localhost:9104'
        url = `${baseUrl}/api/v1/departments/import-template`
      }

      const response = await fetch(url, {
        method: 'GET',
        headers: {
          'Authorization': `Bearer ${authStore.token}`
        }
      })

      if (!response.ok) {
        throw new Error('下載範本失敗')
      }

      const blob = await response.blob()
      const downloadUrl = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = downloadUrl
      link.setAttribute('download', '部門批次匯入範本.xlsx')
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      window.URL.revokeObjectURL(downloadUrl)
    } finally {
      loading.value = false
    }
  }

  return {
    departments,
    loading,
    fetchDepartments,
    createDepartment,
    updateDepartment,
    deleteDepartment,
    downloadImportTemplate
  }
}