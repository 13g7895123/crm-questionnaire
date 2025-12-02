import { ref } from 'vue'
import { useApi } from './useApi'

export const useSuppliers = () => {
  const api = useApi()
  const suppliers = ref([])

  const fetchSuppliers = async () => {
    try {
      const response = await api.get('/suppliers')
      suppliers.value = response.data || []
      return suppliers.value
    } catch (error) {
      throw error
    }
  }

  return {
    suppliers,
    fetchSuppliers
  }
}