<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Models\RmSupplierAssignmentModel;
use App\Models\RmReviewLogModel;
use App\Models\RmAnswerModel;
use CodeIgniter\API\ResponseTrait;

class RmReviews extends BaseController
{
    use ResponseTrait;

    protected $assignmentModel;
    protected $reviewLogModel;

    public function __construct()
    {
        $this->assignmentModel = new RmSupplierAssignmentModel();
        $this->reviewLogModel = new RmReviewLogModel();
    }

    /**
     * 取得待審核的指派清單
     */
    public function pending()
    {
        try {
            $data = $this->assignmentModel->select('rm_supplier_assignments.*, projects.name as project_name')
                ->join('projects', 'projects.id = rm_supplier_assignments.project_id')
                ->where('rm_supplier_assignments.status', 'submitted')
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
     * 審核指派 (核准或退回)
     */
    public function review($id = null)
    {
        try {
            $assignment = $this->assignmentModel->find($id);
            if (!$assignment) {
                return $this->failNotFound('找不到指派記錄');
            }

            $input = $this->request->getJSON(true);
            $action = $input['action'] ?? null; // 'approve' or 'return'
            $comment = $input['comment'] ?? '';

            if (!in_array($action, ['approve', 'return'])) {
                return $this->failValidationError('審核動作無效');
            }

            $newStatus = ($action === 'approve') ? 'completed' : 'in_progress';

            $db = \Config\Database::connect();
            $db->transStart();

            // 更新指派狀態
            $this->assignmentModel->update($id, [
                'status' => $newStatus
            ]);

            // 記錄審核日誌
            $this->reviewLogModel->insert([
                'assignment_id' => $id,
                'reviewer_id'   => $this->request->user['id'] ?? 1, // 預設 1 或從 JWT 取得
                'action'        => ($action === 'approve') ? 'Approved' : 'Returned',
                'comment'       => $comment
            ]);

            // 如果是核准，也要把相關回答狀態更新
            if ($action === 'approve') {
                $answerModel = new RmAnswerModel();
                $answerModel->where('assignment_id', $id)
                    ->where('status', 'submitted')
                    ->set(['status' => 'approved'])
                    ->update();
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->failServerError('審核儲存失敗');
            }

            return $this->respond([
                'success' => true,
                'message' => ($action === 'approve') ? '核准完畢' : '已退回供應商'
            ]);
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * 取得特定指派的審核歷程
     */
    public function history($id = null)
    {
        try {
            $logs = $this->reviewLogModel->getLogsByAssignment($id);
            return $this->respond([
                'success' => true,
                'data'    => $logs
            ]);
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }
}
