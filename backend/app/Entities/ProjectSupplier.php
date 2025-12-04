<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class ProjectSupplier extends Entity
{
    protected $datamap = [];
    
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'submitted_at',
    ];
    
    protected $casts = [
        'id' => 'integer',
        'project_id' => 'integer',
        'supplier_id' => 'integer',
        'current_stage' => 'integer',
    ];

    public function toApiResponse(): array
    {
        return [
            'id' => $this->id,
            'projectId' => $this->project_id,
            'supplierId' => $this->supplier_id,
            'status' => $this->status,
            'currentStage' => $this->current_stage,
            'submittedAt' => $this->submitted_at?->format('c'),
            'createdAt' => $this->created_at?->format('c'),
            'updatedAt' => $this->updated_at?->format('c'),
        ];
    }
}
