<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Entities\Project;

class ProjectModel extends Model
{
    protected $table = 'projects';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $returnType = Project::class;
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'id',
        'name',
        'year',
        'type',
        'template_id',
        'template_version',
        'supplier_id',
        'status',
        'current_stage',
        'submitted_at',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    protected $validationRules = [
        'name' => 'required|max_length[200]',
        'year' => 'required|integer|greater_than[1899]|less_than[2101]',
        'type' => 'required|in_list[SAQ,CONFLICT]',
        'template_id' => 'required',
        'template_version' => 'required',
        'supplier_id' => 'required',
        'status' => 'in_list[DRAFT,IN_PROGRESS,SUBMITTED,REVIEWING,APPROVED,RETURNED]',
    ];

    /**
     * Get projects with relations (for HOST)
     */
    public function getProjectsForHost(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $builder = $this->builder();
        $builder->select('projects.*, organizations.name as supplier_name')
                ->join('organizations', 'organizations.id = projects.supplier_id', 'left')
                ->where('projects.deleted_at IS NULL');

        $this->applyFilters($builder, $filters);

        $total = $builder->countAllResults(false);
        $data = $builder->limit($limit, ($page - 1) * $limit)->get()->getResult();

        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => ceil($total / $limit),
        ];
    }

    /**
     * Get projects for supplier (only assigned projects)
     */
    public function getProjectsForSupplier(string $organizationId, array $filters = [], int $page = 1, int $limit = 20): array
    {
        $builder = $this->builder();
        $builder->select('projects.*')
                ->where('projects.supplier_id', $organizationId)
                ->where('projects.deleted_at IS NULL');

        $this->applyFilters($builder, $filters);

        $total = $builder->countAllResults(false);
        $data = $builder->limit($limit, ($page - 1) * $limit)->get()->getResult();

        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => ceil($total / $limit),
        ];
    }

    /**
     * Apply common filters
     */
    protected function applyFilters($builder, array $filters): void
    {
        if (!empty($filters['type'])) {
            $builder->where('projects.type', $filters['type']);
        }

        if (!empty($filters['status'])) {
            $builder->where('projects.status', $filters['status']);
        }

        if (!empty($filters['year'])) {
            $builder->where('projects.year', $filters['year']);
        }

        if (!empty($filters['search'])) {
            $builder->like('projects.name', $filters['search']);
        }

        if (!empty($filters['sortBy'])) {
            $order = ($filters['order'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
            $builder->orderBy('projects.' . $filters['sortBy'], $order);
        } else {
            $builder->orderBy('projects.created_at', 'DESC');
        }
    }

    /**
     * Get project stats for dashboard
     */
    public function getStats(?string $type = null, ?int $year = null): array
    {
        $builder = $this->builder();
        $builder->where('deleted_at IS NULL');

        if ($type) {
            $builder->where('type', $type);
        }

        if ($year) {
            $builder->where('year', $year);
        }

        $total = $builder->countAllResults(false);

        // By status
        $byStatus = [];
        $statuses = ['DRAFT', 'IN_PROGRESS', 'SUBMITTED', 'REVIEWING', 'APPROVED', 'RETURNED'];
        foreach ($statuses as $status) {
            $byStatus[$status] = $this->builder()
                ->where('status', $status)
                ->where('deleted_at IS NULL')
                ->when($type, fn($q) => $q->where('type', $type))
                ->when($year, fn($q) => $q->where('year', $year))
                ->countAllResults();
        }

        return [
            'total' => $total,
            'byStatus' => $byStatus,
        ];
    }

    /**
     * Get pending review projects for a department
     */
    public function getPendingReviewsForDepartment(string $departmentId, array $filters = [], int $page = 1, int $limit = 20): array
    {
        $builder = $this->builder();
        $builder->select('projects.*, organizations.name as supplier_name,
                         review_stage_configs.stage_order,
                         (SELECT COUNT(*) FROM review_stage_configs WHERE review_stage_configs.project_id = projects.id) as total_stages')
                ->join('organizations', 'organizations.id = projects.supplier_id', 'left')
                ->join('review_stage_configs', 'review_stage_configs.project_id = projects.id AND review_stage_configs.stage_order = projects.current_stage', 'inner')
                ->where('projects.status', 'REVIEWING')
                ->where('review_stage_configs.department_id', $departmentId)
                ->where('projects.deleted_at IS NULL');

        if (!empty($filters['type'])) {
            $builder->where('projects.type', $filters['type']);
        }

        $sortBy = $filters['sortBy'] ?? 'submitted_at';
        $order = ($filters['order'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
        $builder->orderBy('projects.' . $sortBy, $order);

        $total = $builder->countAllResults(false);
        $data = $builder->limit($limit, ($page - 1) * $limit)->get()->getResult();

        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => ceil($total / $limit),
        ];
    }
}
