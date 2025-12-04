<?php

namespace App\Controllers\Api\V1;

use App\Libraries\JWTService;
use App\Models\UserModel;
use App\Models\RefreshTokenModel;
use App\Models\OrganizationModel;
use App\Models\DepartmentModel;
use CodeIgniter\HTTP\ResponseInterface;

class AuthController extends BaseApiController
{
    protected UserModel $userModel;
    protected RefreshTokenModel $refreshTokenModel;
    protected JWTService $jwtService;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->refreshTokenModel = new RefreshTokenModel();
        $this->jwtService = new JWTService();
    }

    /**
     * POST /api/v1/auth/login
     * Login and get JWT tokens
     */
    public function login(): ResponseInterface
    {
        $rules = [
            'username' => 'required|min_length[3]',
            'password' => 'required|min_length[8]',
        ];

        if (!$this->validate($rules)) {
            return $this->validationErrorResponse($this->validator->getErrors());
        }

        $username = $this->request->getJsonVar('username');
        $password = $this->request->getJsonVar('password');

        // Find user by username or email
        $user = $this->userModel->findByCredentials($username);

        if (!$user || !$user->verifyPassword($password)) {
            return $this->errorResponse(
                'AUTH_INVALID_CREDENTIALS',
                '帳號或密碼錯誤',
                401
            );
        }

        // Generate tokens
        $accessToken = $this->jwtService->generateAccessToken([
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role,
            'organization_id' => $user->organization_id,
            'department_id' => $user->department_id,
        ]);

        $refreshTokenData = $this->refreshTokenModel->createToken($user->id);

        // Get organization and department info
        $orgModel = new OrganizationModel();
        $deptModel = new DepartmentModel();
        
        $organization = $orgModel->find($user->organization_id);
        $department = $user->department_id ? $deptModel->find($user->department_id) : null;

        // Set httpOnly cookies
        $response = $this->response;
        
        // Determine if connection is secure (via proxy or direct)
        $isSecure = $this->request->isSecure();
        $cookieDomain = 'crm.l';
        
        // Access token cookie (1 hour)
        $response->setCookie([
            'name' => 'access_token',
            'value' => $accessToken,
            'expire' => $this->jwtService->getAccessTokenTTL(),
            'path' => '/',
            'domain' => $cookieDomain,
            'httponly' => true,
            'secure' => $isSecure,
            'samesite' => 'Lax',
        ]);

        // Refresh token cookie (30 days)
        $response->setCookie([
            'name' => 'refresh_token',
            'value' => $refreshTokenData['token'],
            'expire' => $this->jwtService->getRefreshTokenTTL(),
            'path' => '/api/v1/auth',
            'domain' => $cookieDomain,
            'httponly' => true,
            'secure' => $isSecure,
            'samesite' => 'Lax',
        ]);

        return $this->successResponse([
            'accessToken' => $accessToken,
            'refreshToken' => $refreshTokenData['token'],
            'expiresIn' => $this->jwtService->getAccessTokenTTL(),
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'organizationId' => $user->organization_id,
                'organization' => $organization ? [
                    'id' => $organization->id,
                    'name' => $organization->name,
                    'type' => $organization->type,
                ] : null,
                'departmentId' => $user->department_id,
                'department' => $department ? [
                    'id' => $department->id,
                    'name' => $department->name,
                ] : null,
            ],
        ]);
    }

    /**
     * POST /api/v1/auth/logout
     * Logout and invalidate tokens
     */
    public function logout(): ResponseInterface
    {
        // Get refresh token from cookie
        $refreshToken = $this->request->getCookie('refresh_token');
        
        if ($refreshToken) {
            $this->refreshTokenModel->revokeToken($refreshToken);
        }

        // Clear cookies
        $response = $this->response;
        
        $response->setCookie([
            'name' => 'access_token',
            'value' => '',
            'expire' => -1,
            'path' => '/',
            'httponly' => true,
        ]);

        $response->setCookie([
            'name' => 'refresh_token',
            'value' => '',
            'expire' => -1,
            'path' => '/api/v1/auth',
            'httponly' => true,
        ]);

        return $this->successResponse(null, 200, '登出成功');
    }

    /**
     * GET /api/v1/auth/me
     * Get current user info
     */
    public function me(): ResponseInterface
    {
        $userId = $this->getCurrentUserId();
        $user = $this->userModel->find($userId);

        if (!$user) {
            return $this->errorResponse('AUTH_TOKEN_INVALID', 'Token 無效', 401);
        }

        $orgModel = new OrganizationModel();
        $deptModel = new DepartmentModel();
        
        $organization = $orgModel->find($user->organization_id);
        $department = $user->department_id ? $deptModel->find($user->department_id) : null;

        return $this->successResponse([
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'organizationId' => $user->organization_id,
            'organization' => $organization ? $organization->toApiResponse() : null,
            'departmentId' => $user->department_id,
            'department' => $department ? $department->toApiResponse() : null,
            'createdAt' => $user->created_at?->format('c'),
            'updatedAt' => $user->updated_at?->format('c'),
        ]);
    }

    /**
     * POST /api/v1/auth/refresh
     * Refresh access token
     */
    public function refresh(): ResponseInterface
    {
        // Get refresh token from cookie or request body
        $refreshToken = $this->request->getCookie('refresh_token') 
                     ?? $this->request->getJsonVar('refreshToken');

        if (!$refreshToken) {
            return $this->errorResponse(
                'AUTH_TOKEN_INVALID',
                'Refresh Token 未提供',
                401
            );
        }

        $tokenRecord = $this->refreshTokenModel->validateToken($refreshToken);

        if (!$tokenRecord) {
            return $this->errorResponse(
                'AUTH_TOKEN_INVALID',
                'Refresh Token 無效或已過期，請重新登入',
                401
            );
        }

        $user = $this->userModel->find($tokenRecord->user_id);

        if (!$user || !$user->is_active) {
            return $this->errorResponse(
                'AUTH_TOKEN_INVALID',
                '使用者帳號已停用',
                401
            );
        }

        // Generate new access token
        $accessToken = $this->jwtService->generateAccessToken([
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role,
            'organization_id' => $user->organization_id,
            'department_id' => $user->department_id,
        ]);

        // Set new access token cookie
        $isSecure = $this->request->isSecure();
        $cookieDomain = 'crm.l';
        $this->response->setCookie([
            'name' => 'access_token',
            'value' => $accessToken,
            'expire' => $this->jwtService->getAccessTokenTTL(),
            'path' => '/',
            'domain' => $cookieDomain,
            'httponly' => true,
            'secure' => $isSecure,
            'samesite' => 'Lax',
        ]);

        return $this->successResponse([
            'accessToken' => $accessToken,
            'expiresIn' => $this->jwtService->getAccessTokenTTL(),
        ]);
    }

    /**
     * POST /api/v1/auth/verify
     * Verify if token is valid
     */
    public function verify(): ResponseInterface
    {
        $token = $this->request->getJsonVar('token');

        if (!$token) {
            return $this->successResponse(['valid' => false]);
        }

        $decoded = $this->jwtService->validateAccessToken($token);

        if (!$decoded) {
            return $this->successResponse(['valid' => false]);
        }

        return $this->successResponse([
            'valid' => true,
            'expiresAt' => date('c', $decoded->exp),
            'userId' => $decoded->data->userId,
        ]);
    }
}
