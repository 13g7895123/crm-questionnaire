<?php

namespace App\Models;

use CodeIgniter\Model;

class RmProjectModel extends Model
{
    protected $table = 'rm_projects';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'name',
        'year',
        'type',
        'template_set_id',
        'status',
        'description',
        'review_config',
        'created_by',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    // Validation
    protected $validationRules = [
        'name'            => 'required|min_length[3]|max_length[255]',
        'year'            => 'required|integer|greater_than_equal_to[2020]|less_than_equal_to[2030]',
        'template_set_id' => 'required|is_not_unique[rm_template_sets.id]',
        'status'          => 'required|in_list[DRAFT,IN_PROGRESS,COMPLETED,ARCHIVED]',
    ];

    protected $validationMessages = [
        'template_set_id' => [
            'is_not_unique' => '選擇的範本組不存在',
        ],
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $afterFind = ['processJsonFields'];

    /**
     * 處理 JSON 欄位
     */
    protected function processJsonFields(array $data)
    {
        if (!isset($data['data'])) {
            return $data;
        }

        // 處理單一結果
        if (isset($data['data']['review_config']) && is_string($data['data']['review_config'])) {
            $data['data']['review_config'] = json_decode($data['data']['review_config'], true);
        }

        // 處理多個結果
        if (isset($data['data'][0])) {
            foreach ($data['data'] as &$row) {
                if (isset($row['review_config']) && is_string($row['review_config'])) {
                    $row['review_config'] = json_decode($row['review_config'], true);
                }
            }
        }

        return $data;
    }

    /**
     * 取得帶有範本組資訊的專案
     */
    public function getProjectWithTemplateSet(int $id)
    {
        return $this->select('rm_projects.*, rm_template_sets.name as template_set_name, rm_template_sets.cmrt_version, rm_template_sets.emrt_version, rm_template_sets.amrt_version')
            ->join('rm_template_sets', 'rm_template_sets.id = rm_projects.template_set_id')
            ->where('rm_projects.id', $id)
            ->first();
    }
}
