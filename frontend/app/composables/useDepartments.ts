import { ref } from 'vue'
import { useApi } from './useApi'
import type { Department } from '~/types/index'

export const useDepartments = () => {
  const api = useApi()
  const departments = ref<Department[]>([])

  const fetchDepartments = async () => {
    try {
      const response = await api.get('/departments')
      departments.value = response.data || []
      return departments.value
    } catch (error) {
      throw error
    }
  }

  const createDepartment = async (name: string) => {
    return await api.post('/departments', { name })
  }

  const updateDepartment = async (id: string, name: string) => {
    return await api.put(`/departments/${id}`, { name })
  }

  const deleteDepartment = async (id: string) => {
    return await api.delete(`/departments/${id}`)
  }

  return {
    departments,
    fetchDepartments,
    createDepartment,
    updateDepartment,
    deleteDepartment
  }
}