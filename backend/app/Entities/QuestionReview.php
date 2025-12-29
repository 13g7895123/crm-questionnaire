<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class QuestionReview extends Entity
{
    protected $datamap = [];

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'project_supplier_id' => 'integer',
        'approved' => 'boolean',
        'reviewer_id' => 'integer',
    ];

    public function toApiResponse(): array
    {
        return [
            'questionId' => $this->question_id,
            'approved' => (bool) $this->approved,
            'comment' => $this->comment ?? '',
            'reviewerId' => (int) $this->reviewer_id,
            'reviewerName' => $this->reviewer_name ?? null,
            'updatedAt' => $this->updated_at?->format("c"),
        ];
    }
}
