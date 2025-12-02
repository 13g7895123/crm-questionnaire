<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class RefreshToken extends Entity
{
    protected $datamap = [];
    
    protected $dates = [
        'expires_at',
        'created_at',
    ];
    
    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'is_revoked' => 'boolean',
    ];
}
