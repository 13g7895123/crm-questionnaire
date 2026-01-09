<?php

namespace App\Models;

use CodeIgniter\Model;

class RmAnswerMineModel extends Model
{
    protected $table = 'rm_answer_mines';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'answer_id',
        'metal_type',
        'mine_name',
        'mine_country',
        'mine_location',
    ];

    protected $useTimestamps = false;
    protected $createdField = 'created_at';
}
