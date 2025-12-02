<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Entities\ReviewLog;

class ReviewLogModel extends Model
{
    protected $table = 'review_logs';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $returnType = ReviewLog::class;
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'id',
        'project_id',
        'reviewer_id',
        'stage',
        'action',
        'comment',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = false;

    protected $validationRules = [
        'project_id' => 'required',
        'reviewer_id' => 'required',
        'stage' => 'required|integer',
        'action' => 'required|in_list[APPROVE,RETURN]',
        'comment' => 'required|max_length[1000]',
    ];

    /**
     * Get review history for a project
     */
    public function getHistoryForProject(string $projectId): array
    {
        return $this->builder()
            ->select('review_logs.*, users.username as reviewer_name, departments.id as department_id, departments.name as department_name')
            ->join('users', 'users.id = review_logs.reviewer_id', 'left')
            ->join('departments', 'departments.id = users.department_id', 'left')
            ->where('review_logs.project_id', $projectId)
            ->orderBy('review_logs.created_at', 'ASC')
            ->get()
            ->getResult();
    }

    /**
     * Get review stats for a department
     */
    public function getStatsForDepartment(string $departmentId, ?string $type = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $builder = $this->builder()
            ->select('review_logs.action, COUNT(*) as count')
            ->join('users', 'users.id = review_logs.reviewer_id')
            ->join('projects', 'projects.id = review_logs.project_id')
            ->where('users.department_id', $departmentId)
            ->groupBy('review_logs.action');

        if ($type) {
            $builder->where('projects.type', $type);
        }

        if ($startDate) {
            $builder->where('review_logs.created_at >=', $startDate);
        }

        if ($endDate) {
            $builder->where('review_logs.created_at <=', $endDate);
        }

        $results = $builder->get()->getResult();

        $stats = ['APPROVE' => 0, 'RETURN' => 0];
        foreach ($results as $row) {
            $stats[$row->action] = $row->count;
        }

        return $stats;
    }

    /**
     * Create a review log entry
     */
    public function createLog(string $projectId, string $reviewerId, int $stage, string $action, string $comment): ReviewLog
    {
        $data = [
            'id' => $this->generateUuid(),
            'project_id' => $projectId,
            'reviewer_id' => $reviewerId,
            'stage' => $stage,
            'action' => $action,
            'comment' => $comment,
        ];

        $this->insert($data);

        return $this->find($data['id']);
    }

    /**
     * Generate UUID
     */
    protected function generateUuid(): string
    {
        return sprintf('%s_%s',
            'log',
            bin2hex(random_bytes(12))
        );
    }
}
