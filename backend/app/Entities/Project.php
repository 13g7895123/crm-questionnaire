<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Project extends Entity
{
    protected $datamap = [];
    
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'submitted_at',
    ];
    
    protected $casts = [
        'id' => 'string',
        'template_id' => 'string',
        'supplier_id' => 'string',
        'year' => 'integer',
        'current_stage' => 'integer',
    ];

    public function toApiResponse(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'year' => $this->year,
            'type' => $this->type,
            'templateId' => $this->template_id,
            'templateVersion' => $this->template_version,
            'supplierId' => $this->supplier_id,
            'status' => $this->status,
            'currentStage' => $this->current_stage,
            'submittedAt' => $this->submitted_at?->format('c'),
            'createdAt' => $this->created_at?->format('c'),
            'updatedAt' => $this->updated_at?->format('c'),
        ];
    }
}
