import { useApi } from './useApi'
import { useAuthStore } from '~/stores/auth'

export const useAuth = () => {
  const api = useApi()
  const authStore = useAuthStore()

  const login = async (username: string, password: string) => {
    try {
      const response = await api.post('/auth/login', { username, password })
      authStore.setToken(response.token)
      authStore.setUser(response.user)
      return response
    } catch (error) {
      throw error
    }
  }

  const logout = async () => {
    try {
      await api.post('/auth/logout')
    } catch (error) {
      console.error('Logout failed:', error)
    } finally {
      authStore.logout()
    }
  }

  return {
    login,
    logout
  }
}