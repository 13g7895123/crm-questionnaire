<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class ProjectBasicInfo extends Entity
{
    protected $datamap = [];
    
    protected $dates = [
        'created_at',
        'updated_at',
    ];
    
    protected $casts = [
        'id' => 'int',
        'project_supplier_id' => 'int',
        'employee_count' => 'int',
        'male_count' => 'int',
        'female_count' => 'int',
        'foreign_count' => 'int',
        'facilities' => 'json-array',
        'certifications' => 'json-array',
        'rba_online_member' => 'boolean',
        'contacts' => 'json-array',
    ];

    public function toApiResponse(): array
    {
        return [
            'companyName' => $this->company_name,
            'companyAddress' => $this->company_address,
            'employees' => [
                'total' => $this->employee_count,
                'male' => $this->male_count,
                'female' => $this->female_count,
                'foreign' => $this->foreign_count,
            ],
            'facilities' => $this->facilities ?? [],
            'certifications' => $this->certifications ?? [],
            'rbaOnlineMember' => $this->rba_online_member,
            'contacts' => $this->contacts ?? [],
        ];
    }
}
