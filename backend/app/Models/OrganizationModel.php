<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Entities\Organization;

class OrganizationModel extends Model
{
    protected $table = 'organizations';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = Organization::class;
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'name',
        'type',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    protected $validationRules = [
        'name' => 'required|max_length[200]|is_unique[organizations.name,id,{id}]',
        'type' => 'required|in_list[HOST,SUPPLIER]',
    ];

    protected $validationMessages = [
        'name' => [
            'is_unique' => '組織名稱已存在',
        ],
    ];

    /**
     * Get organizations with counts
     */
    public function getOrganizationsWithCounts(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $builder = $this->builder();
        $builder->select('organizations.*, 
                         (SELECT COUNT(*) FROM departments WHERE departments.organization_id = organizations.id AND departments.deleted_at IS NULL) as department_count,
                         (SELECT COUNT(*) FROM users WHERE users.organization_id = organizations.id AND users.deleted_at IS NULL) as user_count')
                ->where('organizations.deleted_at IS NULL');

        if (!empty($filters['type'])) {
            $builder->where('organizations.type', $filters['type']);
        }

        if (!empty($filters['search'])) {
            $builder->like('organizations.name', $filters['search']);
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
     * Get suppliers list (SUPPLIER type organizations)
     */
    public function getSuppliers(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $filters['type'] = 'SUPPLIER';
        return $this->getOrganizationsWithCounts($filters, $page, $limit);
    }

    /**
     * Check if organization has related data
     */
    public function hasRelatedData(string $id): array
    {
        $userCount = model('UserModel')->where('organization_id', $id)->countAllResults();
        $deptCount = model('DepartmentModel')->where('organization_id', $id)->countAllResults();
        
        return [
            'userCount' => $userCount,
            'departmentCount' => $deptCount,
            'hasData' => ($userCount > 0 || $deptCount > 0),
        ];
    }
}
