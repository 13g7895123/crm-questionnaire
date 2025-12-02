import { ref } from 'vue'
import type { Department } from '~/types/index'

// Mock data
const mockDepartments: Department[] = [
  {
    id: 'dept_it',
    name: 'IT Department',
    organizationId: 'org_host',
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString()
  },
  {
    id: 'dept_sales',
    name: 'Sales Department',
    organizationId: 'org_host',
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString()
  },
  {
    id: 'dept_prod',
    name: 'Production',
    organizationId: 'org_supplier',
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString()
  }
]

export const useDepartments = () => {
  const departments = ref<Department[]>([])
  const loading = ref(false)

  const fetchDepartments = async () => {
    loading.value = true
    try {
      // Simulate API call
      await new Promise(resolve => setTimeout(resolve, 500))
      departments.value = [...mockDepartments]
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
      await new Promise(resolve => setTimeout(resolve, 500))
      const newDept: Department = {
        id: `dept_${Date.now()}`,
        name,
        organizationId: 'org_host',
        createdAt: new Date().toISOString(),
        updatedAt: new Date().toISOString()
      }
      mockDepartments.push(newDept)
      departments.value.push(newDept)
      return { data: newDept }
    } finally {
      loading.value = false
    }
  }

  const updateDepartment = async (id: string, name: string) => {
    loading.value = true
    try {
      await new Promise(resolve => setTimeout(resolve, 500))
      const index = mockDepartments.findIndex(d => d.id === id)
      if (index !== -1) {
        mockDepartments[index].name = name
        const deptIndex = departments.value.findIndex(d => d.id === id)
        if (deptIndex !== -1) {
          departments.value[deptIndex].name = name
        }
        return { data: mockDepartments[index] }
      }
    } finally {
      loading.value = false
    }
  }

  const deleteDepartment = async (id: string) => {
    loading.value = true
    try {
      await new Promise(resolve => setTimeout(resolve, 500))
      const index = mockDepartments.findIndex(d => d.id === id)
      if (index !== -1) {
        mockDepartments.splice(index, 1)
        departments.value = departments.value.filter(d => d.id !== id)
      }
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