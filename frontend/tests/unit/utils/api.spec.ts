import { describe, it, expect } from 'vitest'
import { parseApiError, handleResponseStatus, isRetryableError, ApiError } from '../../../app/utils/api-error'

describe('API Error Utilities', () => {
  describe('parseApiError', () => {
    it('should parse network error (EC-003)', () => {
      const error = new TypeError('Failed to fetch')
      const result = parseApiError(error)

      expect(result.code).toBe('NETWORK_ERROR')
      expect(result.message).toContain('網絡連接錯誤')
      expect(result.statusCode).toBe(0)
    })

    it('should parse timeout error', () => {
      const error = new Error('Request timeout')
      error.code = 'ETIMEDOUT'
      const result = parseApiError(error)

      expect(result.code).toBe('TIMEOUT_ERROR')
      expect(result.message).toContain('請求超時')
    })

    it('should parse ApiError instance', () => {
      const apiError = new ApiError(401, 'UNAUTHORIZED', 'Invalid token')
      const result = parseApiError(apiError)

      expect(result.code).toBe('UNAUTHORIZED')
      expect(result.statusCode).toBe(401)
      expect(result.message).toBe('Invalid token')
    })

    it('should parse JSON parse error', () => {
      const error = new SyntaxError('Unexpected token in JSON at position 0')
      const result = parseApiError(error)

      expect(result.code).toBe('INVALID_JSON')
      expect(result.message).toContain('格式錯誤')
    })

    it('should handle unknown errors', () => {
      const error = new Error('Some random error')
      const result = parseApiError(error)

      expect(result.code).toBe('UNKNOWN_ERROR')
      expect(result.statusCode).toBe(500)
    })
  })

  describe('handleResponseStatus', () => {
    it('should throw for 400 Bad Request', () => {
      expect(() => handleResponseStatus(400)).toThrow(ApiError)
      try {
        handleResponseStatus(400)
      } catch (e: any) {
        expect(e.code).toBe('BAD_REQUEST')
      }
    })

    it('should throw for 401 Unauthorized', () => {
      expect(() => handleResponseStatus(401)).toThrow(ApiError)
      try {
        handleResponseStatus(401)
      } catch (e: any) {
        expect(e.code).toBe('UNAUTHORIZED')
      }
    })

    it('should throw for 403 Forbidden', () => {
      expect(() => handleResponseStatus(403)).toThrow(ApiError)
      try {
        handleResponseStatus(403)
      } catch (e: any) {
        expect(e.code).toBe('FORBIDDEN')
      }
    })

    it('should throw for 404 Not Found', () => {
      expect(() => handleResponseStatus(404)).toThrow(ApiError)
      try {
        handleResponseStatus(404)
      } catch (e: any) {
        expect(e.code).toBe('NOT_FOUND')
      }
    })

    it('should throw for 500 Internal Server Error', () => {
      expect(() => handleResponseStatus(500)).toThrow(ApiError)
      try {
        handleResponseStatus(500)
      } catch (e: any) {
        expect(e.code).toBe('INTERNAL_SERVER_ERROR')
      }
    })

    it('should not throw for 2xx status codes', () => {
      expect(() => handleResponseStatus(200)).not.toThrow()
      expect(() => handleResponseStatus(201)).not.toThrow()
    })
  })

  describe('isRetryableError', () => {
    it('should mark network error as retryable', () => {
      const error = {
        code: 'NETWORK_ERROR',
        message: 'Network error',
        statusCode: 0
      }
      expect(isRetryableError(error)).toBe(true)
    })

    it('should mark timeout error as retryable', () => {
      const error = {
        code: 'TIMEOUT_ERROR',
        message: 'Timeout',
        statusCode: 0
      }
      expect(isRetryableError(error)).toBe(true)
    })

    it('should mark 503 Service Unavailable as retryable', () => {
      const error = {
        code: 'SERVICE_UNAVAILABLE',
        message: 'Service unavailable',
        statusCode: 503
      }
      expect(isRetryableError(error)).toBe(true)
    })

    it('should mark 401 Unauthorized as non-retryable', () => {
      const error = {
        code: 'UNAUTHORIZED',
        message: 'Unauthorized',
        statusCode: 401
      }
      expect(isRetryableError(error)).toBe(false)
    })

    it('should mark 404 Not Found as non-retryable', () => {
      const error = {
        code: 'NOT_FOUND',
        message: 'Not found',
        statusCode: 404
      }
      expect(isRetryableError(error)).toBe(false)
    })
  })
})
