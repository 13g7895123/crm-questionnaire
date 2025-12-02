<?php

namespace App\Controllers\Api\V1;

use App\Models\UserModel;
use App\Models\OrganizationModel;
use App\Models\DepartmentModel;
use App\Models\RefreshTokenModel;
use CodeIgniter\HTTP\ResponseInterface;

class UserController extends BaseApiController
{
    protected UserModel $userModel;
    protected OrganizationModel $orgModel;
    protected DepartmentModel $deptModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->orgModel = new OrganizationModel();
        $this->deptModel = new DepartmentModel();
    }

    /**
     * PUT /api/v1/users/me
     * Update current user profile
     */
    public function updateMe(): ResponseInterface
    {
        $userId = $this->getCurrentUserId();
        $user = $this->userModel->find($userId);

        if (!$user) {
            return $this->notFoundResponse('找不到使用者');
        }

        $rules = [
            'email' => 'permit_empty|valid_email',
            'phone' => 'permit_empty|max_length[20]',
            'departmentId' => 'permit_empty',
        ];

        if (!$this->validate($rules)) {
            return $this->validationErrorResponse($this->validator->getErrors());
        }

        $data = [];

        // Email update with uniqueness check
        $email = $this->request->getJsonVar('email');
        if ($email && $email !== $user->email) {
            $existing = $this->userModel->where('email', $email)
                                       ->where('id !=', $userId)
                                       ->first();
            if ($existing) {
                return $this->conflictResponse('RESOURCE_CONFLICT', '此 Email 已被使用');
            }
            $data['email'] = $email;
        }

        // Phone update
        $phone = $this->request->getJsonVar('phone');
        if ($phone !== null) {
            $data['phone'] = $phone;
        }

        // Department update (must be in same organization)
        $departmentId = $this->request->getJsonVar('departmentId');
        if ($departmentId && $departmentId !== $user->department_id) {
            $department = $this->deptModel->find($departmentId);
            if (!$department) {
                return $this->notFoundResponse('找不到指定的部門');
            }
            if ($department->organization_id !== $user->organization_id) {
                return $this->errorResponse(
                    'VALIDATION_ERROR',
                    '部門必須屬於您的組織',
                    422
                );
            }
            $data['department_id'] = $departmentId;
        }

        if (!empty($data)) {
            $this->userModel->update($userId, $data);
        }

        $updatedUser = $this->userModel->find($userId);
        $department = $updatedUser->department_id 
            ? $this->deptModel->find($updatedUser->department_id) 
            : null;

        return $this->successResponse([
            'id' => $updatedUser->id,
            'username' => $updatedUser->username,
            'email' => $updatedUser->email,
            'phone' => $updatedUser->phone,
            'role' => $updatedUser->role,
            'organizationId' => $updatedUser->organization_id,
            'departmentId' => $updatedUser->department_id,
            'department' => $department ? [
                'id' => $department->id,
                'name' => $department->name,
            ] : null,
            'updatedAt' => $updatedUser->updated_at?->format("c"),
        ]);
    }

    /**
     * PUT /api/v1/users/me/password
     * Change current user password
     */
    public function updatePassword(): ResponseInterface
    {
        $userId = $this->getCurrentUserId();
        $user = $this->userModel->find($userId);

        if (!$user) {
            return $this->notFoundResponse('找不到使用者');
        }

        $rules = [
            'currentPassword' => 'required',
            'newPassword' => 'required|min_length[8]',
            'confirmPassword' => 'required|matches[newPassword]',
        ];

        if (!$this->validate($rules)) {
            return $this->validationErrorResponse($this->validator->getErrors());
        }

        $currentPassword = $this->request->getJsonVar('currentPassword');
        $newPassword = $this->request->getJsonVar('newPassword');

        // Verify current password
        if (!$user->verifyPassword($currentPassword)) {
            return $this->errorResponse(
                'AUTH_INVALID_CREDENTIALS',
                '目前密碼錯誤',
                401
            );
        }

        // Update password
        $user->setPassword($newPassword);
        $this->userModel->save($user);

        // Revoke all refresh tokens
        $refreshTokenModel = new RefreshTokenModel();
        $refreshTokenModel->revokeAllUserTokens($userId);

        return $this->successResponse(null, 200, '密碼更新成功，請使用新密碼重新登入');
    }

    /**
     * GET /api/v1/users
     * Get users list (ADMIN or HOST only)
     */
    public function index(): ResponseInterface
    {
        if (!$this->isAdmin() && !$this->isHost()) {
            return $this->forbiddenResponse();
        }

        $pagination = $this->getPaginationParams();
        
        $filters = [
            'role' => $this->request->getGet('role'),
            'organization_id' => $this->request->getGet('organizationId'),
            'department_id' => $this->request->getGet('departmentId'),
            'search' => $this->request->getGet('search'),
        ];

        // HOST can only see users in same organization
        if ($this->isHost()) {
            $filters['organization_id'] = $this->getCurrentOrganizationId();
        }

        $result = $this->userModel->getUsersWithRelations(
            $filters,
            $pagination['page'],
            $pagination['limit']
        );

        // Format response
        $data = array_map(function ($user) {
            return [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'organizationId' => $user->organization_id,
                'organization' => [
                    'id' => $user->organization_id,
                    'name' => $user->organization_name,
                ],
                'departmentId' => $user->department_id,
                'department' => $user->department_id ? [
                    'id' => $user->department_id,
                    'name' => $user->department_name,
                ] : null,
                'createdAt' => $user->created_at,
                'updatedAt' => $user->updated_at,
            ];
        }, $result['data']);

        $result['data'] = $data;
        return $this->paginatedResponse($result);
    }

    /**
     * POST /api/v1/users
     * Create user (ADMIN only)
     */
    public function create(): ResponseInterface
    {
        if (!$this->isAdmin()) {
            return $this->forbiddenResponse();
        }

        $rules = [
            'username' => 'required|min_length[3]|max_length[100]',
            'email' => 'required|valid_email',
            'password' => 'required|min_length[8]',
            'role' => 'required|in_list[HOST,SUPPLIER,ADMIN]',
            'organizationId' => 'required',
            'departmentId' => 'required',
        ];

        if (!$this->validate($rules)) {
            return $this->validationErrorResponse($this->validator->getErrors());
        }

        // Check username/email uniqueness
        $username = $this->request->getJsonVar('username');
        $email = $this->request->getJsonVar('email');

        $existing = $this->userModel->where('username', $username)->first();
        if ($existing) {
            return $this->conflictResponse('RESOURCE_CONFLICT', '使用者名稱已被使用');
        }

        $existing = $this->userModel->where('email', $email)->first();
        if ($existing) {
            return $this->conflictResponse('RESOURCE_CONFLICT', 'Email 已被使用');
        }

        // Verify organization and department exist
        $organizationId = $this->request->getJsonVar('organizationId');
        $departmentId = $this->request->getJsonVar('departmentId');

        if (!$this->orgModel->find($organizationId)) {
            return $this->notFoundResponse('找不到指定的組織');
        }

        $department = $this->deptModel->find($departmentId);
        if (!$department || $department->organization_id !== $organizationId) {
            return $this->notFoundResponse('找不到指定的部門或部門不屬於該組織');
        }

        // Create user
        $userId = $this->generateUuid('usr');
        $userData = [
            'id' => $userId,
            'username' => $username,
            'email' => $email,
            'phone' => $this->request->getJsonVar('phone'),
            'role' => $this->request->getJsonVar('role'),
            'organization_id' => $organizationId,
            'department_id' => $departmentId,
            'is_active' => true,
        ];

        // Set password using entity
        $user = new \App\Entities\User($userData);
        $user->setPassword($this->request->getJsonVar('password'));

        $this->userModel->insert($user);

        $createdUser = $this->userModel->find($userId);

        return $this->successResponse([
            'id' => $createdUser->id,
            'username' => $createdUser->username,
            'email' => $createdUser->email,
            'phone' => $createdUser->phone,
            'role' => $createdUser->role,
            'organizationId' => $createdUser->organization_id,
            'departmentId' => $createdUser->department_id,
            'createdAt' => $createdUser->created_at?->format("c"),
            'updatedAt' => $createdUser->updated_at?->format("c"),
        ], 201);
    }

    /**
     * PUT /api/v1/users/{userId}
     * Update user (ADMIN only)
     */
    public function update($userId = null): ResponseInterface
    {
        if (!$this->isAdmin()) {
            return $this->forbiddenResponse();
        }

        $user = $this->userModel->find($userId);
        if (!$user) {
            return $this->notFoundResponse('找不到指定的使用者');
        }

        $data = [];

        // Email update
        $email = $this->request->getJsonVar('email');
        if ($email && $email !== $user->email) {
            $existing = $this->userModel->where('email', $email)
                                       ->where('id !=', $userId)
                                       ->first();
            if ($existing) {
                return $this->conflictResponse('RESOURCE_CONFLICT', '此 Email 已被使用');
            }
            $data['email'] = $email;
        }

        // Other fields
        if ($this->request->getJsonVar('phone') !== null) {
            $data['phone'] = $this->request->getJsonVar('phone');
        }

        if ($this->request->getJsonVar('role')) {
            $data['role'] = $this->request->getJsonVar('role');
        }

        if ($this->request->getJsonVar('departmentId')) {
            $data['department_id'] = $this->request->getJsonVar('departmentId');
        }

        if ($this->request->getJsonVar('organizationId')) {
            $data['organization_id'] = $this->request->getJsonVar('organizationId');
        }

        if (!empty($data)) {
            $this->userModel->update($userId, $data);
        }

        $updatedUser = $this->userModel->find($userId);

        return $this->successResponse([
            'id' => $updatedUser->id,
            'username' => $updatedUser->username,
            'email' => $updatedUser->email,
            'phone' => $updatedUser->phone,
            'role' => $updatedUser->role,
            'organizationId' => $updatedUser->organization_id,
            'departmentId' => $updatedUser->department_id,
            'updatedAt' => $updatedUser->updated_at?->format("c"),
        ]);
    }

    /**
     * DELETE /api/v1/users/{userId}
     * Delete user (ADMIN only)
     */
    public function delete($userId = null): ResponseInterface
    {
        if (!$this->isAdmin()) {
            return $this->forbiddenResponse();
        }

        $user = $this->userModel->find($userId);
        if (!$user) {
            return $this->notFoundResponse('找不到指定的使用者');
        }

        // Check for related data (projects, reviews)
        $projectModel = model('ProjectModel');
        $reviewLogModel = model('ReviewLogModel');

        $projectCount = $projectModel->where('supplier_id', $user->organization_id)->countAllResults();
        $reviewCount = $reviewLogModel->where('reviewer_id', $userId)->countAllResults();

        if ($projectCount > 0 || $reviewCount > 0) {
            return $this->conflictResponse(
                'RESOURCE_CONFLICT',
                '此使用者有關聯的專案或審核紀錄，無法刪除'
            );
        }

        $this->userModel->delete($userId);

        return $this->respond(null, 204);
    }
}
