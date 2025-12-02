<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class File extends Entity
{
    protected $datamap = [];
    
    protected $dates = [
        'created_at',
    ];
    
    protected $casts = [
        'id' => 'string',
        'project_id' => 'string',
        'file_size' => 'integer',
    ];

    public function toApiResponse(): array
    {
        return [
            'fileId' => $this->id,
            'fileName' => $this->file_name,
            'fileSize' => $this->file_size,
            'fileUrl' => $this->file_url,
            'contentType' => $this->content_type,
            'uploadedAt' => $this->created_at?->format('c'),
        ];
    }
}
