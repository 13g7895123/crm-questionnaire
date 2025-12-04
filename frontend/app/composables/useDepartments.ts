import { ref } from 'vue'
import { useApi } from './useApi'
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

  const createDepartment = async (name: string) => {
    loading.value = true
    try {
      const response = await api.post('/departments', { name })
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

  return {
    departments,
    loading,
    fetchDepartments,
    createDepartment,
    updateDepartment,
    deleteDepartment
  }
}