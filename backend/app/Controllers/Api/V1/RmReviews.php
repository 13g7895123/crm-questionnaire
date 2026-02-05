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
            $data = $this->assignmentModel->select('rm_supplier_assignments.*, rm_projects.name as project_name, rm_projects.id as project_id')
                ->join('rm_projects', 'rm_projects.id = rm_supplier_assignments.project_id')
                ->where('rm_supplier_assignments.status', 'submitted')
                ->orderBy('rm_supplier_assignments.submitted_at', 'DESC')
                ->findAll();

            return $this->respond([
                'success' => true,
                'data'    => $data
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Failed to fetch pending reviews: ' . $e->getMessage());
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * 審核答卷 (核准或退回)
     * 注意：此方法審核的是單個答卷(answer)，不是整個指派(assignment)
     */
    public function review($id = null)
    {
        try {
            $answerModel = new RmAnswerModel();
            $answer = $answerModel->find($id);
            if (!$answer) {
                return $this->failNotFound('找不到答卷記錄');
            }

            $input = $this->request->getJSON(true);
            $action = $input['action'] ?? null; // 'APPROVE', 'RETURN', 'COMMENT'
            $comment = $input['comment'] ?? '';
            $stage = $input['stage'] ?? 1;

            if (!in_array(strtoupper($action), ['APPROVE', 'RETURN', 'COMMENT'])) {
                return $this->failValidationError('審核動作無效');
            }

            $db = \Config\Database::connect();
            $db->transStart();

            // 記錄審核日誌
            $this->reviewLogModel->insert([
                'answer_id'   => $id,
                'reviewer_id' => $this->request->user['id'] ?? 1,
                'action'      => strtoupper($action),
                'stage'       => $stage,
                'comment'     => $comment
            ]);

            // 如果是核準，更新對應的 assignment 狀態
            if (strtoupper($action) === 'APPROVE') {
                // 檢查該 assignment 的所有 answers 是否都已核准
                $assignmentId = $answer['assignment_id'];
                $allAnswers = $answerModel->where('assignment_id', $assignmentId)->findAll();
                $approvedCount = 0;
                
                foreach ($allAnswers as $ans) {
                    // 檢查此答卷是否有 APPROVE 日誌
                    $hasApproved = $this->reviewLogModel
                        ->where('answer_id', $ans['id'])
                        ->where('action', 'APPROVE')
                        ->countAllResults() > 0;
                    
                    if ($hasApproved || $ans['id'] === $id) {
                        $approvedCount++;
                    }
                }

                // 如果所有答卷都已核准，更新 assignment 狀態為 completed
                if ($approvedCount >= count($allAnswers)) {
                    $this->assignmentModel->update($assignmentId, [
                        'status' => 'completed'
                    ]);
                }
            } elseif (strtoupper($action) === 'RETURN') {
                // 退回時，將 assignment 狀態改為 in_progress
                $this->assignmentModel->update($answer['assignment_id'], [
                    'status' => 'in_progress'
                ]);
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->failServerError('審核儲存失敗');
            }

            return $this->respond([
                'success' => true,
                'message' => match(strtoupper($action)) {
                    'APPROVE' => '核准完畢',
                    'RETURN' => '已退回供應商',
                    'COMMENT' => '評論已儲存',
                    default => '操作完成'
                }
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
