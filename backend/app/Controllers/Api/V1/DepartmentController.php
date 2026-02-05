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

    /**
     * GET /api/v1/departments/import-template
     * Download import template (ADMIN only)
     */
    public function downloadImportTemplate(): ResponseInterface
    {
        if (!$this->isAdmin()) {
            return $this->forbiddenResponse();
        }

        try {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Get all organizations for reference
            $organizations = $this->orgModel->findAll();
            $orgNames = array_map(fn($org) => $org->name, $organizations);
            $orgList = '"' . implode(',', $orgNames) . '"';

            // Set headers
            $headers = ['部門名稱', '組織名稱', '備註'];
            $sheet->fromArray([$headers], null, 'A1');

            // Style headers
            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5'],
                ],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            ];
            $sheet->getStyle('A1:C1')->applyFromArray($headerStyle);

            // Add sample data
            $sampleOrg = !empty($organizations) ? $organizations[0]->name : '範例組織';
            $sampleData = [
                ['品質管理部', $sampleOrg, '這是範例，可以刪除'],
                ['採購部', $sampleOrg, ''],
                ['研發部', $sampleOrg, ''],
            ];
            $sheet->fromArray($sampleData, null, 'A2');

            // Set column widths
            $sheet->getColumnDimension('A')->setWidth(30);
            $sheet->getColumnDimension('B')->setWidth(30);
            $sheet->getColumnDimension('C')->setWidth(40);

            // Add data validation for Organization column if organizations exist
            if (!empty($organizations)) {
                $validation = $sheet->getCell('B2')->getDataValidation();
                $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP);
                $validation->setAllowBlank(false);
                $validation->setShowDropDown(true);
                $validation->setFormula1($orgList);
                $validation->setShowErrorMessage(true);
                $validation->setErrorTitle('錯誤的組織');
                $validation->setError('請選擇現有的組織名稱');

                // Apply validation to more rows
                for ($row = 2; $row <= 100; $row++) {
                    $sheet->getCell("B{$row}")->setDataValidation(clone $validation);
                }
            }

            // Add instructions sheet
            $instructionSheet = $spreadsheet->createSheet(1);
            $instructionSheet->setTitle('使用說明');
            $instructions = [
                ['部門批量匯入範本使用說明'],
                [''],
                ['欄位說明：'],
                ['1. 部門名稱：必填，部門的完整名稱'],
                ['2. 組織名稱：必填，必須是系統中已存在的組織（請從下拉選單選擇）'],
                ['3. 備註：選填，僅用於參考，不會匯入系統'],
                [''],
                ['重要提示：'],
                ['• 同一組織內的部門名稱不可重複'],
                ['• 不同組織可以有相同名稱的部門'],
                ['• 組織名稱必須與系統中已存在的組織完全一致'],
                ['• 重複的記錄將被跳過'],
                ['• 請勿修改表頭（第一行）'],
                ['• 範例資料可以刪除或保留（保留會被匯入）'],
                ['• 最多可匯入 1000 筆資料'],
                [''],
                ['匯入步驟：'],
                ['1. 填寫部門資料（組織名稱請從下拉選單選擇）'],
                ['2. 儲存檔案'],
                ['3. 在系統中選擇此檔案上傳'],
                ['4. 系統會顯示匯入結果'],
                [''],
                ['現有組織列表：'],
            ];
            $instructionSheet->fromArray($instructions, null, 'A1');
            
            // Add organization list
            $orgRow = count($instructions) + 1;
            foreach ($organizations as $org) {
                $instructionSheet->setCellValue("A{$orgRow}", "• {$org->name} ({$org->type})");
                $orgRow++;
            }

            $instructionSheet->getColumnDimension('A')->setWidth(80);
            $instructionSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $instructionSheet->getStyle('A3')->getFont()->setBold(true);
            $instructionSheet->getStyle('A8')->getFont()->setBold(true);
            $instructionSheet->getStyle('A17')->getFont()->setBold(true);
            $instructionSheet->getStyle('A23')->getFont()->setBold(true);

            // Set active sheet back to data sheet
            $spreadsheet->setActiveSheetIndex(0);

            // Generate file
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $filename = '部門匯入範本_' . date('Ymd') . '.xlsx';

            // Create temp file
            $tempFile = WRITEPATH . 'uploads/' . $filename;
            $writer->save($tempFile);

            // Return file download response
            return $this->response
                ->download($tempFile, null)
                ->setFileName($filename)
                ->setContentType('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        } catch (\Exception $e) {
            log_message('error', 'Download department template failed: ' . $e->getMessage());
            return $this->errorResponse('TEMPLATE_DOWNLOAD_ERROR', '下載範本失敗：' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/v1/departments/import
     * Import departments from Excel (ADMIN only)
     */
    public function import(): ResponseInterface
    {
        if (!$this->isAdmin()) {
            return $this->forbiddenResponse();
        }

        $file = $this->request->getFile('file');

        if (!$file || !$file->isValid()) {
            return $this->validationErrorResponse(['file' => '請上傳有效的 Excel 檔案']);
        }

        $allowedExtensions = ['xlsx', 'xls'];
        if (!in_array($file->getExtension(), $allowedExtensions)) {
            return $this->validationErrorResponse(['file' => '只允許上傳 .xlsx 或 .xls 格式的檔案']);
        }

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getTempName());
            $sheet = $spreadsheet->getActiveSheet();
            $data = $sheet->toArray();

            // Remove header row
            array_shift($data);

            // Filter out empty rows
            $data = array_filter($data, function ($row) {
                return !empty($row[0]) && !empty($row[1]);  // Name and organization must be present
            });

            if (empty($data)) {
                return $this->validationErrorResponse(['file' => 'Excel 檔案中沒有有效資料']);
            }

            if (count($data) > 1000) {
                return $this->validationErrorResponse(['file' => '一次最多只能匯入 1000 筆資料']);
            }

            // Get all organizations for lookup
            $organizations = $this->orgModel->findAll();
            $orgMap = [];
            foreach ($organizations as $org) {
                $orgMap[$org->name] = $org->id;
            }

            $db = \Config\Database::connect();
            $db->transStart();

            $successCount = 0;
            $skipCount = 0;
            $errors = [];

            foreach ($data as $index => $row) {
                $rowNum = $index + 2; // +2 because array index starts at 0 and we removed header

                $name = trim($row[0] ?? '');
                $orgName = trim($row[1] ?? '');

                // Validation
                if (empty($name)) {
                    $errors[] = "第 {$rowNum} 行：部門名稱不可為空";
                    continue;
                }

                if (empty($orgName)) {
                    $errors[] = "第 {$rowNum} 行：組織名稱不可為空";
                    continue;
                }

                // Check if organization exists
                if (!isset($orgMap[$orgName])) {
                    $errors[] = "第 {$rowNum} 行：找不到組織 '{$orgName}'，請確認組織名稱正確";
                    continue;
                }

                $organizationId = $orgMap[$orgName];

                // Check if department already exists in this organization
                if ($this->deptModel->existsInOrganization($name, $organizationId)) {
                    $skipCount++;
                    $errors[] = "第 {$rowNum} 行：組織 '{$orgName}' 已存在部門 '{$name}'，已跳過";
                    continue;
                }

                // Insert department
                try {
                    $this->deptModel->insert([
                        'name' => $name,
                        'organization_id' => $organizationId,
                    ]);
                    $successCount++;
                } catch (\Exception $e) {
                    $errors[] = "第 {$rowNum} 行：新增失敗 - " . $e->getMessage();
                }
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->errorResponse('IMPORT_ERROR', '匯入失敗，請檢查資料後重試', 500);
            }

            return $this->successResponse([
                'success' => $successCount,
                'skipped' => $skipCount,
                'total' => count($data),
                'errors' => $errors,
                'message' => "成功匯入 {$successCount} 筆，跳過 {$skipCount} 筆" . (count($errors) > 0 ? '（部分資料有錯誤）' : ''),
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Import departments failed: ' . $e->getMessage());
            return $this->errorResponse('IMPORT_ERROR', '匯入失敗：' . $e->getMessage(), 500);
        }
    }
}
