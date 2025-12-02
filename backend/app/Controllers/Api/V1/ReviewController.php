<?php

namespace App\Controllers\Api\V1;

use App\Models\ProjectModel;
use App\Models\ReviewLogModel;
use App\Models\ReviewStageConfigModel;
use CodeIgniter\HTTP\ResponseInterface;

class ReviewController extends BaseApiController
{
    protected ProjectModel $projectModel;
    protected ReviewLogModel $reviewLogModel;
    protected ReviewStageConfigModel $reviewConfigModel;

    public function __construct()
    {
        $this->projectModel = new ProjectModel();
        $this->reviewLogModel = new ReviewLogModel();
        $this->reviewConfigModel = new ReviewStageConfigModel();
    }

    /**
     * GET /api/v1/reviews/pending
     * Get pending review projects for current user's department (HOST only)
     */
    public function pending(): ResponseInterface
    {
        if (!$this->isHost() && !$this->isAdmin()) {
            return $this->forbiddenResponse();
        }

        $departmentId = $this->getCurrentDepartmentId();
        if (!$departmentId) {
            return $this->errorResponse(
                'VALIDATION_ERROR',
                '您尚未設定所屬部門',
                422
            );
        }

        $pagination = $this->getPaginationParams();
        
        $filters = [
            'type' => $this->request->getGet('type'),
            'sortBy' => $this->request->getGet('sortBy'),
            'order' => $this->request->getGet('order'),
        ];

        $result = $this->projectModel->getPendingReviewsForDepartment(
            $departmentId,
            $filters,
            $pagination['page'],
            $pagination['limit']
        );

        // Format response
        $data = array_map(function ($project) {
            return [
                'id' => $project->id,
                'name' => $project->name,
                'year' => (int) $project->year,
                'type' => $project->type,
                'supplierId' => $project->supplier_id,
                'supplier' => [
                    'id' => $project->supplier_id,
                    'name' => $project->supplier_name,
                ],
                'status' => $project->status,
                'currentStage' => (int) $project->current_stage,
                'totalStages' => (int) ($project->total_stages ?? 0),
                'submittedAt' => $project->submitted_at,
                'updatedAt' => $project->updated_at,
            ];
        }, $result['data']);

        $result['data'] = $data;
        return $this->paginatedResponse($result);
    }

    /**
     * POST /api/v1/projects/{projectId}/review
     * Review project (approve or return)
     */
    public function review($projectId = null): ResponseInterface
    {
        if (!$this->isHost() && !$this->isAdmin()) {
            return $this->forbiddenResponse();
        }

        $project = $this->projectModel->find($projectId);
        if (!$project) {
            return $this->notFoundResponse('找不到指定的專案');
        }

        // Check project status
        if ($project->status !== 'REVIEWING') {
            return $this->conflictResponse(
                'RESOURCE_CONFLICT',
                '專案目前不在審核狀態'
            );
        }

        // Check if current user's department is responsible for current stage
        $departmentId = $this->getCurrentDepartmentId();
        $currentStageConfig = $this->reviewConfigModel->getStage($projectId, $project->current_stage);

        if (!$currentStageConfig || $currentStageConfig->department_id !== $departmentId) {
            return $this->errorResponse(
                'AUTH_INSUFFICIENT_PERMISSION',
                '您的部門不負責目前審核階段',
                403
            );
        }

        // Check if specific approver is set
        if ($currentStageConfig->approver_id && $currentStageConfig->approver_id !== $this->getCurrentUserId()) {
            return $this->errorResponse(
                'AUTH_INSUFFICIENT_PERMISSION',
                '您不是此審核階段的指定審核者',
                403
            );
        }

        $rules = [
            'action' => 'required|in_list[APPROVE,RETURN]',
            'comment' => 'required|max_length[1000]',
        ];

        if (!$this->validate($rules)) {
            return $this->validationErrorResponse($this->validator->getErrors());
        }

        $action = $this->request->getJsonVar('action');
        $comment = $this->request->getJsonVar('comment');

        // Return action requires comment
        if ($action === 'RETURN' && empty(trim($comment))) {
            return $this->validationErrorResponse([
                'comment' => '退回專案時必須填寫審核意見',
            ]);
        }

        // Create review log
        $reviewLog = $this->reviewLogModel->createLog(
            $projectId,
            $this->getCurrentUserId(),
            $project->current_stage,
            $action,
            $comment
        );

        // Get reviewer info
        $user = model('UserModel')->find($this->getCurrentUserId());

        $previousStage = $project->current_stage;
        $newStatus = $project->status;
        $newStage = $project->current_stage;
        $message = '';

        if ($action === 'APPROVE') {
            $totalStages = $this->reviewConfigModel->getTotalStages($projectId);

            if ($project->current_stage >= $totalStages) {
                // Final stage approved
                $newStatus = 'APPROVED';
                $message = '已核准，專案審核完成';
            } else {
                // Move to next stage
                $newStage = $project->current_stage + 1;
                $message = "已核准，專案進入第 {$newStage} 階段審核";
            }
        } else {
            // RETURN
            $newStatus = 'RETURNED';
            $newStage = 0;
            $message = '已退回給供應商重新填寫';
        }

        // Update project
        $this->projectModel->update($projectId, [
            'status' => $newStatus,
            'current_stage' => $newStage,
        ]);

        return $this->successResponse([
            'projectId' => $projectId,
            'action' => $action,
            'previousStage' => $previousStage,
            'currentStage' => $newStage,
            'status' => $newStatus,
            'message' => $message,
            'reviewLog' => [
                'id' => $reviewLog->id,
                'reviewerId' => $this->getCurrentUserId(),
                'reviewerName' => $user?->username,
                'stage' => $previousStage,
                'action' => $action,
                'comment' => $comment,
                'timestamp' => $reviewLog->created_at?->format("c"),
            ],
        ]);
    }

    /**
     * GET /api/v1/projects/{projectId}/reviews
     * Get review history for a project
     */
    public function history($projectId = null): ResponseInterface
    {
        $project = $this->projectModel->find($projectId);
        if (!$project) {
            return $this->notFoundResponse('找不到指定的專案');
        }

        // Supplier can only see their own project's reviews
        if ($this->isSupplier() && $project->supplier_id !== $this->getCurrentOrganizationId()) {
            return $this->errorResponse(
                'SUPPLIER_NOT_ASSIGNED',
                '您無權存取此專案',
                403
            );
        }

        $reviews = $this->reviewLogModel->getHistoryForProject($projectId);

        return $this->successResponse([
            'projectId' => $projectId,
            'projectName' => $project->name,
            'currentStatus' => $project->status,
            'reviews' => array_map(function ($review) {
                return [
                    'id' => $review->id,
                    'reviewerId' => $review->reviewer_id,
                    'reviewerName' => $review->reviewer_name,
                    'reviewerDepartment' => $review->department_id ? [
                        'id' => $review->department_id,
                        'name' => $review->department_name,
                    ] : null,
                    'stage' => (int) $review->stage,
                    'action' => $review->action,
                    'comment' => $review->comment,
                    'timestamp' => $review->created_at,
                ];
            }, $reviews),
        ]);
    }

    /**
     * GET /api/v1/reviews/stats
     * Get review statistics for current user's department
     */
    public function stats(): ResponseInterface
    {
        if (!$this->isHost() && !$this->isAdmin()) {
            return $this->forbiddenResponse();
        }

        $departmentId = $this->getCurrentDepartmentId();
        if (!$departmentId) {
            return $this->errorResponse(
                'VALIDATION_ERROR',
                '您尚未設定所屬部門',
                422
            );
        }

        $type = $this->request->getGet('type');
        $startDate = $this->request->getGet('startDate');
        $endDate = $this->request->getGet('endDate');

        // Get pending count
        $pendingCount = $this->projectModel->builder()
            ->join('review_stage_configs', 'review_stage_configs.project_id = projects.id AND review_stage_configs.stage_order = projects.current_stage')
            ->where('projects.status', 'REVIEWING')
            ->where('review_stage_configs.department_id', $departmentId)
            ->where('projects.deleted_at IS NULL')
            ->countAllResults();

        // Get month stats
        $monthStart = date('Y-m-01 00:00:00');
        $monthStats = $this->reviewLogModel->getStatsForDepartment(
            $departmentId,
            $type,
            $monthStart,
            null
        );

        // Calculate average review time (simplified)
        $avgReviewTime = 2.5; // Placeholder - would need more complex calculation

        // Get department info
        $department = model('DepartmentModel')->find($departmentId);

        // Stats by type
        $byType = [];
        foreach (['SAQ', 'CONFLICT'] as $projectType) {
            $typeStats = $this->reviewLogModel->getStatsForDepartment($departmentId, $projectType, $monthStart, null);
            $typePending = $this->projectModel->builder()
                ->join('review_stage_configs', 'review_stage_configs.project_id = projects.id AND review_stage_configs.stage_order = projects.current_stage')
                ->where('projects.status', 'REVIEWING')
                ->where('projects.type', $projectType)
                ->where('review_stage_configs.department_id', $departmentId)
                ->where('projects.deleted_at IS NULL')
                ->countAllResults();

            $byType[$projectType] = [
                'pending' => $typePending,
                'approved' => $typeStats['APPROVE'],
                'returned' => $typeStats['RETURN'],
            ];
        }

        return $this->successResponse([
            'departmentId' => $departmentId,
            'departmentName' => $department?->name,
            'pending' => $pendingCount,
            'approvedThisMonth' => $monthStats['APPROVE'],
            'returnedThisMonth' => $monthStats['RETURN'],
            'averageReviewTime' => $avgReviewTime,
            'byType' => $byType,
        ]);
    }
}
