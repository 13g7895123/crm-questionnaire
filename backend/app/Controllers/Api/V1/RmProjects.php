<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Models\RmProjectModel;
use CodeIgniter\API\ResponseTrait;

class RmProjects extends BaseController
{
    use ResponseTrait;

    protected $model;

    public function __construct()
    {
        $this->model = new RmProjectModel();
    }

    /**
     * 取得專案列表
     */
    public function index()
    {
        try {
            $data = $this->model->select('rm_projects.*, rm_template_sets.name as template_set_name, 
                                        (SELECT COUNT(*) FROM rm_supplier_assignments WHERE project_id = rm_projects.id) as supplierCount,
                                        (SELECT COUNT(*) FROM rm_supplier_assignments WHERE project_id = rm_projects.id AND status IN ("completed", "submitted")) as approvedCount')
                ->join('rm_template_sets', 'rm_template_sets.id = rm_projects.template_set_id')
                ->where('rm_projects.type', 'CONFLICT')
                ->orderBy('rm_projects.created_at', 'DESC')
                ->findAll();

            return $this->respond([
                'success' => true,
                'data'    => $data
            ]);
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * 取得單一專案詳情
     */
    public function show($id = null)
    {
        try {
            $data = $this->model->getProjectWithTemplateSet($id);
            if (!$data) {
                return $this->failNotFound('找不到該專案');
            }

            // 加入供應商指派資訊
            $assignmentModel = new \App\Models\RmSupplierAssignmentModel();
            $suppliers = $assignmentModel->where('project_id', $id)->findAll();
            $data['suppliers'] = $suppliers;
            $data['supplierCount'] = count($suppliers);

            return $this->respond([
                'success' => true,
                'data'    => $data
            ]);
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * 建立專案
     */
    public function create()
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $input = $this->request->getJSON(true);
            $supplierIds = $input['supplierIds'] ?? [];

            // 處理 review_config
            if (isset($input['reviewConfig']) && is_array($input['reviewConfig'])) {
                $input['review_config'] = json_encode($input['reviewConfig']);
            }

            // 1. 建立專案
            if (!$this->model->insert($input)) {
                $errors = $this->model->errors();
                log_message('error', 'Failed to create project: ' . json_encode($errors));
                return $this->failValidationErrors($errors);
            }

            $projectId = $this->model->getInsertID();

            // 2. 獲取範本組設定
            $templateSetModel = new \App\Models\TemplateSetModel();
            $templateSet = $templateSetModel->find($input['template_set_id']);

            if (!$templateSet) {
                throw new \Exception('找不到指定的範本組');
            }

            if (!empty($supplierIds)) {
                $assignmentModel = new \App\Models\RmSupplierAssignmentModel();
                $orgModel = new \App\Models\OrganizationModel();
                $userModel = new \App\Models\UserModel();

                foreach ($supplierIds as $supplierId) {
                    $org = $orgModel->find($supplierId);
                    if (!$org) {
                        log_message('warning', "Supplier not found: {$supplierId}");
                        continue;
                    }

                    // 支援 Entity 物件和陣列兩種格式
                    $orgName = is_object($org) ? ($org->name ?? '') : ($org['name'] ?? '');

                    // 尋找該組織的第一個使用者作為 Email 來源
                    $user = $userModel->where('organization_id', $supplierId)->first();
                    $email = '';
                    if ($user) {
                        // 支援 Entity 物件和陣列兩種格式
                        $email = is_object($user) ? ($user->email ?? '') : ($user['email'] ?? '');
                    }

                    // 處理 amrt_minerals JSON 編碼
                    $amrtMinerals = $templateSet['amrt_minerals'] ?? null;
                    if (is_array($amrtMinerals)) {
                        $amrtMinerals = json_encode($amrtMinerals);
                    }

                    $assignmentData = [
                        'project_id'     => $projectId,
                        'supplier_id'    => $supplierId,
                        'supplier_name'  => $orgName,
                        'supplier_email' => $email,
                        'cmrt_required'  => $templateSet['cmrt_enabled'] ?? 0,
                        'emrt_required'  => $templateSet['emrt_enabled'] ?? 0,
                        'amrt_required'  => $templateSet['amrt_enabled'] ?? 0,
                        'amrt_minerals'  => $amrtMinerals,
                        'status'         => 'not_assigned'
                    ];

                    if (!$assignmentModel->insert($assignmentData)) {
                        $errors = $assignmentModel->errors();
                        log_message('error', 'Failed to create assignment for supplier ' . $supplierId . ': ' . json_encode($errors));
                        throw new \Exception('建立供應商指派失敗: ' . json_encode($errors));
                    }
                }
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                log_message('error', 'Transaction failed for project creation');
                return $this->failServerError('建立專案失敗（資料庫事務錯誤）');
            }

            return $this->respondCreated([
                'success' => true,
                'message' => '專案建立成功',
                'data'    => ['id' => $projectId]
            ]);
        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'Exception in project creation: ' . $e->getMessage());
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * 更新專案
     */
    public function update($id = null)
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $project = $this->model->find($id);
            if (!$project) {
                return $this->failNotFound('找不到該專案');
            }

            $input = $this->request->getJSON(true);
            $supplierIds = $input['supplierIds'] ?? null;

            if (isset($input['review_config']) && is_array($input['review_config'])) {
                $input['review_config'] = json_encode($input['review_config']);
            }

            // 1. 更新專案基本資訊
            if (!$this->model->update($id, $input)) {
                return $this->failValidationErrors($this->model->errors());
            }

            // 2. 如果有提供 supplierIds，更新供應商指派
            if ($supplierIds !== null) {
                $assignmentModel = new \App\Models\RmSupplierAssignmentModel();
                $existingAssignments = $assignmentModel->where('project_id', $id)->findAll();
                $existingSupplierIds = array_column($existingAssignments, 'supplier_id');

                // 需要新增的
                $toAdd = array_diff($supplierIds, $existingSupplierIds);
                // 需要刪除的 (僅限狀態為 not_assigned 的才允許刪除，避免破壞已開始的資料)
                $toRemove = array_diff($existingSupplierIds, $supplierIds);

                $templateSetModel = new \App\Models\TemplateSetModel();
                $templateSet = $templateSetModel->find($input['template_set_id'] ?? $project['template_set_id']);

                if ($templateSet) {
                    $orgModel = new \App\Models\OrganizationModel();
                    $userModel = new \App\Models\UserModel();

                    foreach ($toAdd as $sid) {
                        $org = $orgModel->find($sid);
                        if (!$org) continue;

                        // 支援 Entity 物件和陣列兩種格式
                        $orgName = is_object($org) ? ($org->name ?? '') : ($org['name'] ?? '');

                        $user = $userModel->where('organization_id', $sid)->first();
                        $email = '';
                        if ($user) {
                            $email = is_object($user) ? ($user->email ?? '') : ($user['email'] ?? '');
                        }

                        // 處理 amrt_minerals JSON 編碼
                        $amrtMinerals = $templateSet['amrt_minerals'] ?? null;
                        if (is_array($amrtMinerals)) {
                            $amrtMinerals = json_encode($amrtMinerals);
                        }

                        $assignmentModel->insert([
                            'project_id'     => $id,
                            'supplier_id'    => $sid,
                            'supplier_name'  => $orgName,
                            'supplier_email' => $email,
                            'cmrt_required'  => $templateSet['cmrt_enabled'] ?? 0,
                            'emrt_required'  => $templateSet['emrt_enabled'] ?? 0,
                            'amrt_required'  => $templateSet['amrt_enabled'] ?? 0,
                            'amrt_minerals'  => $amrtMinerals,
                            'status'         => 'not_assigned'
                        ]);
                    }
                }

                // 刪除未開始的供應商
                if (!empty($toRemove)) {
                    $assignmentModel->where('project_id', $id)
                        ->whereIn('supplier_id', $toRemove)
                        ->where('status', 'not_assigned')
                        ->delete();
                }
            }

            $db->transComplete();

            return $this->respond([
                'success' => true,
                'message' => '專案更新成功'
            ]);
        } catch (\Exception $e) {
            $db->transRollback();
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * 刪除專案
     */
    public function delete($id = null)
    {
        try {
            if (!$this->model->find($id)) {
                return $this->failNotFound('找不到該專案');
            }

            if (!$this->model->delete($id)) {
                return $this->failServerError('刪除失敗');
            }

            return $this->respond([
                'success' => true,
                'message' => '專案已刪除'
            ]);
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * 取得專案進度追蹤
     */
    public function progress($id = null)
    {
        try {
            $assignmentModel = new \App\Models\RmSupplierAssignmentModel();
            $assignments = $assignmentModel->where('project_id', $id)->findAll();

            $total = count($assignments);
            $completedCount = 0;
            $inProgressCount = 0;
            $notStartedCount = 0;
            $notAssignedCount = 0;
            $assignedCount = 0;

            // 範本統計
            $stats = [
                'cmrt' => ['total' => 0, 'completed' => 0],
                'emrt' => ['total' => 0, 'completed' => 0],
                'amrt' => ['total' => 0, 'completed' => 0],
            ];

            $suppliers = [];

            foreach ($assignments as $a) {
                $isAssigned = ($a['cmrt_required'] || $a['emrt_required'] || $a['amrt_required']);
                if ($isAssigned) $assignedCount++;
                else $notAssignedCount++;

                // 範本累計
                if ($a['cmrt_required']) {
                    $stats['cmrt']['total']++;
                    if ($a['status'] === 'completed' || $a['status'] === 'submitted') $stats['cmrt']['completed']++;
                }
                if ($a['emrt_required']) {
                    $stats['emrt']['total']++;
                    if ($a['status'] === 'completed' || $a['status'] === 'submitted') $stats['emrt']['completed']++;
                }
                if ($a['amrt_required']) {
                    $stats['amrt']['total']++;
                    if ($a['status'] === 'completed' || $a['status'] === 'submitted') $stats['amrt']['completed']++;
                }

                // 狀態統計
                switch ($a['status']) {
                    case 'completed':
                    case 'submitted':
                    case 'approved':
                        $completedCount++;
                        $statusText = '已提交';
                        break;
                    case 'in_progress':
                    case 'reviewing':
                        $inProgressCount++;
                        $statusText = '進行中';
                        break;
                    case 'not_assigned':
                        $statusText = '-';
                        break;
                    default:
                        $notStartedCount++;
                        $statusText = '未開始';
                        break;
                }

                // 計算完成度 (簡化邏輯：如果有被指派且已完成則是 100%，否則 0%... 後續可優化為依據內部題項計算)
                $completionRate = ($a['status'] === 'completed' || $a['status'] === 'submitted' || $a['status'] === 'approved') ? 100 : 0;

                $assignedTemplates = [];
                if ($a['cmrt_required']) $assignedTemplates[] = 'CMRT';
                if ($a['emrt_required']) $assignedTemplates[] = 'EMRT';
                if ($a['amrt_required']) $assignedTemplates[] = 'AMRT';

                $suppliers[] = [
                    'supplierId'        => $a['supplier_id'],
                    'supplierName'      => $a['supplier_name'],
                    'assignedTemplates' => $assignedTemplates,
                    'status'            => $statusText,
                    'completionRate'    => $completionRate,
                    'lastUpdated'       => $a['updated_at'] ?? $a['created_at']
                ];
            }

            // 治煉廠統計 (Smelter Analytics)
            $answerModel = new \App\Models\RmAnswerModel();
            $smelterAnswerModel = new \App\Models\RmAnswerSmelterModel();

            // 取得此專案所有指派的 ID
            $assignmentIds = array_column($assignments, 'id');
            if (empty($assignmentIds)) $assignmentIds = [0];

            $projectAnswerIds = $answerModel->whereIn('assignment_id', $assignmentIds)->findColumn('id') ?: [0];

            $totalSmelters = $smelterAnswerModel->whereIn('answer_id', $projectAnswerIds)->countAllResults();

            // 透過關聯主檔檢查合規狀況
            $conformantSmelters = $smelterAnswerModel->whereIn('answer_id', $projectAnswerIds)
                ->join('rm_smelters', 'rm_smelters.id = rm_answer_smelters.rmi_smelter_id')
                ->where('rm_smelters.rmi_conformant', 1)
                ->countAllResults();

            return $this->respond([
                'success' => true,
                'data'    => [
                    'summary' => [
                        'totalSuppliers'        => $total,
                        'assignedSuppliers'     => $assignedCount,
                        'notAssignedSuppliers'  => $notAssignedCount,
                        'completedSuppliers'    => $completedCount,
                        'inProgressSuppliers'   => $inProgressCount,
                        'notStartedSuppliers'   => $notStartedCount,
                    ],
                    'smelterStats' => [
                        'total'      => $totalSmelters,
                        'conformant' => $conformantSmelters,
                        'percentage' => $totalSmelters > 0 ? round(($conformantSmelters / $totalSmelters) * 100) : 0
                    ],
                    'templateStats' => $stats,
                    'suppliers'     => $suppliers
                ]
            ]);
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * 複製專案
     */
    public function duplicate($id = null)
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $project = $this->model->find($id);
            if (!$project) {
                return $this->failNotFound('找不到該專案');
            }

            // 1. 建立新專案（名稱加上副本後綴）
            $newProjectData = [
                'name' => $project['name'] . ' - 副本',
                'year' => $project['year'],
                'type' => $project['type'],
                'template_set_id' => $project['template_set_id'],
                'review_config' => $project['review_config']
            ];

            if (!$this->model->insert($newProjectData)) {
                throw new \Exception('建立複製專案失敗');
            }

            $newProjectId = $this->model->getInsertID();

            // 2. 複製供應商指派（如果有）
            $assignmentModel = new \App\Models\RmSupplierAssignmentModel();
            $originalAssignments = $assignmentModel->where('project_id', $id)->findAll();

            foreach ($originalAssignments as $assignment) {
                // 處理 amrt_minerals JSON 編碼
                $amrtMinerals = $assignment['amrt_minerals'];
                if (is_array($amrtMinerals)) {
                    $amrtMinerals = json_encode($amrtMinerals);
                }
                
                $newAssignmentData = [
                    'project_id' => $newProjectId,
                    'supplier_id' => $assignment['supplier_id'],
                    'supplier_name' => $assignment['supplier_name'],
                    'supplier_email' => $assignment['supplier_email'],
                    'cmrt_required' => $assignment['cmrt_required'],
                    'emrt_required' => $assignment['emrt_required'],
                    'amrt_required' => $assignment['amrt_required'],
                    'amrt_minerals' => $amrtMinerals,
                    'status' => 'not_assigned' // 重置狀態
                ];

                if (!$assignmentModel->insert($newAssignmentData)) {
                    log_message('error', 'Failed to copy assignment for supplier ' . $assignment['supplier_id']);
                }
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->failServerError('複製專案失敗（資料庫事務錯誤）');
            }

            return $this->respond([
                'success' => true,
                'message' => '專案複製成功',
                'data' => ['id' => $newProjectId]
            ]);
        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'Exception in duplicate project: ' . $e->getMessage());
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * 匯出專案進度為 Excel
     */
    public function export($id = null)
    {
        try {
            $assignmentModel = new \App\Models\RmSupplierAssignmentModel();
            $assignments = $assignmentModel->where('project_id', $id)->findAll();
            $project = $this->model->find($id);

            if (!$project) {
                return $this->failNotFound('找不到該專案');
            }

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('填寫進度');

            // 設定標頭
            $headers = ['供應商名稱', 'Email', '指派範本', '目前狀態', '最後更新時間'];
            $sheet->fromArray($headers, NULL, 'A1');

            // 標頭樣式
            $sheet->getStyle('A1:E1')->getFont()->setBold(true);
            $sheet->getStyle('A1:E1')->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFEEEEEE');

            $row = 2;
            foreach ($assignments as $a) {
                $templates = [];
                if ($a['cmrt_required']) $templates[] = 'CMRT';
                if ($a['emrt_required']) $templates[] = 'EMRT';
                if ($a['amrt_required']) $templates[] = 'AMRT';

                $sheet->setCellValue('A' . $row, $a['supplier_name']);
                $sheet->setCellValue('B' . $row, $a['supplier_email']);
                $sheet->setCellValue('C' . $row, implode(', ', $templates));
                $sheet->setCellValue('D' . $row, $a['status']);
                $sheet->setCellValue('E' . $row, $a['updated_at'] ?? $a['created_at']);
                $row++;
            }

            // 自動調整欄寬
            foreach (range('A', 'E') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $fileName = 'RM_Progress_' . $id . '_' . date('YmdHis') . '.xlsx';
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

            // 清除緩衝區以避免損壞 Excel
            if (ob_get_length()) ob_end_clean();

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            header('Cache-Control: max-age=0');

            $writer->save('php://output');
            exit;
        } catch (\Exception $e) {
            return $this->failServerError('匯出失敗: ' . $e->getMessage());
        }
    }

    /**
     * 新增供應商到專案
     */
    public function addSuppliers($id = null)
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $project = $this->model->find($id);
            if (!$project) {
                return $this->failNotFound('找不到該專案');
            }

            $input = $this->request->getJSON(true);
            $supplierIds = $input['supplierIds'] ?? [];

            if (empty($supplierIds)) {
                return $this->fail('未提供供應商清單', 400);
            }

            // 獲取範本組設定
            $templateSetModel = new \App\Models\TemplateSetModel();
            $templateSet = $templateSetModel->find($project['template_set_id']);

            if (!$templateSet) {
                return $this->failNotFound('找不到範本組設定');
            }

            $assignmentModel = new \App\Models\RmSupplierAssignmentModel();
            $orgModel = new \App\Models\OrganizationModel();
            $userModel = new \App\Models\UserModel();

            // 檢查已存在的供應商
            $existingAssignments = $assignmentModel->where('project_id', $id)->findAll();
            $existingSupplierIds = array_column($existingAssignments, 'supplier_id');

            $addedCount = 0;
            $skippedCount = 0;

            foreach ($supplierIds as $sid) {
                // 跳過已存在的
                if (in_array($sid, $existingSupplierIds)) {
                    $skippedCount++;
                    continue;
                }

                $org = $orgModel->find($sid);
                if (!$org) {
                    log_message('warning', "Supplier not found: {$sid}");
                    continue;
                }

                $orgName = is_object($org) ? ($org->name ?? '') : ($org['name'] ?? '');

                $user = $userModel->where('organization_id', $sid)->first();
                $email = '';
                if ($user) {
                    $email = is_object($user) ? ($user->email ?? '') : ($user['email'] ?? '');
                }

                // 處理 amrt_minerals JSON 編碼
                $amrtMinerals = $templateSet['amrt_minerals'] ?? null;
                if (is_array($amrtMinerals)) {
                    $amrtMinerals = json_encode($amrtMinerals);
                }

                $assignmentData = [
                    'project_id'     => $id,
                    'supplier_id'    => $sid,
                    'supplier_name'  => $orgName,
                    'supplier_email' => $email,
                    'cmrt_required'  => $templateSet['cmrt_enabled'] ?? 0,
                    'emrt_required'  => $templateSet['emrt_enabled'] ?? 0,
                    'amrt_required'  => $templateSet['amrt_enabled'] ?? 0,
                    'amrt_minerals'  => $amrtMinerals,
                    'status'         => 'not_assigned'
                ];

                if ($assignmentModel->insert($assignmentData)) {
                    $addedCount++;
                } else {
                    log_message('error', 'Failed to add supplier ' . $sid . ': ' . json_encode($assignmentModel->errors()));
                }
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->failServerError('新增供應商失敗（資料庫事務錯誤）');
            }

            return $this->respond([
                'success' => true,
                'message' => "成功新增 {$addedCount} 個供應商" . ($skippedCount > 0 ? "，跳過 {$skippedCount} 個已存在的供應商" : ''),
                'data' => [
                    'added' => $addedCount,
                    'skipped' => $skippedCount
                ]
            ]);
        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'Exception in addSuppliers: ' . $e->getMessage());
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * 批量刪除供應商指派
     */
    public function batchDeleteSuppliers($id = null)
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $project = $this->model->find($id);
            if (!$project) {
                return $this->failNotFound('找不到該專案');
            }

            $input = $this->request->getJSON(true);
            $assignmentIds = $input['assignmentIds'] ?? [];

            if (empty($assignmentIds)) {
                return $this->fail('未提供要刪除的供應商指派清單', 400);
            }

            $assignmentModel = new \App\Models\RmSupplierAssignmentModel();
            
            // 刪除指定的供應商指派（只刪除屬於此專案的）
            $deletedCount = $assignmentModel
                ->where('project_id', $id)
                ->whereIn('id', $assignmentIds)
                ->delete();

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->failServerError('批量刪除失敗（資料庫事務錯誤）');
            }

            return $this->respond([
                'success' => true,
                'message' => "成功刪除 {$deletedCount} 個供應商指派",
                'data' => [
                    'deleted' => $deletedCount
                ]
            ]);
        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'Exception in batchDeleteSuppliers: ' . $e->getMessage());
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * 匯出專案彙整報表 (冶煉廠清單彙整)
     */
    public function consolidatedReport($id = null)
    {
        try {
            $project = $this->model->find($id);
            if (!$project) return $this->failNotFound('找不到該專案');

            $assignmentModel = new \App\Models\RmSupplierAssignmentModel();
            $assignmentIds = $assignmentModel->where('project_id', $id)->findColumn('id') ?: [0];

            $answerModel = new \App\Models\RmAnswerModel();
            $answerIds = $answerModel->whereIn('assignment_id', $assignmentIds)
                ->whereIn('status', ['submitted', 'approved', 'completed'])
                ->findColumn('id') ?: [0];

            $smelterAnswerModel = new \App\Models\RmAnswerSmelterModel();

            // 抓取所有冶煉廠並進行彙整
            $rawSmelters = $smelterAnswerModel->whereIn('answer_id', $answerIds)->findAll();

            $consolidated = [];
            foreach ($rawSmelters as $s) {
                // 以 ID 或 (名稱+國家+金屬) 為 Key
                $key = $s['smelter_id']
                    ? $s['smelter_id']
                    : ($s['smelter_name'] . '|' . $s['smelter_country'] . '|' . $s['metal_type']);

                if (!isset($consolidated[$key])) {
                    $consolidated[$key] = [
                        'metal_type'      => $s['metal_type'],
                        'smelter_id'      => $s['smelter_id'],
                        'smelter_name'    => $s['smelter_name'],
                        'smelter_country' => $s['smelter_country'],
                        'supplier_count'  => 0,
                        'suppliers'       => []
                    ];
                }

                // 反向找出供應商名稱
                $ans = $answerModel->find($s['answer_id']);
                if ($ans) {
                    $assign = $assignmentModel->find($ans['assignment_id']);
                    if ($assign && !in_array($assign['supplier_name'], $consolidated[$key]['suppliers'])) {
                        $consolidated[$key]['suppliers'][] = $assign['supplier_name'];
                        $consolidated[$key]['supplier_count']++;
                    }
                }
            }

            // 建立 Excel
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('冶煉廠彙總');

            // 標頭
            $headers = ['金屬種類', '冶煉廠 ID', '冶煉廠名稱', '國家', '引用供應商數', '供應商明細'];
            $sheet->fromArray($headers, NULL, 'A1');

            // 資料行
            $row = 2;
            foreach ($consolidated as $item) {
                $sheet->setCellValue('A' . $row, $item['metal_type']);
                $sheet->setCellValue('B' . $row, $item['smelter_id']);
                $sheet->setCellValue('C' . $row, $item['smelter_name']);
                $sheet->setCellValue('D' . $row, $item['smelter_country']);
                $sheet->setCellValue('E' . $row, $item['supplier_count']);
                $sheet->setCellValue('F' . $row, implode(', ', $item['suppliers']));
                $row++;
            }

            // 樣式
            $sheet->getStyle('A1:F1')->getFont()->setBold(true);
            $sheet->getStyle('A1:F1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9EAD3');
            foreach (range('A', 'F') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $fileName = 'Consolidated_Smelter_Report_' . $id . '_' . date('Ymd') . '.xlsx';
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

            if (ob_get_length()) ob_end_clean();
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            $writer->save('php://output');
            exit;
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }
}
