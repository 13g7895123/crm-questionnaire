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

        $this->orgModel->insert([
            'name' => $name,
            'type' => $this->request->getJsonVar('type'),
        ]);

        $orgId = $this->orgModel->getInsertID();
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

    /**
     * GET /api/v1/organizations/import-template
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

            // Set headers
            $headers = ['供應商名稱', '備註'];
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
            $sheet->getStyle('A1:B1')->applyFromArray($headerStyle);

            // Add sample data
            $sampleData = [
                ['範例供應商 A', '這是範例，可以刪除'],
                ['範例供應商 B', ''],
                ['範例供應商 C', ''],
            ];
            $sheet->fromArray($sampleData, null, 'A2');

            // Set column widths
            $sheet->getColumnDimension('A')->setWidth(40);
            $sheet->getColumnDimension('B')->setWidth(40);

            // Add instructions sheet
            $instructionSheet = $spreadsheet->createSheet(1);
            $instructionSheet->setTitle('使用說明');
            $instructions = [
                ['供應商批量匯入範本使用說明'],
                [''],
                ['欄位說明：'],
                ['1. 供應商名稱：必填，供應商的完整名稱（不可重複）'],
                ['2. 備註：選填，僅用於參考，不會匯入系統'],
                [''],
                ['重要提示：'],
                ['• 供應商名稱不可重複，重複的記錄將被跳過'],
                ['• 所有匯入的組織類型為 SUPPLIER（供應商）'],
                ['• 請勿修改表頭（第一行）'],
                ['• 範例資料可以刪除或保留（保留會被匯入）'],
                ['• 最多可匯入 1000 筆資料'],
                [''],
                ['匯入步驟：'],
                ['1. 填寫供應商資料'],
                ['2. 儲存檔案'],
                ['3. 在系統中選擇此檔案上傳'],
                ['4. 系統會顯示匯入結果'],
            ];
            $instructionSheet->fromArray($instructions, null, 'A1');
            $instructionSheet->getColumnDimension('A')->setWidth(80);
            $instructionSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $instructionSheet->getStyle('A3')->getFont()->setBold(true);
            $instructionSheet->getStyle('A8')->getFont()->setBold(true);
            $instructionSheet->getStyle('A14')->getFont()->setBold(true);

            // Set active sheet back to data sheet
            $spreadsheet->setActiveSheetIndex(0);

            // Generate file
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $filename = '供應商匯入範本_' . date('Ymd') . '.xlsx';

            // Create temp file
            $tempFile = WRITEPATH . 'uploads/' . $filename;
            $writer->save($tempFile);

            // Return file download response
            return $this->response
                ->download($tempFile, null)
                ->setFileName($filename)
                ->setContentType('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        } catch (\Exception $e) {
            log_message('error', 'Download organization template failed: ' . $e->getMessage());
            return $this->errorResponse('TEMPLATE_DOWNLOAD_ERROR', '下載範本失敗：' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/v1/organizations/import
     * Import organizations from Excel (ADMIN only)
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
                return !empty($row[0]);  // At least name must be present
            });

            if (empty($data)) {
                return $this->validationErrorResponse(['file' => 'Excel 檔案中沒有有效資料']);
            }

            if (count($data) > 1000) {
                return $this->validationErrorResponse(['file' => '一次最多只能匯入 1000 筆資料']);
            }

            $db = \Config\Database::connect();
            $db->transStart();

            $successCount = 0;
            $skipCount = 0;
            $errors = [];

            foreach ($data as $index => $row) {
                $rowNum = $index + 2; // +2 because array index starts at 0 and we removed header

                $name = trim($row[0] ?? '');

                // Validation
                if (empty($name)) {
                    $errors[] = "第 {$rowNum} 行：供應商名稱不可為空";
                    continue;
                }

                // Check if organization already exists
                $existing = $this->orgModel->where('name', $name)->first();
                if ($existing) {
                    $skipCount++;
                    $errors[] = "第 {$rowNum} 行：供應商名稱 '{$name}' 已存在，已跳過";
                    continue;
                }

                // Insert organization with type SUPPLIER
                try {
                    $this->orgModel->insert([
                        'name' => $name,
                        'type' => 'SUPPLIER',
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
            log_message('error', 'Import organizations failed: ' . $e->getMessage());
            return $this->errorResponse('IMPORT_ERROR', '匯入失敗：' . $e->getMessage(), 500);
        }
    }
}
