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
        'id' => 'integer',
        'template_id' => 'integer',
    ];

    public function toApiResponse(): array
    {
        return [
            'templateId' => $this->template_id,
            'version' => $this->version,
            'createdAt' => $this->created_at?->format('c'),
        ];
    }
}
