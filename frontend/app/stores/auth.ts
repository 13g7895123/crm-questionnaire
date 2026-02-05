import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { User, AuthState } from '~/types/index'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const config = useRuntimeConfig()
  
  // 判斷是否為生產環境：檢查 API base 是否使用 HTTPS
  const isSecure = config.public.apiBase?.startsWith('https://') || false
  
  // Cookie 配置：根據環境自動調整
  const tokenCookie = useCookie('auth_token', {
    maxAge: 60 * 60 * 24 * 7, // 7 days
    path: '/',
    sameSite: 'lax', // 防止 CSRF 攻擊
    secure: isSecure, // HTTPS 環境必須為 true
    // domain 不設置，讓瀏覽器自動處理當前域名
  })
  const token = computed(() => tokenCookie.value || null)

  const isAuthenticated = computed(() => !!token.value)
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
    tokenCookie.value = newToken
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
      const savedUser = localStorage.getItem('auth_user')

      // User info is in localStorage, token is in cookie automatically
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
