<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class TemplateSection extends Entity
{
    protected $datamap = [];
    
    protected $dates = [
        'created_at',
        'updated_at',
    ];
    
    protected $casts = [
        'id' => 'int',
        'template_id' => 'int',
        'order' => 'int',
    ];

    public function toApiResponse(): array
    {
        return [
            'id' => $this->section_id,
            'order' => $this->order,
            'title' => $this->title,
            'description' => $this->description,
            'subsections' => [], // Will be populated by controller/repository
        ];
    }
}
