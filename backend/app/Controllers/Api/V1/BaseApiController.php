<?php

namespace App\Controllers\Api\V1;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;

class BaseApiController extends ResourceController
{
    protected $format = 'json';

    /**
     * Get current authenticated user data
     */
    protected function getCurrentUser(): ?array
    {
        return $this->request->user ?? null;
    }

    /**
     * Get current user ID
     */
    protected function getCurrentUserId(): ?string
    {
        return $this->getCurrentUser()['userId'] ?? null;
    }

    /**
     * Get current user role
     */
    protected function getCurrentUserRole(): ?string
    {
        return $this->getCurrentUser()['role'] ?? null;
    }

    /**
     * Get current user organization ID
     */
    protected function getCurrentOrganizationId(): ?string
    {
        return $this->getCurrentUser()['organizationId'] ?? null;
    }

    /**
     * Get current user department ID
     */
    protected function getCurrentDepartmentId(): ?string
    {
        return $this->getCurrentUser()['departmentId'] ?? null;
    }

    /**
     * Check if current user is HOST
     */
    protected function isHost(): bool
    {
        return $this->getCurrentUserRole() === 'HOST';
    }

    /**
     * Check if current user is SUPPLIER
     */
    protected function isSupplier(): bool
    {
        return $this->getCurrentUserRole() === 'SUPPLIER';
    }

    /**
     * Check if current user is ADMIN
     */
    protected function isAdmin(): bool
    {
        return $this->getCurrentUserRole() === 'ADMIN';
    }

    /**
     * Success response
     */
    protected function successResponse($data, int $statusCode = 200, ?string $message = null): ResponseInterface
    {
        $response = ['success' => true];
        
        if ($message) {
            $response['message'] = $message;
        }
        
        if ($data !== null) {
            $response['data'] = $data;
        }

        return $this->respond($response, $statusCode);
    }

    /**
     * Success response with pagination
     */
    protected function paginatedResponse(array $result, int $statusCode = 200): ResponseInterface
    {
        return $this->respond([
            'success' => true,
            'data' => $result['data'],
            'pagination' => [
                'page' => $result['page'],
                'limit' => $result['limit'],
                'total' => $result['total'],
                'totalPages' => $result['totalPages'],
            ],
        ], $statusCode);
    }

    /**
     * Error response
     */
    protected function errorResponse(string $code, string $message, int $statusCode = 400, ?array $details = null): ResponseInterface
    {
        $error = [
            'code' => $code,
            'message' => $message,
        ];

        if ($details) {
            $error['details'] = $details;
        }

        return $this->respond([
            'success' => false,
            'error' => $error,
            'timestamp' => date('c'),
        ], $statusCode);
    }

    /**
     * Validation error response
     */
    protected function validationErrorResponse(array $errors): ResponseInterface
    {
        return $this->errorResponse(
            'VALIDATION_ERROR',
            '資料驗證失敗',
            422,
            $errors
        );
    }

    /**
     * Not found response
     */
    protected function notFoundResponse(string $message = '找不到指定的資源'): ResponseInterface
    {
        return $this->errorResponse('RESOURCE_NOT_FOUND', $message, 404);
    }

    /**
     * Forbidden response
     */
    protected function forbiddenResponse(string $message = '您沒有權限執行此操作'): ResponseInterface
    {
        return $this->errorResponse('AUTH_INSUFFICIENT_PERMISSION', $message, 403);
    }

    /**
     * Conflict response
     */
    protected function conflictResponse(string $code, string $message, ?array $details = null): ResponseInterface
    {
        return $this->errorResponse($code, $message, 409, $details);
    }

    /**
     * Get pagination parameters from request
     * If no limit specified, returns null to indicate fetching all records
     */
    protected function getPaginationParams(): array
    {
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $limitParam = $this->request->getGet('limit');
        
        // If limit is not specified, use a very large number to get all records
        // If limit is specified, cap it at 100
        if ($limitParam === null || $limitParam === '') {
            $limit = 10000; // Effectively no limit
        } else {
            $limit = min(100, max(1, (int) $limitParam));
        }

        return ['page' => $page, 'limit' => $limit];
    }

    /**
     * Generate UUID with prefix
     */
    protected function generateUuid(string $prefix = ''): string
    {
        $uuid = bin2hex(random_bytes(12));
        return $prefix ? "{$prefix}_{$uuid}" : $uuid;
    }
}
