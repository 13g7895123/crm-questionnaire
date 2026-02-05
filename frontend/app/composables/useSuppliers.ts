import { ref } from 'vue'
import { useApi } from './useApi'
import { useAuthStore } from '~/stores/auth'
import type { Organization } from '~/types/index'

export const useSuppliers = () => {
  const api = useApi()
  const suppliers = ref<Organization[]>([])
  const loading = ref(false)

  const fetchSuppliers = async () => {
    loading.value = true
    try {
      const response = await api.get('/suppliers')
      suppliers.value = response.data || []
      return suppliers.value
    } catch (error) {
      throw error
    } finally {
      loading.value = false
    }
  }

  const createSupplier = async (name: string) => {
    loading.value = true
    try {
      const response = await api.post('/organizations', { name, type: 'SUPPLIER' })
      suppliers.value.push(response.data)
      return response
    } finally {
      loading.value = false
    }
  }

  const updateSupplier = async (id: string, name: string) => {
    loading.value = true
    try {
      const response = await api.put(`/organizations/${id}`, { name })
      const index = suppliers.value.findIndex(s => s.id === id)
      if (index !== -1) {
        suppliers.value[index] = response.data
      }
      return response
    } finally {
      loading.value = false
    }
  }

  const deleteSupplier = async (id: string) => {
    loading.value = true
    try {
      await api.delete(`/organizations/${id}`)
      suppliers.value = suppliers.value.filter(s => s.id !== id)
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
        url = `${apiBase}/organizations/import-template`
      } else {
        const baseUrl = process.client ? '' : process.env.API_BASE_URL || 'http://localhost:9104'
        url = `${baseUrl}/api/v1/organizations/import-template`
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
      link.setAttribute('download', '供應商批次匯入範本.xlsx')
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      window.URL.revokeObjectURL(downloadUrl)
    } finally {
      loading.value = false
    }
  }

  return {
    suppliers,
    loading,
    fetchSuppliers,
    createSupplier,
    updateSupplier,
    deleteSupplier,
    downloadImportTemplate
  }
}