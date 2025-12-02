import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { User, AuthState } from '~/types/index'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(null)

  const isAuthenticated = computed(() => !!token.value && !!user.value)

  const isClient = () => typeof window !== 'undefined'

  /**
   * Set user
   */
  const setUser = (newUser: User | null) => {
    user.value = newUser
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
    user.value = null
    clearToken()
  }

  /**
   * Restore auth from localStorage
   */
  const restoreAuth = () => {
    if (isClient()) {
      const savedToken = localStorage.getItem('auth_token')
      if (savedToken) {
        token.value = savedToken
      }
    }
  }

  /**
   * Update user profile
   */
  const updateUser = (updates: Partial<User>) => {
    if (user.value) {
      user.value = { ...user.value, ...updates }
    }
  }

  return {
    user,
    token,
    isAuthenticated,
    setUser,
    setToken,
    clearToken,
    logout,
    restoreAuth,
    updateUser
  }
})
