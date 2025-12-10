<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class TemplateQuestion extends Entity
{
    protected $datamap = [];
    
    protected $dates = [
        'created_at',
        'updated_at',
    ];
    
    protected $casts = [
        'id' => 'int',
        'subsection_id' => 'int',
        'order' => 'int',
        'required' => 'boolean',
        'config' => 'json-array',
        'conditional_logic' => 'json-array',
        'table_config' => 'json-array',
    ];

    public function toApiResponse(): array
    {
        $response = [
            'id' => $this->question_id,
            'order' => $this->order,
            'text' => $this->text,
            'type' => $this->type,
            'required' => $this->required,
        ];

        if (!empty($this->config)) {
            $response['config'] = $this->config;
        }

        if (!empty($this->conditional_logic)) {
            $response['conditionalLogic'] = $this->conditional_logic;
        }

        if ($this->type === 'TABLE' && !empty($this->table_config)) {
            $response['tableConfig'] = $this->table_config;
        }

        return $response;
    }
}
