<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\JWTService;
use Config\Services;

class JWTAuthFilter implements FilterInterface
{
    /**
     * Check JWT token from httpOnly cookie or Authorization header
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $response = Services::response();
        $jwtService = new JWTService();

        // Try to get token from httpOnly cookie first (more secure)
        $token = $request->getCookie('access_token');

        // Fallback to Authorization header
        if (!$token) {
            $authHeader = $request->getHeaderLine('Authorization');
            if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                $token = $matches[1];
            }
        }

        if (!$token) {
            return $response->setStatusCode(401)->setJSON([
                'success' => false,
                'error' => [
                    'code' => 'AUTH_TOKEN_INVALID',
                    'message' => 'Token 未提供',
                ],
                'timestamp' => date('c'),
            ]);
        }

        $decoded = $jwtService->validateAccessToken($token);

        if (!$decoded) {
            return $response->setStatusCode(401)->setJSON([
                'success' => false,
                'error' => [
                    'code' => 'AUTH_TOKEN_INVALID',
                    'message' => 'Token 無效或已過期',
                ],
                'timestamp' => date('c'),
            ]);
        }

        // Store user data in request for controllers to access
        $request->user = $jwtService->getUserFromToken($decoded);

        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
