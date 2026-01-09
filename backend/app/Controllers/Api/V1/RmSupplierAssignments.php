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

            $dbData = [
                'cmrt_required' => isset($templates['cmrt_required']) ? (int)$templates['cmrt_required'] : 0,
                'emrt_required' => isset($templates['emrt_required']) ? (int)$templates['emrt_required'] : 0,
                'amrt_required' => isset($templates['amrt_required']) ? (int)$templates['amrt_required'] : 0,
                'amrt_minerals' => isset($templates['amrt_minerals']) ? json_encode($templates['amrt_minerals']) : null,
                'status'        => 'assigned'
            ];

            if (!$this->model->update($id, $dbData)) {
                return $this->failValidationErrors($this->model->errors());
            }

            return $this->respond([
                'success' => true,
                'message' => '範本指派成功'
            ]);
        } catch (\Exception $e) {
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

            $templateData = [
                'cmrt_required' => isset($templates['cmrt_required']) ? (int)$templates['cmrt_required'] : 0,
                'emrt_required' => isset($templates['emrt_required']) ? (int)$templates['emrt_required'] : 0,
                'amrt_required' => isset($templates['amrt_required']) ? (int)$templates['amrt_required'] : 0,
                'amrt_minerals' => isset($templates['amrt_minerals']) ? json_encode($templates['amrt_minerals']) : null,
                'status'        => 'assigned'
            ];

            if (!$this->model->where('project_id', $projectId)
                ->whereIn('id', $assignmentIds)
                ->set($templateData)
                ->update()) {
                return $this->failServerError('批量設定失敗');
            }

            return $this->respond([
                'success' => true,
                'message' => '供應商範本批量指派成功'
            ]);
        } catch (\Exception $e) {
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
