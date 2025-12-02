import { ref } from 'vue'
import type { Organization } from '~/types/index'

// Mock data
const mockSuppliers: Organization[] = [
  {
    id: 'org_supplier_1',
    name: 'Tech Components Ltd.',
    type: 'SUPPLIER',
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString()
  },
  {
    id: 'org_supplier_2',
    name: 'Global Materials Inc.',
    type: 'SUPPLIER',
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString()
  },
  {
    id: 'org_supplier_3',
    name: 'Eco Packaging Solutions',
    type: 'SUPPLIER',
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString()
  }
]

export const useSuppliers = () => {
  const suppliers = ref<Organization[]>([])
  const loading = ref(false)

  const fetchSuppliers = async () => {
    loading.value = true
    try {
      // Simulate API call
      await new Promise(resolve => setTimeout(resolve, 500))
      suppliers.value = [...mockSuppliers]
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
      await new Promise(resolve => setTimeout(resolve, 500))
      const newSupplier: Organization = {
        id: `org_supplier_${Date.now()}`,
        name,
        type: 'SUPPLIER',
        createdAt: new Date().toISOString(),
        updatedAt: new Date().toISOString()
      }
      mockSuppliers.push(newSupplier)
      suppliers.value.push(newSupplier)
      return { data: newSupplier }
    } finally {
      loading.value = false
    }
  }

  const updateSupplier = async (id: string, name: string) => {
    loading.value = true
    try {
      await new Promise(resolve => setTimeout(resolve, 500))
      const index = mockSuppliers.findIndex(s => s.id === id)
      if (index !== -1) {
        mockSuppliers[index].name = name
        const supIndex = suppliers.value.findIndex(s => s.id === id)
        if (supIndex !== -1) {
          suppliers.value[supIndex].name = name
        }
        return { data: mockSuppliers[index] }
      }
    } finally {
      loading.value = false
    }
  }

  const deleteSupplier = async (id: string) => {
    loading.value = true
    try {
      await new Promise(resolve => setTimeout(resolve, 500))
      const index = mockSuppliers.findIndex(s => s.id === id)
      if (index !== -1) {
        mockSuppliers.splice(index, 1)
        suppliers.value = suppliers.value.filter(s => s.id !== id)
      }
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