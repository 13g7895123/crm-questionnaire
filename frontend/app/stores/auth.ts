import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { User, AuthState } from '~/types/index'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(null)

  const isAuthenticated = computed(() => !!token.value && !!user.value)
  const currentOrganizationId = computed(() => user.value?.organizationId)

  const isClient = () => typeof window !== 'undefined'

  /**
   * Set user
   */
  const setUser = (newUser: User | null) => {
    user.value = newUser
    if (isClient()) {
      if (newUser) {
        localStorage.setItem('auth_user', JSON.stringify(newUser))
      } else {
        localStorage.removeItem('auth_user')
      }
    }
  }

  /**
   * Set authentication token
   */
  const setToken = (newToken: string | null) => {
    token.value = newToken
    if (newToken && isClient()) {
      localStorage.setItem('auth_token', newToken)
    } else if (!newToken && isClient()) {
      localStorage.removeItem('auth_token')
    }
  }

  /**
   * Clear token
   */
  const clearToken = () => {
    setToken(null)
  }

  /**
   * Logout
   */
  const logout = () => {
    setUser(null)
    clearToken()
  }

  /**
   * Restore auth from localStorage
   */
  const restoreAuth = () => {
    if (isClient()) {
      const savedToken = localStorage.getItem('auth_token')
      const savedUser = localStorage.getItem('auth_user')
      
      if (savedToken) {
        token.value = savedToken
      }
      
      if (savedUser) {
        try {
          user.value = JSON.parse(savedUser)
        } catch (e) {
          console.error('Failed to parse user from localStorage', e)
          localStorage.removeItem('auth_user')
        }
      }
    }
  }

  /**
   * Update user profile
   */
  const updateUser = (updates: Partial<User>) => {
    if (user.value) {
      const updatedUser = { ...user.value, ...updates }
      setUser(updatedUser)
    }
  }

  return {
    user,
    token,
    isAuthenticated,
    currentOrganizationId,
    setUser,
    setToken,
    clearToken,
    logout,
    restoreAuth,
    updateUser
  }
})
