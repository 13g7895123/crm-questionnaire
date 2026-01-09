<?php

namespace App\Models;

use CodeIgniter\Model;

class RmReviewLogModel extends Model
{
    protected $table = 'rm_review_logs';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'assignment_id',
        'reviewer_id',
        'action',
        'comment',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = ''; // 不使用 updated_at

    /**
     * 取得特定指派的審核歷程
     */
    public function getLogsByAssignment(int $assignmentId)
    {
        return $this->select('rm_review_logs.*, users.name as reviewer_name')
            ->join('users', 'users.id = rm_review_logs.reviewer_id', 'left')
            ->where('assignment_id', $assignmentId)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }
}
