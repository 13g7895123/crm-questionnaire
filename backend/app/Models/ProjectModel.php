<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Entities\Project;

class ProjectModel extends Model
{
    protected $table = 'projects';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = Project::class;
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'name',
        'year',
        'type',
        'template_id',
        'template_version',
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
    ];

    /**
     * Get projects with supplier stats (for HOST)
     */
    public function getProjectsForHost(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $builder = $this->builder();
        $builder->select('projects.*,
                         (SELECT COUNT(*) FROM project_suppliers ps WHERE ps.project_id = projects.id AND ps.deleted_at IS NULL) as supplier_count,
                         (SELECT COUNT(*) FROM project_suppliers ps WHERE ps.project_id = projects.id AND ps.status = "APPROVED" AND ps.deleted_at IS NULL) as approved_count')
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

        $total = $builder->countAllResults();

        return [
            'total' => $total,
        ];
    }

    /**
     * Get project with suppliers detail
     */
    public function getProjectWithSuppliers(int $projectId): ?object
    {
        $project = $this->find($projectId);
        if (!$project) {
            return null;
        }

        $supplierModel = new ProjectSupplierModel();
        $suppliers = $supplierModel->getSuppliersForProject($projectId);

        $result = (object) $project->toArray();
        $result->suppliers = $suppliers;

        return $result;
    }
}
