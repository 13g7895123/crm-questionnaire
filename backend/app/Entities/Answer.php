<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Answer extends Entity
{
    protected $datamap = [];
    
    protected $dates = [
        'created_at',
        'updated_at',
    ];
    
    protected $casts = [
        'id' => 'string',
        'project_id' => 'string',
        'value' => 'json',
    ];

    public function toApiResponse(): array
    {
        return [
            'questionId' => $this->question_id,
            'value' => $this->value,
        ];
    }
}
