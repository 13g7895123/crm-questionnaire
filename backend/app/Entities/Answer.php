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
        // Note: 'value' is handled manually via getValue() to preserve type
    ];

    /**
     * Custom getter for value field to properly decode JSON
     * This handles booleans, strings, arrays, and objects correctly
     */
    public function getValue()
    {
        $raw = $this->attributes['value'] ?? null;

        if ($raw === null) {
            return null;
        }

        // json_decode without the associative flag preserves types correctly
        $decoded = json_decode($raw);

        // If json_decode failed, return the raw value
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $raw;
        }

        return $decoded;
    }

    public function toApiResponse(): array
    {
        return [
            'questionId' => $this->question_id,
            'value' => $this->getValue(),
        ];
    }
}
