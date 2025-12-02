import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '../../../app/stores/auth'

// Mock localStorage for tests
const localStorageMock = (() => {
  let store: Record<string, string> = {}
  return {
    getItem: (key: string) => store[key] || null,
    setItem: (key: string, value: string) => {
      store[key] = value.toString()
    },
    removeItem: (key: string) => {
      delete store[key]
    },
    clear: () => {
      store = {}
    }
  }
})()

// Set up global localStorage mock
Object.defineProperty(window, 'localStorage', {
  value: localStorageMock
})

describe('Auth Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    // Clear localStorage
    localStorage.clear()
  })

  afterEach(() => {
    localStorage.clear()
  })

  it('should initialize with empty state', () => {
    const store = useAuthStore()
    expect(store.user).toBeNull()
    expect(store.token).toBeNull()
    expect(store.isAuthenticated).toBe(false)
  })

  it('should login and set user and token', () => {
    const store = useAuthStore()
    const mockUser = {
      id: 'user1',
      username: 'testuser',
      email: 'test@example.com',
      phone: '123456',
      departmentId: 'dept1',
      role: 'HOST' as const,
      organizationId: 'org1'
    }
    const mockToken = 'mock-jwt-token'

    store.setUser(mockUser)
    store.setToken(mockToken)

    expect(store.user).toEqual(mockUser)
    expect(store.token).toBe(mockToken)
    expect(store.isAuthenticated).toBe(true)
  })

  it('should logout and clear auth state', () => {
    const store = useAuthStore()
    const mockUser = {
      id: 'user1',
      username: 'testuser',
      email: 'test@example.com',
      phone: '123456',
      departmentId: 'dept1',
      role: 'HOST' as const,
      organizationId: 'org1'
    }

    store.setUser(mockUser)
    store.setToken('token')

    store.logout()

    expect(store.user).toBeNull()
    expect(store.token).toBeNull()
    expect(store.isAuthenticated).toBe(false)
  })

  it('should persist token in localStorage', () => {
    const store = useAuthStore()
    const mockToken = 'persistent-token'

    store.setToken(mockToken)

    expect(localStorage.getItem('auth_token')).toBe(mockToken)
  })

  it('should restore token from localStorage', () => {
    localStorage.setItem('auth_token', 'restored-token')
    const store = useAuthStore()

    store.restoreAuth()

    expect(store.token).toBe('restored-token')
  })

  it('should handle token expiration', () => {
    const store = useAuthStore()
    store.setToken('valid-token')

    store.clearToken()

    expect(store.token).toBeNull()
    expect(store.isAuthenticated).toBe(false)
  })
})
