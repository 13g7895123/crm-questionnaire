<?php

namespace App\Controllers\Api\V1;

use App\Models\FileModel;
use App\Models\ProjectModel;
use App\Models\TemplateVersionModel;
use CodeIgniter\HTTP\ResponseInterface;

class FileController extends BaseApiController
{
    protected FileModel $fileModel;
    protected ProjectModel $projectModel;

    public function __construct()
    {
        $this->fileModel = new FileModel();
        $this->projectModel = new ProjectModel();
    }

    /**
     * POST /api/v1/files/upload
     * Upload file for a project question
     */
    public function upload(): ResponseInterface
    {
        $projectId = $this->request->getPost('projectId');
        $questionId = $this->request->getPost('questionId');

        if (!$projectId || !$questionId) {
            return $this->validationErrorResponse([
                'projectId' => '專案 ID 為必填',
                'questionId' => '題目 ID 為必填',
            ]);
        }

        // Verify project access
        $project = $this->projectModel->find($projectId);
        if (!$project) {
            return $this->notFoundResponse('找不到指定的專案');
        }

        if ($this->isSupplier() && $project->supplier_id !== $this->getCurrentOrganizationId()) {
            return $this->errorResponse(
                'SUPPLIER_NOT_ASSIGNED',
                '您無權上傳檔案到此專案',
                403
            );
        }

        // Get file from request
        $file = $this->request->getFile('file');

        if (!$file || !$file->isValid()) {
            return $this->validationErrorResponse(['file' => '請選擇要上傳的檔案']);
        }

        // Get question config for validation
        $versionModel = new TemplateVersionModel();
        $version = $versionModel->getVersion($project->template_id, $project->template_version);
        
        $questionConfig = null;
        if ($version && $version->questions) {
            foreach ($version->questions as $q) {
                if ($q['id'] === $questionId) {
                    $questionConfig = $q['config'] ?? null;
                    break;
                }
            }
        }

        // Default config
        $maxFileSize = $questionConfig['maxFileSize'] ?? 5242880; // 5MB
        $allowedTypes = $questionConfig['allowedFileTypes'] ?? ['pdf', 'jpg', 'png', 'docx'];

        // Validate file size
        if ($file->getSize() > $maxFileSize) {
            return $this->errorResponse(
                'FILE_TOO_LARGE',
                '檔案大小超過限制 (' . round($maxFileSize / 1048576, 1) . 'MB)',
                413
            );
        }

        // Validate file type
        $extension = strtolower($file->getExtension());
        if (!in_array($extension, $allowedTypes)) {
            return $this->errorResponse(
                'VALIDATION_ERROR',
                '不允許的檔案類型',
                422,
                [
                    'allowedTypes' => $allowedTypes,
                    'receivedType' => $extension,
                ]
            );
        }

        // Generate unique filename
        $newFileName = $this->generateUuid('f') . '.' . $extension;

        // Move file to storage
        $uploadPath = WRITEPATH . 'uploads/' . $projectId;
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        if (!$file->move($uploadPath, $newFileName)) {
            return $this->errorResponse(
                'FILE_UPLOAD_ERROR',
                '檔案上傳失敗',
                500
            );
        }

        // Generate file URL
        $fileUrl = base_url("uploads/{$projectId}/{$newFileName}");

        // Save file record
        $fileRecord = $this->fileModel->createFile([
            'project_id' => $projectId,
            'question_id' => $questionId,
            'file_name' => $file->getClientName(),
            'file_path' => $uploadPath . '/' . $newFileName,
            'file_url' => $fileUrl,
            'file_size' => $file->getSize(),
            'content_type' => $file->getMimeType(),
        ]);

        return $this->successResponse([
            'fileId' => $fileRecord->id,
            'fileName' => $file->getClientName(),
            'fileSize' => $file->getSize(),
            'fileUrl' => $fileUrl,
            'contentType' => $file->getMimeType(),
            'uploadedAt' => date('c'),
        ]);
    }
}
