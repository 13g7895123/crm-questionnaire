<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class User extends Entity
{
    protected $datamap = [];
    
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];
    
    protected $casts = [
        'id' => 'string',
        'organization_id' => 'string',
        'department_id' => '?string',
        'is_active' => 'boolean',
    ];

    /**
     * Set password with hashing
     */
    public function setPassword(string $password): self
    {
        $this->attributes['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        return $this;
    }

    /**
     * Verify password
     */
    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->attributes['password_hash'] ?? '');
    }

    /**
     * Get full user data for API response (without sensitive data)
     */
    public function toApiResponse(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'organizationId' => $this->organization_id,
            'departmentId' => $this->department_id,
            'createdAt' => $this->created_at?->format('c'),
            'updatedAt' => $this->updated_at?->format('c'),
        ];
    }
}
