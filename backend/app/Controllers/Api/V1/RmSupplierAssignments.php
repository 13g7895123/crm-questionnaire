<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Models\RmSupplierAssignmentModel;
use CodeIgniter\API\ResponseTrait;

class RmSupplierAssignments extends BaseController
{
    use ResponseTrait;

    protected $model;

    public function __construct()
    {
        $this->model = new RmSupplierAssignmentModel();
    }

    /**
     * 取得專案的供應商列表
     */
    public function index($projectId = null)
    {
        try {
            $data = $this->model->where('project_id', $projectId)->findAll();
            return $this->respond([
                'success' => true,
                'data'    => $data
            ]);
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * 設定單一供應商的範本指派
     */
    public function assignTemplate($projectId = null, $id = null)
    {
        try {
            $input = $this->request->getJSON(true);

            // 如果 input 包含 templates 嵌套對象（從前端傳來的）
            $templates = $input;
            if (isset($input['templates'])) {
                $templates = $input['templates'];
            }

            // 處理 amrt_minerals JSON 編碼
            $amrtMinerals = $templates['amrt_minerals'] ?? null;
            if (is_array($amrtMinerals)) {
                $amrtMinerals = json_encode($amrtMinerals);
            }

            $dbData = [
                'cmrt_required' => (isset($templates['cmrt_required']) && $templates['cmrt_required']) ? 1 : 0,
                'emrt_required' => (isset($templates['emrt_required']) && $templates['emrt_required']) ? 1 : 0,
                'amrt_required' => (isset($templates['amrt_required']) && $templates['amrt_required']) ? 1 : 0,
                'amrt_minerals' => $amrtMinerals,
                'status'        => 'assigned'
            ];

            if (!$this->model->update($id, $dbData)) {
                $errors = $this->model->errors();
                return $this->failValidationErrors($errors);
            }

            return $this->respond([
                'success' => true,
                'message' => '範本指派成功'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'assignTemplate error: ' . $e->getMessage());
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * 批量設定供應商範本
     */
    public function batchAssign($projectId = null)
    {
        try {
            $input = $this->request->getJSON(true);
            $assignmentIds = $input['assignmentIds'] ?? [];
            $templates = $input['templates'] ?? [];

            if (empty($assignmentIds)) {
                return $this->failValidationError('未提供指派清單');
            }

            // 處理 amrt_minerals JSON 編碼
            $amrtMinerals = $templates['amrt_minerals'] ?? null;
            if (is_array($amrtMinerals)) {
                $amrtMinerals = json_encode($amrtMinerals);
            }

            $templateData = [
                'cmrt_required' => (isset($templates['cmrt_required']) && $templates['cmrt_required']) ? 1 : 0,
                'emrt_required' => (isset($templates['emrt_required']) && $templates['emrt_required']) ? 1 : 0,
                'amrt_required' => (isset($templates['amrt_required']) && $templates['amrt_required']) ? 1 : 0,
                'amrt_minerals' => $amrtMinerals,
                'status'        => 'assigned'
            ];

            // 執行批量更新
            // 注意：Model::update($ids, $data) 會執行對應 ID 的更新
            $db = \Config\Database::connect();
            $db->transStart();

            $success = $this->model->update($assignmentIds, $templateData);

            $db->transComplete();

            if (!$success || $db->transStatus() === false) {
                return $this->failServerError('批量設定失敗');
            }

            // 獲取受影響的行數 (CodeIgniter 4 update 如果資料相同，affectedRows 可能為 0，所以不適合當作失敗判斷，但適合記錄)
            $affected = $db->affectedRows();
            log_message('info', "Batch update success. Affected rows: $affected");

            return $this->respond([
                'success' => true,
                'message' => '供應商範本批量指派成功',
                'affected_rows' => $affected
            ]);
        } catch (\Exception $e) {
            log_message('error', 'batchAssign error: ' . $e->getMessage());
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * 模擬下載 Excel 範本
     */
    public function downloadTemplateAssignmentTemplate()
    {
        // 這裡實際應該生成 Excel，暫時返回成功訊號
        return $this->respond([
            'success' => true,
            'message' => '匯入範本生成成功'
        ]);
    }

    /**
     * 通知單一供應商
     */
    public function notify($projectId = null, $id = null)
    {
        try {
            $this->model->update($id, [
                'notified_at' => date('Y-m-d H:i:s'),
                'status'      => 'assigned'
            ]);

            return $this->respond([
                'success' => true,
                'message' => '通知已發送'
            ]);
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * 通知所有已指派範本的供應商
     */
    public function notifyAll($projectId = null)
    {
        try {
            $this->model->where('project_id', $projectId)
                ->where('status', 'assigned')
                ->set(['notified_at' => date('Y-m-d H:i:s')])
                ->update();

            return $this->respond([
                'success' => true,
                'message' => '已通知所有供應商'
            ]);
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }
}
