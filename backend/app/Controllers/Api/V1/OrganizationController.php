<?php

namespace App\Controllers\Api\V1;

use App\Models\OrganizationModel;
use CodeIgniter\HTTP\ResponseInterface;

class OrganizationController extends BaseApiController
{
    protected OrganizationModel $orgModel;

    public function __construct()
    {
        $this->orgModel = new OrganizationModel();
    }

    /**
     * GET /api/v1/organizations
     * Get organizations list (ADMIN only)
     */
    public function index(): ResponseInterface
    {
        if (!$this->isAdmin()) {
            return $this->forbiddenResponse();
        }

        $pagination = $this->getPaginationParams();
        
        $filters = [
            'type' => $this->request->getGet('type'),
            'search' => $this->request->getGet('search'),
        ];

        $result = $this->orgModel->getOrganizationsWithCounts(
            $filters,
            $pagination['page'],
            $pagination['limit']
        );

        // Format response
        $data = array_map(function ($org) {
            return [
                'id' => $org->id,
                'name' => $org->name,
                'type' => $org->type,
                'departmentCount' => (int) $org->department_count,
                'userCount' => (int) $org->user_count,
                'createdAt' => $org->created_at,
                'updatedAt' => $org->updated_at,
            ];
        }, $result['data']);

        $result['data'] = $data;
        return $this->paginatedResponse($result);
    }

    /**
     * GET /api/v1/organizations/{organizationId}
     * Get organization details
     */
    public function show($organizationId = null): ResponseInterface
    {
        $organization = $this->orgModel->find($organizationId);

        if (!$organization) {
            return $this->notFoundResponse('找不到指定的組織');
        }

        // Check permission: ADMIN can see all, others can only see their own organization
        if (!$this->isAdmin() && $organizationId !== $this->getCurrentOrganizationId()) {
            return $this->forbiddenResponse('您無權檢視此組織資訊');
        }

        // Get departments
        $deptModel = model('DepartmentModel');
        $departments = $deptModel->builder()
            ->select('departments.*, (SELECT COUNT(*) FROM users WHERE users.department_id = departments.id AND users.deleted_at IS NULL) as member_count')
            ->where('departments.organization_id', $organizationId)
            ->where('departments.deleted_at IS NULL')
            ->get()
            ->getResult();

        // Get counts
        $userCount = model('UserModel')
            ->where('organization_id', $organizationId)
            ->countAllResults();

        $projectCount = model('ProjectModel')
            ->where('supplier_id', $organizationId)
            ->countAllResults();

        return $this->successResponse([
            'id' => $organization->id,
            'name' => $organization->name,
            'type' => $organization->type,
            'departments' => array_map(function ($dept) {
                return [
                    'id' => $dept->id,
                    'name' => $dept->name,
                    'memberCount' => (int) $dept->member_count,
                ];
            }, $departments),
            'userCount' => $userCount,
            'projectCount' => $projectCount,
            'createdAt' => $organization->created_at?->format("c"),
            'updatedAt' => $organization->updated_at?->format("c"),
        ]);
    }

    /**
     * POST /api/v1/organizations
     * Create organization (ADMIN only)
     */
    public function create(): ResponseInterface
    {
        if (!$this->isAdmin()) {
            return $this->forbiddenResponse();
        }

        $rules = [
            'name' => 'required|max_length[200]',
            'type' => 'required|in_list[HOST,SUPPLIER]',
        ];

        if (!$this->validate($rules)) {
            return $this->validationErrorResponse($this->validator->getErrors());
        }

        $name = $this->request->getJsonVar('name');

        // Check name uniqueness
        $existing = $this->orgModel->where('name', $name)->first();
        if ($existing) {
            return $this->conflictResponse('RESOURCE_CONFLICT', '組織名稱已存在');
        }

        $orgId = $this->generateUuid('org');
        $this->orgModel->insert([
            'id' => $orgId,
            'name' => $name,
            'type' => $this->request->getJsonVar('type'),
        ]);

        $organization = $this->orgModel->find($orgId);

        return $this->successResponse([
            'id' => $organization->id,
            'name' => $organization->name,
            'type' => $organization->type,
            'createdAt' => $organization->created_at?->format("c"),
            'updatedAt' => $organization->updated_at?->format("c"),
        ], 201);
    }

    /**
     * PUT /api/v1/organizations/{organizationId}
     * Update organization (ADMIN only)
     */
    public function update($organizationId = null): ResponseInterface
    {
        if (!$this->isAdmin()) {
            return $this->forbiddenResponse();
        }

        $organization = $this->orgModel->find($organizationId);
        if (!$organization) {
            return $this->notFoundResponse('找不到指定的組織');
        }

        $name = $this->request->getJsonVar('name');
        if ($name && $name !== $organization->name) {
            // Check name uniqueness
            $existing = $this->orgModel->where('name', $name)
                                       ->where('id !=', $organizationId)
                                       ->first();
            if ($existing) {
                return $this->conflictResponse('RESOURCE_CONFLICT', '組織名稱已存在');
            }

            $this->orgModel->update($organizationId, ['name' => $name]);
        }

        $updatedOrg = $this->orgModel->find($organizationId);

        return $this->successResponse([
            'id' => $updatedOrg->id,
            'name' => $updatedOrg->name,
            'type' => $updatedOrg->type,
            'updatedAt' => $updatedOrg->updated_at?->format("c"),
        ]);
    }

    /**
     * DELETE /api/v1/organizations/{organizationId}
     * Delete organization (ADMIN only)
     */
    public function delete($organizationId = null): ResponseInterface
    {
        if (!$this->isAdmin()) {
            return $this->forbiddenResponse();
        }

        $organization = $this->orgModel->find($organizationId);
        if (!$organization) {
            return $this->notFoundResponse('找不到指定的組織');
        }

        // Check for related data
        $relatedData = $this->orgModel->hasRelatedData($organizationId);
        if ($relatedData['hasData']) {
            return $this->conflictResponse(
                'RESOURCE_CONFLICT',
                '此組織有使用者或部門，無法刪除',
                $relatedData
            );
        }

        $this->orgModel->delete($organizationId);

        return $this->respond(null, 204);
    }

    /**
     * GET /api/v1/suppliers
     * Get suppliers list (HOST only)
     */
    public function suppliers(): ResponseInterface
    {
        if (!$this->isHost() && !$this->isAdmin()) {
            return $this->forbiddenResponse();
        }

        $pagination = $this->getPaginationParams();
        
        $filters = [
            'search' => $this->request->getGet('search'),
        ];

        $result = $this->orgModel->getSuppliers(
            $filters,
            $pagination['page'],
            $pagination['limit']
        );

        // Format response
        $data = array_map(function ($org) {
            return [
                'id' => $org->id,
                'name' => $org->name,
                'type' => $org->type,
                'createdAt' => $org->created_at,
            ];
        }, $result['data']);

        $result['data'] = $data;
        return $this->paginatedResponse($result);
    }
}
