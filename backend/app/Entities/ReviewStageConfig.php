<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class ReviewStageConfig extends Entity
{
    protected $datamap = [];
    
    protected $casts = [
        'id' => 'string',
        'project_id' => 'string',
        'department_id' => 'string',
        'approver_id' => '?string',
        'stage_order' => 'integer',
    ];

    public function toApiResponse(): array
    {
        return [
            'stageOrder' => $this->stage_order,
            'departmentId' => $this->department_id,
            'approverId' => $this->approver_id,
        ];
    }
}
