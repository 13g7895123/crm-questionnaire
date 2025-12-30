<?php

namespace App\Controllers\Api\V1;

use App\Models\ProjectModel;
use App\Models\ProjectSupplierModel;
use App\Models\TemplateModel;
use App\Models\TemplateVersionModel;
use App\Models\OrganizationModel;
use App\Models\ReviewStageConfigModel;
use App\Models\QuestionReviewModel;
use App\Repositories\TemplateStructureRepository;
use CodeIgniter\HTTP\ResponseInterface;

class ProjectController extends BaseApiController
{
    protected ProjectModel $projectModel;
    protected ProjectSupplierModel $projectSupplierModel;
    protected TemplateModel $templateModel;
    protected OrganizationModel $orgModel;
    protected ReviewStageConfigModel $reviewConfigModel;
    protected QuestionReviewModel $questionReviewModel;
    protected TemplateStructureRepository $structureRepo;

    public function __construct()
    {
        $this->projectModel = new ProjectModel();
        $this->projectSupplierModel = new ProjectSupplierModel();
        $this->templateModel = new TemplateModel();
        $this->orgModel = new OrganizationModel();
        $this->reviewConfigModel = new ReviewStageConfigModel();
        $this->questionReviewModel = new QuestionReviewModel();
        $this->structureRepo = new TemplateStructureRepository();
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
            // Supplier sees their assigned projects via project_suppliers
            $result = $this->projectSupplierModel->getProjectsForSupplier(
                $this->getCurrentOrganizationId(),
                $filters,
                $pagination['page'],
                $pagination['limit']
            );

            // Format response for supplier
            $data = array_map(function ($ps) {
                return [
                    'id' => $ps->id, // project_supplier_id
                    'projectId' => $ps->project_id,
                    'name' => $ps->name,
                    'year' => (int) $ps->year,
                    'type' => $ps->type,
                    'templateId' => $ps->template_id,
                    'templateVersion' => $ps->template_version,
                    'status' => $ps->status,
                    'currentStage' => (int) $ps->current_stage,
                    'submittedAt' => $ps->submitted_at,
                    'createdAt' => $ps->created_at,
                    'updatedAt' => $ps->updated_at,
                ];
            }, $result['data']);
        } else {
            // HOST sees all projects with supplier counts
            $result = $this->projectModel->getProjectsForHost(
                $filters,
                $pagination['page'],
                $pagination['limit']
            );

            // Format response for HOST
            $data = array_map(function ($project) {
                $response = [
                    'id' => $project->id,
                    'name' => $project->name,
                    'year' => (int) $project->year,
                    'type' => $project->type,
                    'templateId' => $project->template_id,
                    'templateVersion' => $project->template_version,
                    'supplierCount' => (int) ($project->supplier_count ?? 0),
                    'approvedCount' => (int) ($project->approved_count ?? 0),
                    'createdAt' => $project->created_at,
                    'updatedAt' => $project->updated_at,
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

                return $response;
            }, $result['data']);
        }

        $result['data'] = $data;
        return $this->paginatedResponse($result);
    }

    /**
     * GET /api/v1/projects/{projectId}
     * Get project details
     */
    public function show($projectId = null): ResponseInterface
    {
        $startTime = microtime(true);

        $project = $this->projectModel->find($projectId);
        log_message('info', sprintf('Project show - find project: %.3fms', (microtime(true) - $startTime) * 1000));

        if (!$project) {
            return $this->notFoundResponse('找不到指定的專案');
        }

        $t1 = microtime(true);
        $template = $this->templateModel->find($project->template_id);
        log_message('info', sprintf('Project show - find template: %.3fms', (microtime(true) - $t1) * 1000));

        $t2 = microtime(true);
        $reviewConfig = $this->reviewConfigModel->getConfigForProject($projectId);
        log_message('info', sprintf('Project show - get review config: %.3fms', (microtime(true) - $t2) * 1000));

        $t3 = microtime(true);
        $suppliers = $this->projectSupplierModel->getSuppliersForProject($projectId);
        log_message('info', sprintf('Project show - get suppliers: %.3fms', (microtime(true) - $t3) * 1000));

        log_message('info', sprintf('Project show - total time: %.3fms', (microtime(true) - $startTime) * 1000));

        // Supplier can only see if they are assigned
        if ($this->isSupplier()) {
            $isAssigned = false;
            $currentOrgId = $this->getCurrentOrganizationId();
            foreach ($suppliers as $s) {
                if ($s->supplier_id == $currentOrgId) {
                    $isAssigned = true;
                    break;
                }
            }
            if (!$isAssigned) {
                return $this->errorResponse(
                    'SUPPLIER_NOT_ASSIGNED',
                    '您無權存取此專案',
                    403
                );
            }
        }

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
            'createdAt' => $project->created_at?->format("c"),
            'updatedAt' => $project->updated_at?->format("c"),
        ];

        // HOST/ADMIN can see suppliers and review config
        if ($this->isHost() || $this->isAdmin()) {
            // Get total questions count from template structure
            $totalQuestions = 0;
            if ($project->template_id) {
                $structure = $this->structureRepo->getTemplateStructure((int)$project->template_id);
                foreach ($structure as $section) {
                    foreach ($section['subsections'] ?? [] as $subsection) {
                        $totalQuestions += count($subsection['questions'] ?? []);
                    }
                }
            }

            // Get reviewed counts for all suppliers in one query
            $supplierIds = array_map(fn($s) => $s->id, $suppliers);
            // Build stages map for each supplier
            $stagesMap = [];
            foreach ($suppliers as $s) {
                $stagesMap[$s->id] = (int)$s->current_stage ?: 1;
            }
            $reviewedCounts = $this->questionReviewModel->getReviewedCountsForProjectSuppliers($supplierIds, $stagesMap);

            $response['suppliers'] = array_map(function ($s) use ($totalQuestions, $reviewedCounts) {
                $counts = $reviewedCounts[$s->id] ?? ['total' => 0, 'approved' => 0, 'rejected' => 0];
                return [
                    'id' => $s->id,
                    'supplierId' => $s->supplier_id,
                    'supplierName' => $s->supplier_name,
                    'status' => $s->status,
                    'currentStage' => (int) $s->current_stage,
                    'submittedAt' => $s->submitted_at,
                    'totalQuestions' => $totalQuestions,
                    'reviewedQuestions' => $counts['total'],
                    'approvedQuestions' => $counts['approved'],
                    'rejectedQuestions' => $counts['rejected'],
                ];
            }, $suppliers);

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
        } elseif ($this->isSupplier()) {
            // Find the specific record for this supplier
            $currentOrgId = $this->getCurrentOrganizationId();
            foreach ($suppliers as $s) {
                if ($s->supplier_id == $currentOrgId) {
                    $response['projectSupplierId'] = (int) $s->id;
                    $response['status'] = $s->status;
                    break;
                }
            }
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
            'supplierIds' => 'required',
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

        // Validate suppliers (multiple)
        $supplierIds = $this->request->getJsonVar('supplierIds');
        $supplierIds = json_decode(json_encode($supplierIds), true);

        if (!is_array($supplierIds) || count($supplierIds) < 1) {
            return $this->errorResponse(
                'VALIDATION_ERROR',
                '供應商選擇錯誤',
                422,
                ['supplierIds' => '必須選擇至少一個供應商']
            );
        }

        // Validate each supplier exists and is of type SUPPLIER
        $validSupplierIds = [];
        foreach ($supplierIds as $supplierId) {
            $supplier = $this->orgModel->find($supplierId);
            if (!$supplier || $supplier->type !== 'SUPPLIER') {
                return $this->notFoundResponse("找不到指定的供應商: {$supplierId}");
            }
            $validSupplierIds[] = $supplierId;
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
        ]);

        $projectId = $this->projectModel->getInsertID();

        // Create review config
        $this->reviewConfigModel->createConfigForProject($projectId, $reviewConfig);

        // Add suppliers to project
        $this->projectSupplierModel->addSuppliersToProject($projectId, $validSupplierIds);

        // Fetch created project with relations
        $project = $this->projectModel->find($projectId);
        $reviewConfigData = $this->reviewConfigModel->getConfigForProject($projectId);
        $suppliers = $this->projectSupplierModel->getSuppliersForProject($projectId);

        return $this->successResponse([
            'id' => $project->id,
            'name' => $project->name,
            'year' => $project->year,
            'type' => $project->type,
            'templateId' => $project->template_id,
            'templateVersion' => $project->template_version,
            'suppliers' => array_map(function ($s) {
                return [
                    'id' => $s->id,
                    'supplierId' => $s->supplier_id,
                    'supplierName' => $s->supplier_name,
                    'status' => $s->status,
                    'currentStage' => (int) $s->current_stage,
                ];
            }, $suppliers),
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

        // Check if any supplier has already been approved
        $stats = $this->projectSupplierModel->getStatsByProject($projectId);
        if ($stats['byStatus']['APPROVED'] > 0) {
            return $this->conflictResponse(
                'PROJECT_HAS_APPROVED',
                '專案已有供應商核准，無法修改'
            );
        }

        $data = [];

        if ($this->request->getJsonVar('name')) {
            $data['name'] = $this->request->getJsonVar('name');
        }

        if ($this->request->getJsonVar('year')) {
            $data['year'] = $this->request->getJsonVar('year');
        }

        // Handle templateId and templateVersion update
        $templateId = $this->request->getJsonVar('templateId');
        $templateVersion = $this->request->getJsonVar('templateVersion');

        if ($templateId || $templateVersion) {
            // Check if any supplier has started filling (not just DRAFT/IN_PROGRESS without answers)
            // For simplicity, we check if any supplier is beyond IN_PROGRESS status
            $hasStartedFilling = $stats['byStatus']['SUBMITTED'] > 0 ||
                $stats['byStatus']['REVIEWING'] > 0 ||
                $stats['byStatus']['APPROVED'] > 0;

            if ($hasStartedFilling) {
                return $this->conflictResponse(
                    'TEMPLATE_CHANGE_NOT_ALLOWED',
                    '範本無法修改，已有供應商開始填寫問卷'
                );
            }

            // Validate template exists
            if ($templateId) {
                $template = $this->templateModel->find($templateId);
                if (!$template) {
                    return $this->notFoundResponse('找不到指定的範本');
                }
                $data['template_id'] = $templateId;
            }

            // Validate version exists
            if ($templateVersion) {
                $versionModel = new TemplateVersionModel();
                $targetTemplateId = $templateId ?? $project->template_id;
                $version = $versionModel->getVersion($targetTemplateId, $templateVersion);
                if (!$version) {
                    return $this->notFoundResponse('找不到指定的範本版本');
                }
                $data['template_version'] = $templateVersion;
            }
        }

        // Review config can only be updated if no supplier has submitted
        $reviewConfig = $this->request->getJsonVar('reviewConfig');
        if ($reviewConfig) {
            $reviewConfig = json_decode(json_encode($reviewConfig), true);

            // Check if any supplier has submitted
            $hasSubmitted = $stats['byStatus']['SUBMITTED'] > 0 ||
                $stats['byStatus']['REVIEWING'] > 0 ||
                $stats['byStatus']['APPROVED'] > 0;

            if ($hasSubmitted) {
                return $this->conflictResponse(
                    'PROJECT_ALREADY_SUBMITTED',
                    '已有供應商提交，無法修改審核流程'
                );
            }

            $this->reviewConfigModel->createConfigForProject($projectId, $reviewConfig);
        }

        // Handle adding/removing suppliers
        $supplierIds = $this->request->getJsonVar('supplierIds');
        if ($supplierIds) {
            $supplierIds = json_decode(json_encode($supplierIds), true);

            // Get current suppliers
            $currentSuppliers = $this->projectSupplierModel->getSuppliersForProject($projectId);
            $currentSupplierIds = array_map(fn($s) => $s->supplier_id, $currentSuppliers);

            // Add new suppliers
            $newSupplierIds = array_diff($supplierIds, $currentSupplierIds);
            if (!empty($newSupplierIds)) {
                $this->projectSupplierModel->addSuppliersToProject($projectId, $newSupplierIds);
            }

            // Note: Removing suppliers is not implemented to prevent data loss
            // Consider soft-delete if needed
        }

        if (!empty($data)) {
            $this->projectModel->update($projectId, $data);
        }

        $updatedProject = $this->projectModel->find($projectId);
        $reviewConfigData = $this->reviewConfigModel->getConfigForProject($projectId);
        $suppliers = $this->projectSupplierModel->getSuppliersForProject($projectId);

        return $this->successResponse([
            'id' => $updatedProject->id,
            'name' => $updatedProject->name,
            'year' => $updatedProject->year,
            'type' => $updatedProject->type,
            'templateId' => $updatedProject->template_id,
            'templateVersion' => $updatedProject->template_version,
            'suppliers' => array_map(function ($s) {
                return [
                    'id' => $s->id,
                    'supplierId' => $s->supplier_id,
                    'supplierName' => $s->supplier_name,
                    'status' => $s->status,
                    'currentStage' => (int) $s->current_stage,
                ];
            }, $suppliers),
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

        // Check if any supplier has submitted
        $stats = $this->projectSupplierModel->getStatsByProject($projectId);
        $hasActivity = $stats['byStatus']['SUBMITTED'] > 0 ||
            $stats['byStatus']['REVIEWING'] > 0 ||
            $stats['byStatus']['APPROVED'] > 0;

        if ($hasActivity) {
            return $this->conflictResponse(
                'RESOURCE_CONFLICT',
                '專案已有供應商提交，無法刪除'
            );
        }

        // Delete related data
        $this->reviewConfigModel->where('project_id', $projectId)->delete();

        // Delete project_suppliers and related answers/review_logs
        $projectSuppliers = $this->projectSupplierModel->where('project_id', $projectId)->findAll();
        foreach ($projectSuppliers as $ps) {
            model('AnswerModel')->where('project_supplier_id', $ps->id)->delete();
            model('ReviewLogModel')->where('project_supplier_id', $ps->id)->delete();
        }
        $this->projectSupplierModel->where('project_id', $projectId)->delete();

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

        // Get project counts
        $projectStats = $this->projectModel->getStats($type, $year);

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

        // Get supplier status counts across all projects
        $supplierStats = $this->projectSupplierModel->builder()
            ->select('status, COUNT(*) as count')
            ->where('deleted_at IS NULL')
            ->groupBy('status')
            ->get()
            ->getResult();

        $bySupplierStatus = [];
        foreach ($supplierStats as $s) {
            $bySupplierStatus[$s->status] = (int) $s->count;
        }

        return $this->successResponse([
            'totalProjects' => $projectStats['total'],
            'byType' => $byType,
            'byYear' => $byYear,
            'supplierStatus' => $bySupplierStatus,
        ]);
    }
}
