<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class TemplateSubsection extends Entity
{
    protected $datamap = [];
    
    protected $dates = [
        'created_at',
        'updated_at',
    ];
    
    protected $casts = [
        'id' => 'int',
        'section_id' => 'int',
        'order' => 'int',
    ];

    public function toApiResponse(): array
    {
        return [
            'id' => $this->subsection_id,
            'order' => $this->order,
            'title' => $this->title,
            'description' => $this->description,
            'questions' => [], // Will be populated by controller/repository
        ];
    }
}
