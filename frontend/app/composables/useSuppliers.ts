import { ref } from 'vue'
import { useApi } from './useApi'
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

  return {
    suppliers,
    loading,
    fetchSuppliers,
    createSupplier,
    updateSupplier,
    deleteSupplier
  }
}