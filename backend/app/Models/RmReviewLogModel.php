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
        'answer_id',
        'reviewer_id',
        'action',
        'stage',
        'comment',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = ''; // 不使用 updated_at

    /**
     * 取得特定答卷的審核歷程
     */
    public function getLogsByAnswer(int $answerId)
    {
        return $this->select('rm_review_logs.*, users.username as reviewer_name')
            ->join('users', 'users.id = rm_review_logs.reviewer_id', 'left')
            ->where('answer_id', $answerId)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    /**
     * 取得特定指派的所有審核歷程（通過答卷關聯）
     */
    public function getLogsByAssignment(int $assignmentId)
    {
        return $this->select('rm_review_logs.*, users.username as reviewer_name, rm_answers.template_type')
            ->join('rm_answers', 'rm_answers.id = rm_review_logs.answer_id', 'left')
            ->join('users', 'users.id = rm_review_logs.reviewer_id', 'left')
            ->where('rm_answers.assignment_id', $assignmentId)
            ->orderBy('rm_review_logs.created_at', 'DESC')
            ->findAll();
    }
}
