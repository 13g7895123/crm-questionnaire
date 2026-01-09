<?php

namespace App\Models;

use CodeIgniter\Model;

class RmSmelterModel extends Model
{
    protected $table = 'rm_smelters';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'smelter_id',
        'smelter_name',
        'metal_type',
        'country',
        'facility_type',
        'rmi_conformant',
        'last_updated',
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * 根據 RMI ID 或名稱搜尋冶煉廠
     */
    public function searchSmelter($query, $metalType = null)
    {
        $builder = $this->like('smelter_id', $query)
            ->orLike('smelter_name', $query);

        if ($metalType) {
            $builder->where('metal_type', $metalType);
        }

        return $builder->findAll(10);
    }
}
