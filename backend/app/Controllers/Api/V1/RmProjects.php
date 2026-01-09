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
            $data['suppliers'] = $assignmentModel->where('project_id', $id)->findAll();

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
            if (isset($input['review_config']) && is_array($input['review_config'])) {
                $input['review_config'] = json_encode($input['review_config']);
            }

            // 1. 建立專案
            if (!$this->model->insert($input)) {
                return $this->failValidationErrors($this->model->errors());
            }

            $projectId = $this->model->getInsertID();

            // 2. 獲取位本組設定
            $templateSetModel = new \App\Models\TemplateSetModel();
            $templateSet = $templateSetModel->find($input['template_set_id']);

            if ($templateSet && !empty($supplierIds)) {
                $assignmentModel = new \App\Models\RmSupplierAssignmentModel();
                $orgModel = new \App\Models\OrganizationModel();
                $userModel = new \App\Models\UserModel();

                foreach ($supplierIds as $supplierId) {
                    $org = $orgModel->find($supplierId);
                    if (!$org) continue;

                    // 尋找該組織的第一個使用者作為 Email 來源
                    $user = $userModel->where('organization_id', $supplierId)->first();
                    $email = $user ? $user->email : '';

                    $assignmentModel->insert([
                        'project_id'     => $projectId,
                        'supplier_id'    => $supplierId,
                        'supplier_name'  => $org->name,
                        'supplier_email' => $email,
                        'cmrt_required'  => $templateSet['cmrt_enabled'],
                        'emrt_required'  => $templateSet['emrt_enabled'],
                        'amrt_required'  => $templateSet['amrt_enabled'],
                        'amrt_minerals'  => $templateSet['amrt_minerals'],
                        'status'         => 'not_assigned'
                    ]);
                }
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->failServerError('建立專案失敗（資料庫事務錯誤）');
            }

            return $this->respondCreated([
                'success' => true,
                'message' => '專案建立成功',
                'id'      => $projectId
            ]);
        } catch (\Exception $e) {
            $db->transRollback();
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

                        $user = $userModel->where('organization_id', $sid)->first();
                        $email = $user ? $user->email : '';

                        $assignmentModel->insert([
                            'project_id'     => $id,
                            'supplier_id'    => $sid,
                            'supplier_name'  => $org->name,
                            'supplier_email' => $email,
                            'cmrt_required'  => $templateSet['cmrt_enabled'],
                            'emrt_required'  => $templateSet['emrt_enabled'],
                            'amrt_required'  => $templateSet['amrt_enabled'],
                            'amrt_minerals'  => $templateSet['amrt_minerals'],
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
