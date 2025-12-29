<?php

namespace App\Controllers\Api\V1;

use App\Models\ProjectModel;
use App\Models\ProjectSupplierModel;
use App\Models\AnswerModel;
use App\Models\TemplateVersionModel;
use App\Repositories\ProjectBasicInfoRepository;
use App\Repositories\TemplateStructureRepository;
use App\Libraries\ScoringEngine;
use App\Libraries\ConditionalLogicEngine;
use App\Libraries\AnswerValidator;
use CodeIgniter\HTTP\ResponseInterface;

class AnswerController extends BaseApiController
{
    protected ProjectModel $projectModel;
    protected ProjectSupplierModel $projectSupplierModel;
    protected AnswerModel $answerModel;
    protected ProjectBasicInfoRepository $basicInfoRepo;
    protected TemplateStructureRepository $structureRepo;
    protected ScoringEngine $scoringEngine;
    protected ConditionalLogicEngine $conditionalEngine;
    protected AnswerValidator $answerValidator;

    public function __construct()
    {
        $this->projectModel = new ProjectModel();
        $this->projectSupplierModel = new ProjectSupplierModel();
        $this->answerModel = new AnswerModel();
        $this->basicInfoRepo = new ProjectBasicInfoRepository();
        $this->structureRepo = new TemplateStructureRepository();
        $this->scoringEngine = new ScoringEngine();
        $this->conditionalEngine = new ConditionalLogicEngine();
        $this->answerValidator = new AnswerValidator();
    }

    /**
     * GET /api/v1/project-suppliers/{projectSupplierId}/answers
     * Get answers for a specific project-supplier
     */
    public function index($projectSupplierId = null): ResponseInterface
    {
        $projectSupplier = $this->projectSupplierModel->find($projectSupplierId);

        if (!$projectSupplier) {
            return $this->notFoundResponse('找不到指定的專案供應商記錄');
        }

        // Check access permission
        if ($this->isSupplier() && $projectSupplier->supplier_id != $this->getCurrentOrganizationId()) {
            return $this->errorResponse(
                'SUPPLIER_NOT_ASSIGNED',
                '您無權存取此專案',
                403
            );
        }

        $answers = $this->answerModel->getAnswersForProjectSupplier($projectSupplierId);
        $lastSavedAt = $this->answerModel->getLastSavedAt($projectSupplierId);

        return $this->successResponse([
            'projectSupplierId' => (int) $projectSupplierId,
            'answers' => $answers,
            'lastSavedAt' => $lastSavedAt,
        ]);
    }

    /**
     * PUT /api/v1/project-suppliers/{projectSupplierId}/answers
     * Save/update answers (draft save)
     */
    public function update($projectSupplierId = null): ResponseInterface
    {
        $projectSupplier = $this->projectSupplierModel->find($projectSupplierId);

        if (!$projectSupplier) {
            return $this->notFoundResponse('找不到指定的專案供應商記錄');
        }

        // Only assigned supplier can edit
        if ($this->isSupplier() && $projectSupplier->supplier_id != $this->getCurrentOrganizationId()) {
            return $this->errorResponse(
                'SUPPLIER_NOT_ASSIGNED',
                '您無權編輯此專案',
                403
            );
        }

        // Check project supplier status
        if (!in_array($projectSupplier->status, ['IN_PROGRESS', 'RETURNED'])) {
            return $this->conflictResponse(
                'PROJECT_ALREADY_SUBMITTED',
                '專案已提交，無法修改答案'
            );
        }

        // Get JSON body
        $json = $this->request->getJSON(true);
        $answers = $json['answers'] ?? null;

        if (!is_array($answers)) {
            return $this->validationErrorResponse(['answers' => '答案格式錯誤，請確認 answers 欄位為物件格式']);
        }

        // Allow empty answers (just save nothing)
        if (empty($answers)) {
            return $this->successResponse([
                'projectSupplierId' => (int) $projectSupplierId,
                'savedCount' => 0,
                'lastSavedAt' => date('c'),
            ]);
        }

        // Basic validation of answers
        $validationErrors = $this->validateAnswersFormat($answers);
        if (!empty($validationErrors)) {
            return $this->validationErrorResponse($validationErrors);
        }

        $savedCount = $this->answerModel->saveAnswers($projectSupplierId, $answers);

        return $this->successResponse([
            'projectSupplierId' => (int) $projectSupplierId,
            'savedCount' => $savedCount,
            'lastSavedAt' => date('c'),
        ]);
    }

    /**
     * POST /api/v1/project-suppliers/{projectSupplierId}/submit
     * Submit project for review
     */
    public function submit($projectSupplierId = null): ResponseInterface
    {
        $projectSupplier = $this->projectSupplierModel->find($projectSupplierId);

        if (!$projectSupplier) {
            return $this->notFoundResponse('找不到指定的專案供應商記錄');
        }

        // Only assigned supplier can submit
        if ($this->isSupplier() && $projectSupplier->supplier_id != $this->getCurrentOrganizationId()) {
            return $this->errorResponse(
                'SUPPLIER_NOT_ASSIGNED',
                '您無權提交此專案',
                403
            );
        }

        // Check project supplier status
        if (!in_array($projectSupplier->status, ['IN_PROGRESS', 'RETURNED'])) {
            return $this->conflictResponse(
                'PROJECT_ALREADY_SUBMITTED',
                '專案已提交，無法重複提交'
            );
        }

        // Get project to validate template
        $project = $this->projectModel->find($projectSupplier->project_id);
        if (!$project) {
            return $this->notFoundResponse('找不到關聯的專案');
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

        $questions = $version->questions ?? [];
        $answers = $this->answerModel->getAnswersForProjectSupplier($projectSupplierId);

        $missingQuestions = [];

        // Only validate if questions exist
        if (is_array($questions) || is_object($questions)) {
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
        }

        if (!empty($missingQuestions)) {
            return $this->errorResponse(
                'VALIDATION_ERROR',
                '尚有必填題目未完成',
                422,
                ['missingQuestions' => $missingQuestions]
            );
        }

        // Update project supplier status
        $this->projectSupplierModel->update($projectSupplierId, [
            'status' => 'REVIEWING',
            'current_stage' => 1,
            'submitted_at' => date('Y-m-d H:i:s'),
        ]);

        $updatedProjectSupplier = $this->projectSupplierModel->find($projectSupplierId);

        return $this->successResponse([
            'projectSupplierId' => (int) $projectSupplierId,
            'status' => $updatedProjectSupplier->status,
            'submittedAt' => $updatedProjectSupplier->submitted_at,
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

    /**
     * GET /api/v1/project-suppliers/{projectSupplierId}/basic-info
     * Get basic company info for SAQ template
     */
    public function getBasicInfo($projectSupplierId = null): ResponseInterface
    {
        $projectSupplier = $this->projectSupplierModel->find($projectSupplierId);

        if (!$projectSupplier) {
            return $this->notFoundResponse('找不到指定的專案供應商記錄');
        }

        // Check access permission
        if ($this->isSupplier() && $projectSupplier->supplier_id != $this->getCurrentOrganizationId()) {
            return $this->errorResponse(
                'SUPPLIER_NOT_ASSIGNED',
                '您無權存取此專案',
                403
            );
        }

        $basicInfo = $this->basicInfoRepo->getByProjectSupplierId($projectSupplierId);

        if (!$basicInfo) {
            return $this->successResponse([
                'projectSupplierId' => (int) $projectSupplierId,
                'basicInfo' => null,
            ]);
        }

        return $this->successResponse([
            'projectSupplierId' => (int) $projectSupplierId,
            'basicInfo' => $basicInfo->toApiResponse(),
        ]);
    }

    /**
     * PUT /api/v1/project-suppliers/{projectSupplierId}/basic-info
     * Save/update basic company info
     */
    public function saveBasicInfo($projectSupplierId = null): ResponseInterface
    {
        $projectSupplier = $this->projectSupplierModel->find($projectSupplierId);

        if (!$projectSupplier) {
            return $this->notFoundResponse('找不到指定的專案供應商記錄');
        }

        // Only assigned supplier can edit
        if ($this->isSupplier() && $projectSupplier->supplier_id != $this->getCurrentOrganizationId()) {
            return $this->errorResponse(
                'SUPPLIER_NOT_ASSIGNED',
                '您無權編輯此專案',
                403
            );
        }

        // Check project supplier status
        if (!in_array($projectSupplier->status, ['IN_PROGRESS', 'RETURNED'])) {
            return $this->conflictResponse(
                'PROJECT_ALREADY_SUBMITTED',
                '專案已提交，無法修改資料'
            );
        }

        $data = $this->request->getJsonVar('basicInfo');
        if (!is_array($data)) {
            return $this->validationErrorResponse(['basicInfo' => '資料格式錯誤']);
        }

        // Validate data
        $errors = $this->basicInfoRepo->validateBasicInfoData($data);
        if (!empty($errors)) {
            return $this->validationErrorResponse($errors);
        }

        // Save data
        $success = $this->basicInfoRepo->saveBasicInfo($projectSupplierId, $data);

        if (!$success) {
            return $this->errorResponse(
                'SAVE_ERROR',
                '儲存資料失敗',
                500
            );
        }

        // Return updated data
        return $this->getBasicInfo($projectSupplierId);
    }

    /**
     * POST /api/v1/project-suppliers/{projectSupplierId}/calculate-score
     * Calculate scores for SAQ template
     */
    public function calculateScore($projectSupplierId = null): ResponseInterface
    {
        $projectSupplier = $this->projectSupplierModel->find($projectSupplierId);

        if (!$projectSupplier) {
            return $this->notFoundResponse('找不到指定的專案供應商記錄');
        }

        // Check access permission
        if ($this->isSupplier() && $projectSupplier->supplier_id != $this->getCurrentOrganizationId()) {
            return $this->errorResponse(
                'SUPPLIER_NOT_ASSIGNED',
                '您無權存取此專案',
                403
            );
        }

        // Calculate scores
        $scoreData = $this->scoringEngine->getScoreBreakdown($projectSupplierId);

        return $this->successResponse($scoreData);
    }

    /**
     * GET /api/v1/project-suppliers/{projectSupplierId}/visible-questions
     * Get visible questions based on current answers and conditional logic
     */
    public function getVisibleQuestions($projectSupplierId = null): ResponseInterface
    {
        $projectSupplier = $this->projectSupplierModel->find($projectSupplierId);

        if (!$projectSupplier) {
            return $this->notFoundResponse('找不到指定的專案供應商記錄');
        }

        // Check access permission
        if ($this->isSupplier() && $projectSupplier->supplier_id != $this->getCurrentOrganizationId()) {
            return $this->errorResponse(
                'SUPPLIER_NOT_ASSIGNED',
                '您無權存取此專案',
                403
            );
        }

        // Get project to find template
        $project = $this->projectModel->find($projectSupplier->project_id);
        if (!$project) {
            return $this->notFoundResponse('找不到關聯的專案');
        }

        // Get template structure
        $structure = $this->structureRepo->getTemplateStructure($project->template_id);

        // Get current answers
        $answers = $this->answerModel->getAnswersForProjectSupplier($projectSupplierId);

        // Calculate visible questions
        $visibleQuestions = $this->conditionalEngine->getVisibleQuestions($structure, $answers);

        return $this->successResponse([
            'projectSupplierId' => (int) $projectSupplierId,
            'visibleQuestions' => $visibleQuestions,
        ]);
    }

    /**
     * POST /api/v1/project-suppliers/{projectSupplierId}/validate
     * Validate answers before submission
     */
    public function validateAnswers($projectSupplierId = null): ResponseInterface
    {
        $projectSupplier = $this->projectSupplierModel->find($projectSupplierId);

        if (!$projectSupplier) {
            return $this->notFoundResponse('找不到指定的專案供應商記錄');
        }

        // Check access permission
        if ($this->isSupplier() && $projectSupplier->supplier_id != $this->getCurrentOrganizationId()) {
            return $this->errorResponse(
                'SUPPLIER_NOT_ASSIGNED',
                '您無權存取此專案',
                403
            );
        }

        // Get project to find template
        $project = $this->projectModel->find($projectSupplier->project_id);
        if (!$project) {
            return $this->notFoundResponse('找不到關聯的專案');
        }

        // Get template structure
        $structure = $this->structureRepo->getTemplateStructure($project->template_id);

        // Get current answers
        $answers = $this->answerModel->getAnswersForProjectSupplier($projectSupplierId);

        // Get basic info if SAQ template
        $basicInfo = null;
        $templateModel = model('TemplateModel');
        $template = $templateModel->find($project->template_id);
        if ($template && $template->type === 'SAQ') {
            $basicInfoEntity = $this->basicInfoRepo->getByProjectSupplierId($projectSupplierId);
            $basicInfo = $basicInfoEntity ? $basicInfoEntity->toApiResponse() : null;
        }

        // Validate
        $validation = $this->answerValidator->validateForSubmission($structure, $answers, $basicInfo);

        return $this->successResponse([
            'projectSupplierId' => (int) $projectSupplierId,
            'valid' => $validation['valid'],
            'errors' => $validation['errors'],
        ]);
    }
}
