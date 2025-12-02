<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class ReviewLog extends Entity
{
    protected $datamap = [];
    
    protected $dates = [
        'created_at',
    ];
    
    protected $casts = [
        'id' => 'string',
        'project_id' => 'string',
        'reviewer_id' => 'string',
        'stage' => 'integer',
    ];

    public function toApiResponse(): array
    {
        return [
            'id' => $this->id,
            'reviewerId' => $this->reviewer_id,
            'stage' => $this->stage,
            'action' => $this->action,
            'comment' => $this->comment,
            'timestamp' => $this->created_at?->format('c'),
        ];
    }
}
