<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Entities\User;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = User::class;
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'username',
        'email',
        'password_hash',
        'phone',
        'role',
        'organization_id',
        'department_id',
        'is_active',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    protected $validationRules = [
        'username' => 'required|min_length[3]|max_length[100]|is_unique[users.username,id,{id}]',
        'email' => 'required|valid_email|is_unique[users.email,id,{id}]',
        'role' => 'required|in_list[HOST,SUPPLIER,ADMIN]',
        'organization_id' => 'required',
    ];

    protected $validationMessages = [
        'username' => [
            'is_unique' => '使用者名稱已被使用',
        ],
        'email' => [
            'is_unique' => 'Email 已被使用',
        ],
    ];

    /**
     * Find user by username or email
     */
    public function findByCredentials(string $username): ?User
    {
        return $this->groupStart()
                    ->where('username', $username)
                    ->orWhere('email', $username)
                    ->groupEnd()
                    ->where('is_active', 1)
                    ->first();
    }

    /**
     * Get users with organization and department info
     */
    public function getUsersWithRelations(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $builder = $this->builder();
        $builder->select('users.*, organizations.name as organization_name, organizations.type as organization_type, departments.name as department_name')
                ->join('organizations', 'organizations.id = users.organization_id', 'left')
                ->join('departments', 'departments.id = users.department_id', 'left')
                ->where('users.deleted_at IS NULL');

        if (!empty($filters['role'])) {
            $builder->where('users.role', $filters['role']);
        }

        if (!empty($filters['organization_id'])) {
            $builder->where('users.organization_id', $filters['organization_id']);
        }

        if (!empty($filters['department_id'])) {
            $builder->where('users.department_id', $filters['department_id']);
        }

        if (!empty($filters['search'])) {
            $builder->groupStart()
                    ->like('users.username', $filters['search'])
                    ->orLike('users.email', $filters['search'])
                    ->groupEnd();
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
}
