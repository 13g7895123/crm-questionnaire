import { useApi } from './useApi'
import { useAuthStore } from '~/stores/auth'

export const useAuth = () => {
  const api = useApi()
  const authStore = useAuthStore()

  const login = async (username: string, password: string) => {
    try {
      const response = await api.post('/auth/login', { username, password })
      // API returns { success: true, data: { accessToken, user, ... } }
      if (response.data && response.data.accessToken) {
        authStore.setToken(response.data.accessToken)
        authStore.setUser(response.data.user)
      } else {
        throw new Error('Invalid login response')
      }
      return response
    } catch (error) {
      console.error('Login error:', error)
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