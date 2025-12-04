<?php

namespace App\Controllers\Api\V1;

use App\Models\ProjectModel;
use App\Models\TemplateModel;
use App\Models\TemplateVersionModel;
use App\Models\OrganizationModel;
use App\Models\ReviewStageConfigModel;
use CodeIgniter\HTTP\ResponseInterface;

class ProjectController extends BaseApiController
{
    protected ProjectModel $projectModel;
    protected TemplateModel $templateModel;
    protected OrganizationModel $orgModel;
    protected ReviewStageConfigModel $reviewConfigModel;

    public function __construct()
    {
        $this->projectModel = new ProjectModel();
        $this->templateModel = new TemplateModel();
        $this->orgModel = new OrganizationModel();
        $this->reviewConfigModel = new ReviewStageConfigModel();
    }

    /**
     * GET /api/v1/projects
     * Get projects list
     */
    public function index(): ResponseInterface
    {
        $pagination = $this->getPaginationParams();
        
        $filters = [
            'type' => $this->request->getGet('type'),
            'status' => $this->request->getGet('status'),
            'year' => $this->request->getGet('year'),
            'search' => $this->request->getGet('search'),
            'sortBy' => $this->request->getGet('sortBy'),
            'order' => $this->request->getGet('order'),
        ];

        // Different queries based on user role
        if ($this->isSupplier()) {
            $result = $this->projectModel->getProjectsForSupplier(
                $this->getCurrentOrganizationId(),
                $filters,
                $pagination['page'],
                $pagination['limit']
            );
        } else {
            $result = $this->projectModel->getProjectsForHost(
                $filters,
                $pagination['page'],
                $pagination['limit']
            );
        }

        // Format response
        $data = array_map(function ($project) {
            $response = [
                'id' => $project->id,
                'name' => $project->name,
                'year' => (int) $project->year,
                'type' => $project->type,
                'templateId' => $project->template_id,
                'templateVersion' => $project->template_version,
                'status' => $project->status,
                'currentStage' => (int) $project->current_stage,
                'createdAt' => $project->created_at,
                'updatedAt' => $project->updated_at,
            ];

            // HOST can see supplier info and review config
            if ($this->isHost() || $this->isAdmin()) {
                $response['supplierId'] = $project->supplier_id;
                $response['supplier'] = [
                    'id' => $project->supplier_id,
                    'name' => $project->supplier_name ?? null,
                    'type' => 'SUPPLIER',
                ];

                // Get review config
                $reviewConfig = $this->reviewConfigModel->getConfigForProject($project->id);
                $response['reviewConfig'] = array_map(function ($config) {
                    return [
                        'stageOrder' => (int) $config->stage_order,
                        'departmentId' => $config->department_id,
                        'department' => [
                            'id' => $config->department_id,
                            'name' => $config->department_name,
                        ],
                    ];
                }, $reviewConfig);
            }

            return $response;
        }, $result['data']);

        $result['data'] = $data;
        return $this->paginatedResponse($result);
    }

    /**
     * GET /api/v1/projects/{projectId}
     * Get project details
     */
    public function show($projectId = null): ResponseInterface
    {
        $project = $this->projectModel->find($projectId);

        if (!$project) {
            return $this->notFoundResponse('找不到指定的專案');
        }

        // Supplier can only see assigned projects
        if ($this->isSupplier() && $project->supplier_id !== $this->getCurrentOrganizationId()) {
            return $this->errorResponse(
                'SUPPLIER_NOT_ASSIGNED',
                '您無權存取此專案',
                403
            );
        }

        $template = $this->templateModel->find($project->template_id);
        $supplier = $this->orgModel->find($project->supplier_id);
        $reviewConfig = $this->reviewConfigModel->getConfigForProject($projectId);

        $response = [
            'id' => $project->id,
            'name' => $project->name,
            'year' => $project->year,
            'type' => $project->type,
            'templateId' => $project->template_id,
            'templateVersion' => $project->template_version,
            'template' => $template ? [
                'id' => $template->id,
                'name' => $template->name,
                'type' => $template->type,
                'latestVersion' => $template->latest_version,
            ] : null,
            'status' => $project->status,
            'currentStage' => $project->current_stage,
            'submittedAt' => $project->submitted_at?->format("c"),
            'createdAt' => $project->created_at?->format("c"),
            'updatedAt' => $project->updated_at?->format("c"),
        ];

        // HOST/ADMIN can see supplier and review config
        if ($this->isHost() || $this->isAdmin()) {
            $response['supplierId'] = $project->supplier_id;
            $response['supplier'] = $supplier ? [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'type' => $supplier->type,
            ] : null;

            $response['reviewConfig'] = array_map(function ($config) {
                return [
                    'stageOrder' => (int) $config->stage_order,
                    'departmentId' => $config->department_id,
                    'department' => [
                        'id' => $config->department_id,
                        'name' => $config->department_name,
                    ],
                ];
            }, $reviewConfig);
        }

        return $this->successResponse($response);
    }

    /**
     * POST /api/v1/projects
     * Create project (HOST only)
     */
    public function create(): ResponseInterface
    {
        if (!$this->isHost() && !$this->isAdmin()) {
            return $this->errorResponse(
                'AUTH_INSUFFICIENT_PERMISSION',
                '供應商無權建立專案',
                403
            );
        }

        $rules = [
            'name' => 'required|max_length[200]',
            'year' => 'required|integer|greater_than[1899]|less_than[2101]',
            'type' => 'required|in_list[SAQ,CONFLICT]',
            'templateId' => 'required',
            'templateVersion' => 'required',
            'supplierId' => 'required',
            'reviewConfig' => 'required',
        ];

        if (!$this->validate($rules)) {
            return $this->validationErrorResponse($this->validator->getErrors());
        }

        // Validate template and version
        $templateId = $this->request->getJsonVar('templateId');
        $templateVersion = $this->request->getJsonVar('templateVersion');

        $template = $this->templateModel->find($templateId);
        if (!$template) {
            return $this->notFoundResponse('找不到指定的範本');
        }

        $versionModel = new TemplateVersionModel();
        $version = $versionModel->getVersion($templateId, $templateVersion);
        if (!$version) {
            return $this->notFoundResponse('找不到指定的範本版本');
        }

        // Validate supplier
        $supplierId = $this->request->getJsonVar('supplierId');
        $supplier = $this->orgModel->find($supplierId);
        if (!$supplier || $supplier->type !== 'SUPPLIER') {
            return $this->notFoundResponse('找不到指定的供應商');
        }

        // Validate review config
        $reviewConfig = $this->request->getJsonVar('reviewConfig');
        $reviewConfig = json_decode(json_encode($reviewConfig), true);

        if (!is_array($reviewConfig) || count($reviewConfig) < 1 || count($reviewConfig) > 5) {
            return $this->errorResponse(
                'REVIEW_STAGE_INVALID',
                '審核流程設定錯誤',
                422,
                ['reviewConfig' => '審核階段必須為 1-5 個，且順序必須連續']
            );
        }

        // Validate stage order is sequential
        $expectedOrder = 1;
        foreach ($reviewConfig as $config) {
            if (!isset($config['stageOrder']) || $config['stageOrder'] !== $expectedOrder) {
                return $this->errorResponse(
                    'REVIEW_STAGE_INVALID',
                    '審核流程設定錯誤',
                    422,
                    ['reviewConfig' => '審核階段順序必須從 1 開始且連續']
                );
            }
            $expectedOrder++;
        }

        // Create project
        $this->projectModel->insert([
            'name' => $this->request->getJsonVar('name'),
            'year' => $this->request->getJsonVar('year'),
            'type' => $this->request->getJsonVar('type'),
            'template_id' => $templateId,
            'template_version' => $templateVersion,
            'supplier_id' => $supplierId,
            'status' => 'IN_PROGRESS',
            'current_stage' => 0,
        ]);

        $projectId = $this->projectModel->getInsertID();

        // Create review config
        $this->reviewConfigModel->createConfigForProject($projectId, $reviewConfig);

        // Fetch created project with relations
        $project = $this->projectModel->find($projectId);
        $reviewConfigData = $this->reviewConfigModel->getConfigForProject($projectId);

        return $this->successResponse([
            'id' => $project->id,
            'name' => $project->name,
            'year' => $project->year,
            'type' => $project->type,
            'templateId' => $project->template_id,
            'templateVersion' => $project->template_version,
            'supplierId' => $project->supplier_id,
            'status' => $project->status,
            'currentStage' => $project->current_stage,
            'reviewConfig' => array_map(function ($config) {
                return [
                    'stageOrder' => (int) $config->stage_order,
                    'departmentId' => $config->department_id,
                    'department' => [
                        'id' => $config->department_id,
                        'name' => $config->department_name,
                    ],
                ];
            }, $reviewConfigData),
            'createdAt' => $project->created_at?->format("c"),
            'updatedAt' => $project->updated_at?->format("c"),
        ], 201);
    }

    /**
     * PUT /api/v1/projects/{projectId}
     * Update project (HOST only)
     */
    public function update($projectId = null): ResponseInterface
    {
        if (!$this->isHost() && !$this->isAdmin()) {
            return $this->forbiddenResponse();
        }

        $project = $this->projectModel->find($projectId);
        if (!$project) {
            return $this->notFoundResponse('找不到指定的專案');
        }

        // Check if project can be updated
        if (in_array($project->status, ['APPROVED'])) {
            return $this->conflictResponse(
                'PROJECT_ALREADY_SUBMITTED',
                '專案已核准，無法修改'
            );
        }

        $data = [];

        if ($this->request->getJsonVar('name')) {
            $data['name'] = $this->request->getJsonVar('name');
        }

        if ($this->request->getJsonVar('year')) {
            $data['year'] = $this->request->getJsonVar('year');
        }

        // Review config can only be updated before submission
        $reviewConfig = $this->request->getJsonVar('reviewConfig');
        if ($reviewConfig) {
            $reviewConfig = json_decode(json_encode($reviewConfig), true);
        }

        if ($reviewConfig && in_array($project->status, ['DRAFT', 'IN_PROGRESS', 'RETURNED'])) {
            $this->reviewConfigModel->createConfigForProject($projectId, $reviewConfig);
        } elseif ($reviewConfig) {
            return $this->conflictResponse(
                'PROJECT_ALREADY_SUBMITTED',
                '專案已提交，無法修改審核流程'
            );
        }

        if (!empty($data)) {
            $this->projectModel->update($projectId, $data);
        }

        $updatedProject = $this->projectModel->find($projectId);
        $reviewConfigData = $this->reviewConfigModel->getConfigForProject($projectId);

        return $this->successResponse([
            'id' => $updatedProject->id,
            'name' => $updatedProject->name,
            'year' => $updatedProject->year,
            'type' => $updatedProject->type,
            'templateId' => $updatedProject->template_id,
            'templateVersion' => $updatedProject->template_version,
            'supplierId' => $updatedProject->supplier_id,
            'status' => $updatedProject->status,
            'currentStage' => $updatedProject->current_stage,
            'reviewConfig' => array_map(function ($config) {
                return [
                    'stageOrder' => (int) $config->stage_order,
                    'departmentId' => $config->department_id,
                    'department' => [
                        'id' => $config->department_id,
                        'name' => $config->department_name,
                    ],
                ];
            }, $reviewConfigData),
            'updatedAt' => $updatedProject->updated_at?->format("c"),
        ]);
    }

    /**
     * DELETE /api/v1/projects/{projectId}
     * Delete project (HOST only)
     */
    public function delete($projectId = null): ResponseInterface
    {
        if (!$this->isHost() && !$this->isAdmin()) {
            return $this->forbiddenResponse();
        }

        $project = $this->projectModel->find($projectId);
        if (!$project) {
            return $this->notFoundResponse('找不到指定的專案');
        }

        // Only DRAFT projects can be deleted
        if ($project->status !== 'DRAFT') {
            return $this->conflictResponse(
                'RESOURCE_CONFLICT',
                '僅草稿狀態的專案可刪除'
            );
        }

        // Delete related data
        $this->reviewConfigModel->where('project_id', $projectId)->delete();
        model('AnswerModel')->where('project_id', $projectId)->delete();
        model('ReviewLogModel')->where('project_id', $projectId)->delete();

        $this->projectModel->delete($projectId);

        return $this->respond(null, 204);
    }

    /**
     * GET /api/v1/projects/stats
     * Get project statistics (HOST only)
     */
    public function stats(): ResponseInterface
    {
        if (!$this->isHost() && !$this->isAdmin()) {
            return $this->forbiddenResponse();
        }

        $type = $this->request->getGet('type');
        $year = $this->request->getGet('year');

        $stats = $this->projectModel->getStats($type, $year);

        // Get by type
        $byType = [
            'SAQ' => $this->projectModel->where('type', 'SAQ')
                                       ->where('deleted_at IS NULL')
                                       ->countAllResults(),
            'CONFLICT' => $this->projectModel->where('type', 'CONFLICT')
                                            ->where('deleted_at IS NULL')
                                            ->countAllResults(),
        ];

        // Get by year
        $years = $this->projectModel->builder()
            ->select('year, COUNT(*) as count')
            ->where('deleted_at IS NULL')
            ->groupBy('year')
            ->orderBy('year', 'DESC')
            ->get()
            ->getResult();

        $byYear = [];
        foreach ($years as $y) {
            $byYear[$y->year] = (int) $y->count;
        }

        return $this->successResponse([
            'total' => $stats['total'],
            'byStatus' => $stats['byStatus'],
            'byType' => $byType,
            'byYear' => $byYear,
        ]);
    }
}
