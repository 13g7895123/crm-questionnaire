<?php

namespace App\Models;

use CodeIgniter\Model;

class RmAnswerSmelterModel extends Model
{
    protected $table = 'rm_answer_smelters';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'answer_id',
        'metal_type',
        'smelter_id',
        'smelter_name',
        'smelter_country',
        'source_of_smelter_id',
        'is_conformant_expected',
        'rmi_smelter_id',
    ];

    protected $useTimestamps = false; // 這張表通常是一次性匯入，使用 created_at 即可
    protected $createdField = 'created_at';

    /**
     * 取得特定回答的所有冶煉廠
     */
    public function getSmeltersByAnswer(int $answerId)
    {
        return $this->select('rm_answer_smelters.*, rm_smelters.rmi_conformant')
            ->join('rm_smelters', 'rm_smelters.id = rm_answer_smelters.rmi_smelter_id', 'left')
            ->where('answer_id', $answerId)
            ->findAll();
    }
}
