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
      const baseUrl = process.client ? '' : process.env.API_BASE_URL || 'http://localhost:3000'
      const url = `${baseUrl}/api${endpoint}`

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
        body: options?.body ? JSON.stringify(options.body) : undefined
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

      const data = await response.json() as T
      return data
    } catch (err) {
      const parsed = parseApiError(err)
      error.value = parsed
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
    get,
    post,
    put,
    delete: delete_,
    patch,
    clearError
  }
}
