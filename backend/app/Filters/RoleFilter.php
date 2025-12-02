<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class RoleFilter implements FilterInterface
{
    /**
     * Check if user has required role
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $response = Services::response();

        // Check if user data exists (should be set by JWTAuthFilter)
        if (!isset($request->user)) {
            return $response->setStatusCode(401)->setJSON([
                'success' => false,
                'error' => [
                    'code' => 'AUTH_TOKEN_INVALID',
                    'message' => '需要認證',
                ],
                'timestamp' => date('c'),
            ]);
        }

        // If no specific roles required, allow access
        if (empty($arguments)) {
            return $request;
        }

        $userRole = $request->user['role'] ?? '';
        $allowedRoles = $arguments;

        // Check if user's role is in allowed roles
        if (!in_array($userRole, $allowedRoles)) {
            return $response->setStatusCode(403)->setJSON([
                'success' => false,
                'error' => [
                    'code' => 'AUTH_INSUFFICIENT_PERMISSION',
                    'message' => '您沒有權限執行此操作',
                ],
                'timestamp' => date('c'),
            ]);
        }

        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
