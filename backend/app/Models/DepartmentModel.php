<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Entities\Department;

class DepartmentModel extends Model
{
    protected $table = 'departments';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = Department::class;
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'name',
        'organization_id',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    protected $validationRules = [
        'name' => 'required|max_length[100]',
        'organization_id' => 'required',
    ];

    /**
     * Get departments with organization info
     */
    public function getDepartmentsWithRelations(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $builder = $this->builder();
        $builder->select('departments.*, organizations.name as organization_name, organizations.type as organization_type,
                         (SELECT COUNT(*) FROM users WHERE users.department_id = departments.id AND users.deleted_at IS NULL) as member_count')
                ->join('organizations', 'organizations.id = departments.organization_id', 'left')
                ->where('departments.deleted_at IS NULL');

        if (!empty($filters['organization_id'])) {
            $builder->where('departments.organization_id', $filters['organization_id']);
        }

        if (!empty($filters['search'])) {
            $builder->like('departments.name', $filters['search']);
        }

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
     * Check if department name exists in organization
     */
    public function existsInOrganization(string $name, string $organizationId, ?string $excludeId = null): bool
    {
        $builder = $this->where('name', $name)
                       ->where('organization_id', $organizationId)
                       ->where('deleted_at IS NULL');
        
        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }

        return $builder->countAllResults() > 0;
    }

    /**
     * Check if department has related data
     */
    public function hasRelatedData(string $id): array
    {
        $userCount = model('UserModel')->where('department_id', $id)->countAllResults();
        $projectCount = $this->db->table('review_stage_configs')
                                 ->where('department_id', $id)
                                 ->countAllResults();
        
        return [
            'userCount' => $userCount,
            'projectCount' => $projectCount,
            'hasData' => ($userCount > 0 || $projectCount > 0),
        ];
    }
}
