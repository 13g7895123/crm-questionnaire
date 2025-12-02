<?php

namespace App\Controllers\Api\V1;

use App\Models\ProjectModel;
use App\Models\AnswerModel;
use App\Models\TemplateVersionModel;
use CodeIgniter\HTTP\ResponseInterface;

class AnswerController extends BaseApiController
{
    protected ProjectModel $projectModel;
    protected AnswerModel $answerModel;

    public function __construct()
    {
        $this->projectModel = new ProjectModel();
        $this->answerModel = new AnswerModel();
    }

    /**
     * GET /api/v1/projects/{projectId}/answers
     * Get project answers
     */
    public function index($projectId = null): ResponseInterface
    {
        $project = $this->projectModel->find($projectId);

        if (!$project) {
            return $this->notFoundResponse('找不到指定的專案');
        }

        // Check access permission
        if ($this->isSupplier() && $project->supplier_id !== $this->getCurrentOrganizationId()) {
            return $this->errorResponse(
                'SUPPLIER_NOT_ASSIGNED',
                '您無權存取此專案',
                403
            );
        }

        $answers = $this->answerModel->getAnswersForProject($projectId);
        $lastSavedAt = $this->answerModel->getLastSavedAt($projectId);

        return $this->successResponse([
            'projectId' => $projectId,
            'answers' => $answers,
            'lastSavedAt' => $lastSavedAt,
        ]);
    }

    /**
     * PUT /api/v1/projects/{projectId}/answers
     * Save/update answers (draft save)
     */
    public function update($projectId = null): ResponseInterface
    {
        $project = $this->projectModel->find($projectId);

        if (!$project) {
            return $this->notFoundResponse('找不到指定的專案');
        }

        // Only assigned supplier can edit
        if ($this->isSupplier() && $project->supplier_id !== $this->getCurrentOrganizationId()) {
            return $this->errorResponse(
                'SUPPLIER_NOT_ASSIGNED',
                '您無權編輯此專案',
                403
            );
        }

        // Check project status
        if (!in_array($project->status, ['IN_PROGRESS', 'RETURNED'])) {
            return $this->conflictResponse(
                'PROJECT_ALREADY_SUBMITTED',
                '專案已提交，無法修改答案'
            );
        }

        $answers = $this->request->getJsonVar('answers');
        if (!is_array($answers)) {
            return $this->validationErrorResponse(['answers' => '答案格式錯誤']);
        }

        // Basic validation of answers
        $validationErrors = $this->validateAnswersFormat($answers);
        if (!empty($validationErrors)) {
            return $this->validationErrorResponse($validationErrors);
        }

        $savedCount = $this->answerModel->saveAnswers($projectId, $answers);

        return $this->successResponse([
            'projectId' => $projectId,
            'savedCount' => $savedCount,
            'lastSavedAt' => date('c'),
        ]);
    }

    /**
     * POST /api/v1/projects/{projectId}/submit
     * Submit project for review
     */
    public function submit($projectId = null): ResponseInterface
    {
        $project = $this->projectModel->find($projectId);

        if (!$project) {
            return $this->notFoundResponse('找不到指定的專案');
        }

        // Only assigned supplier can submit
        if ($this->isSupplier() && $project->supplier_id !== $this->getCurrentOrganizationId()) {
            return $this->errorResponse(
                'SUPPLIER_NOT_ASSIGNED',
                '您無權提交此專案',
                403
            );
        }

        // Check project status
        if (!in_array($project->status, ['IN_PROGRESS', 'RETURNED'])) {
            return $this->conflictResponse(
                'PROJECT_ALREADY_SUBMITTED',
                '專案已提交，無法重複提交'
            );
        }

        // Validate all required questions are answered
        $versionModel = new TemplateVersionModel();
        $version = $versionModel->getVersion($project->template_id, $project->template_version);
        
        if (!$version) {
            return $this->errorResponse(
                'VALIDATION_ERROR',
                '範本版本不存在',
                422
            );
        }

        $questions = $version->questions;
        $answers = $this->answerModel->getAnswersForProject($projectId);

        $missingQuestions = [];
        foreach ($questions as $question) {
            if ($question['required'] ?? false) {
                $questionId = $question['id'];
                if (!isset($answers[$questionId]) || $this->isAnswerEmpty($answers[$questionId]['value'])) {
                    $missingQuestions[] = [
                        'questionId' => $questionId,
                        'questionText' => $question['text'],
                    ];
                }
            }
        }

        if (!empty($missingQuestions)) {
            return $this->errorResponse(
                'VALIDATION_ERROR',
                '尚有必填題目未完成',
                422,
                ['missingQuestions' => $missingQuestions]
            );
        }

        // Update project status
        $this->projectModel->update($projectId, [
            'status' => 'SUBMITTED',
            'current_stage' => 1,
            'submitted_at' => date('Y-m-d H:i:s'),
        ]);

        // Auto transition to REVIEWING
        $this->projectModel->update($projectId, ['status' => 'REVIEWING']);

        $updatedProject = $this->projectModel->find($projectId);

        return $this->successResponse([
            'projectId' => $projectId,
            'status' => $updatedProject->status,
            'submittedAt' => $updatedProject->submitted_at?->format("c"),
            'message' => '專案已成功提交，將進入審核流程',
        ]);
    }

    /**
     * Validate answers format
     */
    protected function validateAnswersFormat(array $answers): array
    {
        $errors = [];

        foreach ($answers as $questionId => $answer) {
            if (!isset($answer['questionId']) || !isset($answer['value'])) {
                $errors[$questionId] = '答案格式錯誤，需包含 questionId 和 value';
            }
        }

        return $errors;
    }

    /**
     * Check if answer is empty
     */
    protected function isAnswerEmpty($value): bool
    {
        if ($value === null) return true;
        if ($value === '') return true;
        if (is_array($value) && empty($value)) return true;
        return false;
    }
}
