/**
 * Base API Composable using Nuxt 3 useFetch
 * Provides centralized API handling with error management and auth token injection
 */

import { ref, computed } from 'vue'
import { useAuthStore } from '~/stores/auth'
import { parseApiError, handleResponseStatus } from '~/utils/api-error'
import type { ErrorResponse } from '~/utils/api-error'

interface UseFetchOptions {
  method?: 'GET' | 'POST' | 'PUT' | 'DELETE' | 'PATCH'
  body?: any
  headers?: Record<string, string>
  retry?: number
  timeout?: number
}

export const useApi = () => {
  const authStore = useAuthStore()
  const isLoading = ref(false)
  const error = ref<ErrorResponse | null>(null)
  const { showSystemAlert } = useSweetAlert()

  /**
   * Fetch with automatic token injection and error handling
   */
  const fetchApi = async <T = any>(
    endpoint: string,
    options?: UseFetchOptions
  ): Promise<T> => {
    isLoading.value = true
    error.value = null

    try {
      const config = useRuntimeConfig()
      // 優先使用 runtimeConfig.public.apiBase
      const apiBase = config.public.apiBase || ''

      // 如果 apiBase 是以 http 開頭，則直接拼接
      // 如果是非 http 開頭（如 /api/v1），則讓它自動拼接 host
      let url = ''
      if (apiBase.startsWith('http')) {
        // endpoint 已經包含 / 了，所以直接拼
        url = `${apiBase}${endpoint}`
      } else {
        // 相對路徑模式
        const baseUrl = process.client ? '' : process.env.API_BASE_URL || 'http://localhost:9104'
        url = `${baseUrl}/api/v1${endpoint}`
      }

      const headers: Record<string, string> = {
        'Content-Type': 'application/json',
        ...options?.headers
      }

      // Inject auth token if available
      if (authStore.token) {
        headers['Authorization'] = `Bearer ${authStore.token}`
      }

      const response = await fetch(url, {
        method: options?.method || 'GET',
        headers,
        body: options?.body ? JSON.stringify(options.body) : undefined,
        credentials: 'include'
      })

      // Handle non-2xx responses
      if (!response.ok) {
        let data
        try {
          data = await response.json()
        } catch {
          data = null
        }
        handleResponseStatus(response.status, data)
      }

      // Handle 204 No Content
      if (response.status === 204) {
        return null as T
      }

      const data = await response.json() as T
      return data
    } catch (err) {
      const parsed = parseApiError(err)
      error.value = parsed

      if (parsed.statusCode === 401) {
        if (process.client) {
          await showSystemAlert('登入過期', 'warning')
          await navigateTo('/login')
        }
      }

      throw parsed
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Upload FormData (for file uploads)
   * Note: Does not set Content-Type header to let browser set it with boundary
   */
  const uploadFormData = async <T = any>(
    endpoint: string,
    formData: FormData
  ): Promise<T> => {
    isLoading.value = true
    error.value = null

    try {
      const config = useRuntimeConfig()
      const apiBase = config.public.apiBase || ''

      let url = ''
      if (apiBase.startsWith('http')) {
        url = `${apiBase}${endpoint}`
      } else {
        const baseUrl = process.client ? '' : process.env.API_BASE_URL || 'http://localhost:9104'
        url = `${baseUrl}/api/v1${endpoint}`
      }

      const headers: Record<string, string> = {}

      // Inject auth token if available
      if (authStore.token) {
        headers['Authorization'] = `Bearer ${authStore.token}`
      }

      const response = await fetch(url, {
        method: 'POST',
        headers,
        body: formData,
        credentials: 'include'
      })

      // Handle non-2xx responses
      if (!response.ok) {
        let data
        try {
          data = await response.json()
        } catch {
          data = null
        }
        handleResponseStatus(response.status, data)
      }

      // Handle 204 No Content
      if (response.status === 204) {
        return null as T
      }

      const data = await response.json() as T
      return data
    } catch (err) {
      const parsed = parseApiError(err)
      error.value = parsed

      if (parsed.statusCode === 401) {
        if (process.client) {
          await showSystemAlert('登入過期', 'warning')
          await navigateTo('/login')
        }
      }

      throw parsed
    } finally {
      isLoading.value = false
    }
  }

  /**
   * GET request
   */
  const get = <T = any>(endpoint: string, options?: Omit<UseFetchOptions, 'method' | 'body'>) =>
    fetchApi<T>(endpoint, { ...options, method: 'GET' })

  /**
   * POST request
   */
  const post = <T = any>(endpoint: string, body?: any, options?: Omit<UseFetchOptions, 'method' | 'body'>) =>
    fetchApi<T>(endpoint, { ...options, method: 'POST', body })

  /**
   * PUT request
   */
  const put = <T = any>(endpoint: string, body?: any, options?: Omit<UseFetchOptions, 'method' | 'body'>) =>
    fetchApi<T>(endpoint, { ...options, method: 'PUT', body })

  /**
   * DELETE request
   */
  const delete_ = <T = any>(endpoint: string, options?: Omit<UseFetchOptions, 'method' | 'body'>) =>
    fetchApi<T>(endpoint, { ...options, method: 'DELETE' })

  /**
   * PATCH request
   */
  const patch = <T = any>(endpoint: string, body?: any, options?: Omit<UseFetchOptions, 'method' | 'body'>) =>
    fetchApi<T>(endpoint, { ...options, method: 'PATCH', body })

  /**
   * Clear error
   */
  const clearError = () => {
    error.value = null
  }

  return {
    isLoading: computed(() => isLoading.value),
    error: computed(() => error.value),
    fetchApi,
    uploadFormData,
    get,
    post,
    put,
    delete: delete_,
    patch,
    clearError
  }
}

