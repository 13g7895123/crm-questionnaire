<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class TemplateVersion extends Entity
{
    protected $datamap = [];
    
    protected $dates = [
        'created_at',
    ];
    
    protected $casts = [
        'id' => 'string',
        'template_id' => 'string',
        'questions' => 'json-array',
    ];

    public function toApiResponse(): array
    {
        return [
            'templateId' => $this->template_id,
            'version' => $this->version,
            'questions' => $this->questions,
            'createdAt' => $this->created_at?->format('c'),
        ];
    }
}
