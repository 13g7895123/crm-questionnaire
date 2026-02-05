/**
 * API Error Handling Utility
 * Handles network errors, server errors, and provides user-friendly messages
 */

export class ApiError extends Error {
  constructor(
    public statusCode: number,
    public code: string,
    message: string,
    public details?: any
  ) {
    super(message)
    this.name = 'ApiError'
  }
}

export interface ErrorResponse {
  code: string
  message: string
  statusCode: number
  details?: any
}

/**
 * Parse error from fetch response or network error
 * Handles EC-003: Network disconnection errors
 */
export function parseApiError(error: any): ErrorResponse {
  // Network disconnection error (EC-003)
  if (error instanceof TypeError && error.message.includes('fetch')) {
    return {
      code: 'NETWORK_ERROR',
      message: '網絡連接錯誤，請檢查您的網絡連接',
      statusCode: 0,
      details: error.message
    }
  }

  // Timeout error
  if (error?.code === 'ETIMEDOUT' || error?.message?.includes('timeout')) {
    return {
      code: 'TIMEOUT_ERROR',
      message: '請求超時，請重試',
      statusCode: 0,
      details: error.message
    }
  }

  // API response error
  if (error instanceof ApiError) {
    return {
      code: error.code,
      message: error.message,
      statusCode: error.statusCode,
      details: error.details
    }
  }

  // JSON parse error
  if (error instanceof SyntaxError && error.message.includes('JSON')) {
    return {
      code: 'INVALID_JSON',
      message: '伺服器回應格式錯誤',
      statusCode: 500,
      details: error.message
    }
  }

  // Unknown error
  return {
    code: 'UNKNOWN_ERROR',
    message: '發生未知錯誤，請稍後重試',
    statusCode: 500,
    details: error?.message || String(error)
  }
}

/**
 * Handle HTTP response status codes
 */
export function handleResponseStatus(status: number, data?: any): void {
  // Extract error info from backend response structure
  // Backend returns: { success: false, error: { code, message, details }, timestamp }
  const errorInfo = data?.error || {}
  const code = errorInfo.code || `HTTP_${status}`
  const message = errorInfo.message || '發生錯誤'
  const details = errorInfo.details || null

  switch (status) {
    case 400:
      throw new ApiError(400, code, message || '請求參數錯誤', details)
    case 401:
      throw new ApiError(401, code, message || '未授權，請重新登入', details)
    case 403:
      throw new ApiError(403, code, message || '無權限存取此資源', details)
    case 404:
      throw new ApiError(404, code, message || '資源不存在', details)
    case 409:
      throw new ApiError(409, code, message || '資源衝突，請重新載入頁面', details)
    case 422:
      throw new ApiError(422, code, message || '資料驗證失敗', details)
    case 429:
      throw new ApiError(429, code, message || '請求過於頻繁，請稍候再試', details)
    case 500:
      throw new ApiError(500, code, message || '伺服器錯誤，請稍後重試', details)
    case 503:
      throw new ApiError(503, code, message || '服務暫時不可用，請稍後重試', details)
  }

  if (status >= 400) {
    throw new ApiError(status, code, message || `HTTP ${status} 錯誤`, details)
  }
}

/**
 * Check if error is retryable
 */
export function isRetryableError(error: ErrorResponse): boolean {
  const retryableStatuses = [0, 408, 429, 500, 502, 503, 504]
  const retryableCodes = [
    'NETWORK_ERROR',
    'TIMEOUT_ERROR',
    'TOO_MANY_REQUESTS',
    'INTERNAL_SERVER_ERROR',
    'SERVICE_UNAVAILABLE'
  ]

  return (
    retryableStatuses.includes(error.statusCode) ||
    retryableCodes.includes(error.code)
  )
}
