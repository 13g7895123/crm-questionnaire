<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Entities\ProjectSupplier;

class ProjectSupplierModel extends Model
{
    protected $table = 'project_suppliers';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = ProjectSupplier::class;
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'project_id',
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
        'project_id' => 'required|integer',
        'supplier_id' => 'required|integer',
        'status' => 'in_list[DRAFT,IN_PROGRESS,SUBMITTED,REVIEWING,APPROVED,RETURNED]',
    ];

    /**
     * 取得專案的所有供應商（含供應商資訊）
     */
    public function getSuppliersForProject(int $projectId): array
    {
        return $this->builder()
            ->select('project_suppliers.*, organizations.name as supplier_name')
            ->join('organizations', 'organizations.id = project_suppliers.supplier_id', 'left')
            ->where('project_suppliers.project_id', $projectId)
            ->where('project_suppliers.deleted_at IS NULL')
            ->get()
            ->getResult();
    }

    /**
     * 取得供應商的所有專案（含專案資訊）
     */
    public function getProjectsForSupplier(int $supplierId, array $filters = [], int $page = 1, int $limit = 20): array
    {
        $builder = $this->builder();
        $builder->select('project_suppliers.*, projects.name, projects.year, projects.type, 
                         projects.template_id, projects.template_version')
                ->join('projects', 'projects.id = project_suppliers.project_id', 'left')
                ->where('project_suppliers.supplier_id', $supplierId)
                ->where('project_suppliers.deleted_at IS NULL')
                ->where('projects.deleted_at IS NULL');

        // Apply filters
        if (!empty($filters['type'])) {
            $builder->where('projects.type', $filters['type']);
        }

        if (!empty($filters['status'])) {
            $builder->where('project_suppliers.status', $filters['status']);
        }

        if (!empty($filters['year'])) {
            $builder->where('projects.year', $filters['year']);
        }

        if (!empty($filters['search'])) {
            $builder->like('projects.name', $filters['search']);
        }

        $sortBy = $filters['sortBy'] ?? 'created_at';
        $order = ($filters['order'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
        $builder->orderBy('project_suppliers.' . $sortBy, $order);

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
     * 根據專案ID和供應商ID取得記錄
     */
    public function findByProjectAndSupplier(int $projectId, int $supplierId): ?ProjectSupplier
    {
        return $this->where('project_id', $projectId)
                    ->where('supplier_id', $supplierId)
                    ->where('deleted_at IS NULL')
                    ->first();
    }

    /**
     * 為專案新增多個供應商
     */
    public function addSuppliersToProject(int $projectId, array $supplierIds): array
    {
        $createdIds = [];
        foreach ($supplierIds as $supplierId) {
            $this->insert([
                'project_id' => $projectId,
                'supplier_id' => $supplierId,
                'status' => 'IN_PROGRESS',
                'current_stage' => 0,
            ]);
            $createdIds[] = $this->getInsertID();
        }
        return $createdIds;
    }

    /**
     * 取得待審核的專案供應商（依部門）
     */
    public function getPendingReviewsForDepartment(int $departmentId, array $filters = [], int $page = 1, int $limit = 20): array
    {
        $builder = $this->builder();
        $builder->select('project_suppliers.*, projects.name, projects.year, projects.type,
                         projects.template_id, projects.template_version,
                         organizations.name as supplier_name,
                         review_stage_configs.stage_order,
                         (SELECT COUNT(*) FROM review_stage_configs rsc WHERE rsc.project_id = projects.id) as total_stages')
                ->join('projects', 'projects.id = project_suppliers.project_id', 'left')
                ->join('organizations', 'organizations.id = project_suppliers.supplier_id', 'left')
                ->join('review_stage_configs', 'review_stage_configs.project_id = projects.id AND review_stage_configs.stage_order = project_suppliers.current_stage', 'inner')
                ->where('project_suppliers.status', 'REVIEWING')
                ->where('review_stage_configs.department_id', $departmentId)
                ->where('project_suppliers.deleted_at IS NULL')
                ->where('projects.deleted_at IS NULL');

        if (!empty($filters['type'])) {
            $builder->where('projects.type', $filters['type']);
        }

        $sortBy = $filters['sortBy'] ?? 'submitted_at';
        $order = ($filters['order'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
        $builder->orderBy('project_suppliers.' . $sortBy, $order);

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
     * 取得專案統計（依狀態）
     */
    public function getStatsByProject(int $projectId): array
    {
        $statuses = ['DRAFT', 'IN_PROGRESS', 'SUBMITTED', 'REVIEWING', 'APPROVED', 'RETURNED'];
        $byStatus = [];

        foreach ($statuses as $status) {
            $byStatus[$status] = $this->where('project_id', $projectId)
                                      ->where('status', $status)
                                      ->where('deleted_at IS NULL')
                                      ->countAllResults();
        }

        $total = $this->where('project_id', $projectId)
                      ->where('deleted_at IS NULL')
                      ->countAllResults();

        return [
            'total' => $total,
            'byStatus' => $byStatus,
        ];
    }
}
