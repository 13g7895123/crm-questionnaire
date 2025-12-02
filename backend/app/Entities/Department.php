<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Department extends Entity
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
    ];

    public function toApiResponse(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'organizationId' => $this->organization_id,
            'createdAt' => $this->created_at?->format('c'),
            'updatedAt' => $this->updated_at?->format('c'),
        ];
    }
}
