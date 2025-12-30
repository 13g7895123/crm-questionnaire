<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Entities\ReviewLog;

class ReviewLogModel extends Model
{
    protected $table = 'review_logs';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = ReviewLog::class;
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'project_supplier_id',
        'reviewer_id',
        'stage',
        'action',
        'comment',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = '';

    protected $validationRules = [
        'project_supplier_id' => 'required',
        'reviewer_id' => 'required',
        'stage' => 'required|integer',
        'action' => 'required|in_list[APPROVE,RETURN]',
        'comment' => 'required|max_length[1000]',
    ];

    /**
     * Get review history for a project supplier
     */
    public function getHistoryForProjectSupplier(int $projectSupplierId): array
    {
        return $this->builder()
            ->select('review_logs.*, users.username as reviewer_name, departments.id as department_id, departments.name as department_name')
            ->join('users', 'users.id = review_logs.reviewer_id', 'left')
            ->join('departments', 'departments.id = users.department_id', 'left')
            ->where('review_logs.project_supplier_id', $projectSupplierId)
            ->orderBy('review_logs.created_at', 'ASC')
            ->get()
            ->getResult();
    }

    /**
     * Get review stats for a department
     */
    public function getStatsForDepartment(int $departmentId, ?string $type = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $builder = $this->builder()
            ->select('review_logs.action, COUNT(*) as count')
            ->join('users', 'users.id = review_logs.reviewer_id')
            ->join('project_suppliers', 'project_suppliers.id = review_logs.project_supplier_id')
            ->join('projects', 'projects.id = project_suppliers.project_id')
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
    public function createLog(int $projectSupplierId, int $reviewerId, int $stage, string $action, string $comment): ReviewLog
    {
        $data = [
            'project_supplier_id' => $projectSupplierId,
            'reviewer_id' => $reviewerId,
            'stage' => $stage,
            'action' => $action,
            'comment' => $comment,
        ];

        $id = $this->insert($data);

        return $this->find($id);
    }

    /**
     * @deprecated Use getHistoryForProjectSupplier instead
     */
    public function getHistoryForProject(int $projectId): array
    {
        return $this->getHistoryForProjectSupplier($projectId);
    }
}
