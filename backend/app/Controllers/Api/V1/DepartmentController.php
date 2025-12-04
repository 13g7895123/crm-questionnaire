<?php

namespace App\Controllers\Api\V1;

use App\Models\DepartmentModel;
use App\Models\OrganizationModel;
use CodeIgniter\HTTP\ResponseInterface;

class DepartmentController extends BaseApiController
{
    protected DepartmentModel $deptModel;
    protected OrganizationModel $orgModel;

    public function __construct()
    {
        $this->deptModel = new DepartmentModel();
        $this->orgModel = new OrganizationModel();
    }

    /**
     * GET /api/v1/departments
     * Get departments list
     */
    public function index(): ResponseInterface
    {
        $pagination = $this->getPaginationParams();
        
        $filters = [
            'organization_id' => $this->request->getGet('organizationId'),
            'search' => $this->request->getGet('search'),
        ];

        // If no organization specified, use current user's organization
        if (empty($filters['organization_id'])) {
            $filters['organization_id'] = $this->getCurrentOrganizationId();
        }

        // Non-admin users can only see their own organization's departments
        if (!$this->isAdmin() && $filters['organization_id'] !== $this->getCurrentOrganizationId()) {
            $filters['organization_id'] = $this->getCurrentOrganizationId();
        }

        $result = $this->deptModel->getDepartmentsWithRelations(
            $filters,
            $pagination['page'],
            $pagination['limit']
        );

        // Format response
        $data = array_map(function ($dept) {
            return [
                'id' => $dept->id,
                'name' => $dept->name,
                'organizationId' => $dept->organization_id,
                'organization' => [
                    'id' => $dept->organization_id,
                    'name' => $dept->organization_name,
                    'type' => $dept->organization_type,
                ],
                'createdAt' => $dept->created_at,
                'updatedAt' => $dept->updated_at,
            ];
        }, $result['data']);

        $result['data'] = $data;
        return $this->paginatedResponse($result);
    }

    /**
     * GET /api/v1/departments/{departmentId}
     * Get department details
     */
    public function show($departmentId = null): ResponseInterface
    {
        $department = $this->deptModel->find($departmentId);

        if (!$department) {
            return $this->notFoundResponse('找不到指定的部門');
        }

        $organization = $this->orgModel->find($department->organization_id);

        // Get member count
        $memberCount = model('UserModel')
            ->where('department_id', $departmentId)
            ->countAllResults();

        return $this->successResponse([
            'id' => $department->id,
            'name' => $department->name,
            'organizationId' => $department->organization_id,
            'organization' => $organization ? [
                'id' => $organization->id,
                'name' => $organization->name,
                'type' => $organization->type,
            ] : null,
            'memberCount' => $memberCount,
            'createdAt' => $department->created_at?->format("c"),
            'updatedAt' => $department->updated_at?->format("c"),
        ]);
    }

    /**
     * POST /api/v1/departments
     * Create department (ADMIN only)
     */
    public function create(): ResponseInterface
    {
        if (!$this->isAdmin()) {
            return $this->forbiddenResponse();
        }

        $rules = [
            'name' => 'required|max_length[100]',
            'organizationId' => 'required',
        ];

        if (!$this->validate($rules)) {
            return $this->validationErrorResponse($this->validator->getErrors());
        }

        $name = $this->request->getJsonVar('name');
        $organizationId = $this->request->getJsonVar('organizationId');

        // Check organization exists
        $organization = $this->orgModel->find($organizationId);
        if (!$organization) {
            return $this->notFoundResponse('找不到指定的組織');
        }

        // Check name uniqueness in organization
        if ($this->deptModel->existsInOrganization($name, $organizationId)) {
            return $this->conflictResponse('RESOURCE_CONFLICT', '此組織已存在相同名稱的部門');
        }

        $this->deptModel->insert([
            'name' => $name,
            'organization_id' => $organizationId,
        ]);

        $deptId = $this->deptModel->getInsertID();
        $department = $this->deptModel->find($deptId);

        return $this->successResponse([
            'id' => $department->id,
            'name' => $department->name,
            'organizationId' => $department->organization_id,
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'type' => $organization->type,
            ],
            'createdAt' => $department->created_at?->format("c"),
            'updatedAt' => $department->updated_at?->format("c"),
        ], 201);
    }

    /**
     * PUT /api/v1/departments/{departmentId}
     * Update department (ADMIN only)
     */
    public function update($departmentId = null): ResponseInterface
    {
        if (!$this->isAdmin()) {
            return $this->forbiddenResponse();
        }

        $department = $this->deptModel->find($departmentId);
        if (!$department) {
            return $this->notFoundResponse('找不到指定的部門');
        }

        $rules = [
            'name' => 'required|max_length[100]',
        ];

        if (!$this->validate($rules)) {
            return $this->validationErrorResponse($this->validator->getErrors());
        }

        $name = $this->request->getJsonVar('name');

        // Check name uniqueness in organization
        if ($name !== $department->name && 
            $this->deptModel->existsInOrganization($name, $department->organization_id, $departmentId)) {
            return $this->conflictResponse('RESOURCE_CONFLICT', '此組織已存在相同名稱的部門');
        }

        $this->deptModel->update($departmentId, ['name' => $name]);

        $updatedDept = $this->deptModel->find($departmentId);
        $organization = $this->orgModel->find($updatedDept->organization_id);

        return $this->successResponse([
            'id' => $updatedDept->id,
            'name' => $updatedDept->name,
            'organizationId' => $updatedDept->organization_id,
            'organization' => $organization ? [
                'id' => $organization->id,
                'name' => $organization->name,
                'type' => $organization->type,
            ] : null,
            'createdAt' => $updatedDept->created_at?->format("c"),
            'updatedAt' => $updatedDept->updated_at?->format("c"),
        ]);
    }

    /**
     * DELETE /api/v1/departments/{departmentId}
     * Delete department (ADMIN only)
     */
    public function delete($departmentId = null): ResponseInterface
    {
        if (!$this->isAdmin()) {
            return $this->forbiddenResponse();
        }

        $department = $this->deptModel->find($departmentId);
        if (!$department) {
            return $this->notFoundResponse('找不到指定的部門');
        }

        // Check for related data
        $relatedData = $this->deptModel->hasRelatedData($departmentId);
        if ($relatedData['hasData']) {
            return $this->conflictResponse(
                'DEPARTMENT_IN_USE',
                '此部門有使用者或被專案審核流程使用，無法刪除',
                $relatedData
            );
        }

        $this->deptModel->delete($departmentId);

        return $this->respond(null, 204);
    }
}
