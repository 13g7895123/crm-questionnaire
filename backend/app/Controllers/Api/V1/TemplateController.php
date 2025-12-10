<?php

namespace App\Controllers\Api\V1;

use App\Models\TemplateModel;
use App\Models\TemplateVersionModel;
use App\Repositories\TemplateStructureRepository;
use CodeIgniter\HTTP\ResponseInterface;

class TemplateController extends BaseApiController
{
    protected TemplateModel $templateModel;
    protected TemplateVersionModel $versionModel;
    protected TemplateStructureRepository $structureRepo;

    public function __construct()
    {
        $this->templateModel = new TemplateModel();
        $this->versionModel = new TemplateVersionModel();
        $this->structureRepo = new TemplateStructureRepository();
    }

    /**
     * GET /api/v1/templates
     * Get templates list (HOST only)
     */
    public function index(): ResponseInterface
    {
        if (!$this->isHost() && !$this->isAdmin()) {
            return $this->forbiddenResponse();
        }

        $pagination = $this->getPaginationParams();
        
        $filters = [
            'type' => $this->request->getGet('type'),
            'search' => $this->request->getGet('search'),
        ];

        $result = $this->templateModel->getTemplatesWithVersions(
            $filters,
            $pagination['page'],
            $pagination['limit']
        );

        // Format response
        $data = array_map(function ($template) {
            return [
                'id' => $template->id,
                'name' => $template->name,
                'type' => $template->type,
                'latestVersion' => $template->latest_version,
                'versions' => array_map(function ($v) {
                    return [
                        'version' => $v->version,
                        'createdAt' => $v->created_at,
                    ];
                }, $template->versions ?? []),
                'createdAt' => $template->created_at,
                'updatedAt' => $template->updated_at,
            ];
        }, $result['data']);

        $result['data'] = $data;
        return $this->paginatedResponse($result);
    }

    /**
     * GET /api/v1/templates/{templateId}
     * Get template details with latest version questions
     */
    public function show($templateId = null): ResponseInterface
    {
        $template = $this->templateModel->find($templateId);

        if (!$template) {
            return $this->notFoundResponse('找不到指定的範本');
        }

        // Get all versions
        $versions = $this->versionModel->where('template_id', $templateId)
                                       ->orderBy('created_at', 'DESC')
                                       ->findAll();

        // Get latest version questions
        $latestVersion = $this->versionModel->getVersion($templateId, $template->latest_version);

        return $this->successResponse([
            'id' => $template->id,
            'name' => $template->name,
            'type' => $template->type,
            'latestVersion' => $template->latest_version,
            'versions' => array_map(function ($v) {
                return [
                    'version' => $v->version,
                    'createdAt' => $v->created_at?->format("c"),
                ];
            }, $versions),
            'currentVersionQuestions' => $latestVersion ? $latestVersion->questions : [],
            'createdAt' => $template->created_at?->format("c"),
            'updatedAt' => $template->updated_at?->format("c"),
        ]);
    }

    /**
     * POST /api/v1/templates
     * Create template (HOST only) - auto creates version 1.0.0
     */
    public function create(): ResponseInterface
    {
        if (!$this->isHost() && !$this->isAdmin()) {
            return $this->forbiddenResponse();
        }

        $rules = [
            'name' => 'required|max_length[200]',
            'type' => 'required|in_list[SAQ,CONFLICT]',
            'questions' => 'required',
        ];

        if (!$this->validate($rules)) {
            return $this->validationErrorResponse($this->validator->getErrors());
        }

        $questions = $this->request->getJsonVar('questions');
        if (!is_array($questions) || count($questions) < 1) {
            return $this->validationErrorResponse(['questions' => '至少需要 1 個題目']);
        }

        // Validate questions
        $validatedQuestions = $this->validateQuestions($questions);
        if ($validatedQuestions['error']) {
            return $this->validationErrorResponse($validatedQuestions['errors']);
        }

        // Create template
        $this->templateModel->insert([
            'name' => $this->request->getJsonVar('name'),
            'type' => $this->request->getJsonVar('type'),
            'latest_version' => '1.0.0',
        ]);

        $templateId = $this->templateModel->getInsertID();

        // Create version 1.0.0
        $this->versionModel->insert([
            'template_id' => $templateId,
            'version' => '1.0.0',
            'questions' => json_encode($validatedQuestions['questions']),
        ]);

        $versionId = $this->versionModel->getInsertID();
        $template = $this->templateModel->find($templateId);
        $version = $this->versionModel->find($versionId);

        return $this->successResponse([
            'id' => $template->id,
            'name' => $template->name,
            'type' => $template->type,
            'latestVersion' => $template->latest_version,
            'versions' => [[
                'version' => '1.0.0',
                'createdAt' => $version->created_at?->format("c"),
            ]],
            'currentVersionQuestions' => $version->questions,
            'createdAt' => $template->created_at?->format("c"),
            'updatedAt' => $template->updated_at?->format("c"),
        ], 201);
    }

    /**
     * PUT /api/v1/templates/{templateId}
     * Update template name (HOST only)
     */
    public function update($templateId = null): ResponseInterface
    {
        if (!$this->isHost() && !$this->isAdmin()) {
            return $this->forbiddenResponse();
        }

        $template = $this->templateModel->find($templateId);
        if (!$template) {
            return $this->notFoundResponse('找不到指定的範本');
        }

        $name = $this->request->getJsonVar('name');
        if ($name) {
            $this->templateModel->update($templateId, ['name' => $name]);
        }

        $updatedTemplate = $this->templateModel->find($templateId);

        return $this->successResponse([
            'id' => $updatedTemplate->id,
            'name' => $updatedTemplate->name,
            'type' => $updatedTemplate->type,
            'latestVersion' => $updatedTemplate->latest_version,
            'updatedAt' => $updatedTemplate->updated_at?->format("c"),
        ]);
    }

    /**
     * DELETE /api/v1/templates/{templateId}
     * Delete template (HOST only)
     */
    public function delete($templateId = null): ResponseInterface
    {
        if (!$this->isHost() && !$this->isAdmin()) {
            return $this->forbiddenResponse();
        }

        $template = $this->templateModel->find($templateId);
        if (!$template) {
            return $this->notFoundResponse('找不到指定的範本');
        }

        // Check if template is in use
        $projectCount = $this->templateModel->isInUse($templateId);
        if ($projectCount > 0) {
            return $this->conflictResponse(
                'TEMPLATE_IN_USE',
                '此範本正在被專案使用，無法刪除',
                ['projectCount' => $projectCount]
            );
        }

        // Delete versions
        $this->versionModel->where('template_id', $templateId)->delete();
        
        // Delete template
        $this->templateModel->delete($templateId);

        return $this->respond(null, 204);
    }

    /**
     * POST /api/v1/templates/{templateId}/versions
     * Create new template version (HOST only)
     */
    public function createVersion($templateId = null): ResponseInterface
    {
        if (!$this->isHost() && !$this->isAdmin()) {
            return $this->forbiddenResponse();
        }

        $template = $this->templateModel->find($templateId);
        if (!$template) {
            return $this->notFoundResponse('找不到指定的範本');
        }

        $rules = [
            'version' => 'required|regex_match[/^\d+\.\d+\.\d+$/]',
            'questions' => 'required',
        ];

        if (!$this->validate($rules)) {
            return $this->validationErrorResponse($this->validator->getErrors());
        }

        $version = $this->request->getJsonVar('version');
        $questions = $this->request->getJsonVar('questions');

        // Check version is greater
        if (!$this->versionModel->isVersionGreater($templateId, $version)) {
            return $this->validationErrorResponse([
                'version' => "版本號必須大於目前最新版本 {$template->latest_version}",
            ]);
        }

        // Validate questions
        $validatedQuestions = $this->validateQuestions($questions);
        if ($validatedQuestions['error']) {
            return $this->validationErrorResponse($validatedQuestions['errors']);
        }

        // Create version
        $this->versionModel->insert([
            'template_id' => $templateId,
            'version' => $version,
            'questions' => json_encode($validatedQuestions['questions']),
        ]);

        $versionId = $this->versionModel->getInsertID();

        // Update template latest version
        $this->templateModel->update($templateId, ['latest_version' => $version]);

        // Get all versions
        $versions = $this->versionModel->where('template_id', $templateId)
                                       ->orderBy('created_at', 'DESC')
                                       ->findAll();

        $newVersion = $this->versionModel->find($versionId);
        $updatedTemplate = $this->templateModel->find($templateId);

        return $this->successResponse([
            'id' => $updatedTemplate->id,
            'name' => $updatedTemplate->name,
            'type' => $updatedTemplate->type,
            'latestVersion' => $updatedTemplate->latest_version,
            'versions' => array_map(function ($v) {
                return [
                    'version' => $v->version,
                    'createdAt' => $v->created_at?->format("c"),
                ];
            }, $versions),
            'currentVersionQuestions' => $newVersion->questions,
            'updatedAt' => $updatedTemplate->updated_at?->format("c"),
        ], 201);
    }

    /**
     * GET /api/v1/templates/{templateId}/versions/{version}
     * Get specific template version
     */
    public function showVersion($templateId = null, $version = null): ResponseInterface
    {
        $template = $this->templateModel->find($templateId);
        if (!$template) {
            return $this->notFoundResponse('找不到指定的範本');
        }

        $versionData = $this->versionModel->getVersion($templateId, $version);
        if (!$versionData) {
            return $this->notFoundResponse('找不到指定的範本版本');
        }

        return $this->successResponse([
            'templateId' => $templateId,
            'templateName' => $template->name,
            'templateType' => $template->type,
            'version' => $versionData->version,
            'questions' => $versionData->questions,
            'createdAt' => $versionData->created_at?->format("c"),
        ]);
    }

    /**
     * Validate questions array
     */
    protected function validateQuestions(array $questions): array
    {
        $errors = [];
        $validTypes = ['TEXT', 'NUMBER', 'DATE', 'BOOLEAN', 'SINGLE_CHOICE', 'MULTI_CHOICE', 'FILE', 'RATING'];
        $validatedQuestions = [];

        foreach ($questions as $index => $question) {
            $qIndex = "questions[{$index}]";

            if (empty($question['text'])) {
                $errors["{$qIndex}.text"] = '題目文字為必填';
            }

            if (empty($question['type']) || !in_array($question['type'], $validTypes)) {
                $errors["{$qIndex}.type"] = '題目類型無效';
            }

            if (!isset($question['required'])) {
                $question['required'] = false;
            }

            // Validate options for choice types
            if (in_array($question['type'] ?? '', ['SINGLE_CHOICE', 'MULTI_CHOICE'])) {
                if (empty($question['options']) || !is_array($question['options'])) {
                    $errors["{$qIndex}.options"] = "{$question['type']} 題型必須提供選項";
                }
            }

            // Generate question ID if not provided
            if (empty($question['id'])) {
                $question['id'] = 'q_' . bin2hex(random_bytes(6));
            }

            $validatedQuestions[] = $question;
        }

        if (!empty($errors)) {
            return ['error' => true, 'errors' => $errors];
        }

        return ['error' => false, 'questions' => $validatedQuestions];
    }

    /**
     * GET /api/v1/templates/{templateId}/structure
     * Get template v2.0 hierarchical structure (sections, subsections, questions)
     */
    public function getStructure($templateId = null): ResponseInterface
    {
        $template = $this->templateModel->find($templateId);
        if (!$template) {
            return $this->notFoundResponse('找不到指定的範本');
        }

        // Check if template has v2.0 structure
        if (!$this->structureRepo->hasV2Structure($templateId)) {
            return $this->successResponse([
                'templateId' => $templateId,
                'hasV2Structure' => false,
                'structure' => [
                    'includeBasicInfo' => false,
                    'sections' => [],
                ],
            ]);
        }

        $sections = $this->structureRepo->getTemplateStructure($templateId);

        return $this->successResponse([
            'templateId' => $templateId,
            'hasV2Structure' => true,
            'structure' => [
                'includeBasicInfo' => $template->type === 'SAQ',
                'sections' => $sections,
            ],
        ]);
    }

    /**
     * PUT /api/v1/templates/{templateId}/structure
     * Save/update template v2.0 structure (HOST only)
     */
    public function saveStructure($templateId = null): ResponseInterface
    {
        if (!$this->isHost() && !$this->isAdmin()) {
            return $this->forbiddenResponse();
        }

        $template = $this->templateModel->find($templateId);
        if (!$template) {
            return $this->notFoundResponse('找不到指定的範本');
        }

        $rules = [
            'sections' => 'required',
        ];

        if (!$this->validate($rules)) {
            return $this->validationErrorResponse($this->validator->getErrors());
        }

        $sections = $this->request->getJsonVar('sections');
        if (!is_array($sections)) {
            return $this->validationErrorResponse(['sections' => '結構格式錯誤']);
        }

        // Save structure
        $success = $this->structureRepo->saveTemplateStructure($templateId, $sections);

        if (!$success) {
            return $this->internalErrorResponse('儲存範本結構失敗');
        }

        // Return updated structure
        return $this->getStructure($templateId);
    }
}
