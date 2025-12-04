import { ref } from 'vue'
import { useApi } from './useApi'
import type { User } from '~/types/index'

export const useUsers = () => {
  const api = useApi()
  const users = ref<User[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  const fetchUsers = async () => {
    loading.value = true
    error.value = null
    try {
      const response = await api.get('/users')
      users.value = response.data || []
      return users.value
    } catch (e: any) {
      error.value = e.message || 'Failed to fetch users'
      throw e
    } finally {
      loading.value = false
    }
  }

  const createUser = async (userData: Partial<User>) => {
    loading.value = true
    try {
      const response = await api.post('/users', userData)
      users.value.push(response.data)
      return response
    } catch (e: any) {
      throw new Error(e.message || 'Failed to create user')
    } finally {
      loading.value = false
    }
  }

  const updateUser = async (id: string, updates: Partial<User>) => {
    loading.value = true
    try {
      const response = await api.put(`/users/${id}`, updates)
      const index = users.value.findIndex(u => u.id === id)
      if (index !== -1) {
        users.value[index] = response.data
      }
      return response
    } catch (e: any) {
      throw new Error(e.message || 'Failed to update user')
    } finally {
      loading.value = false
    }
  }

  const deleteUser = async (id: string) => {
    loading.value = true
    try {
      await api.delete(`/users/${id}`)
      users.value = users.value.filter(u => u.id !== id)
    } catch (e: any) {
      throw new Error(e.message || 'Failed to delete user')
    } finally {
      loading.value = false
    }
  }

  return {
    users,
    loading,
    error,
    fetchUsers,
    createUser,
    updateUser,
    deleteUser
  }
}
