<?php

namespace App\Models;

use CodeIgniter\Model;

class TemplateSetModel extends Model
{
    protected $table = 'rm_template_sets';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $protectFields = true;
    protected $allowedFields = [
        'name',
        'year',
        'description',
        'cmrt_enabled',
        'cmrt_version',
        'emrt_enabled',
        'emrt_version',
        'amrt_enabled',
        'amrt_version',
        'amrt_minerals',
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
        'name' => 'required|min_length[3]|max_length[255]',
        'year' => 'required|integer|greater_than_equal_to[2020]|less_than_equal_to[2030]',
    ];

    protected $validationMessages = [
        'name' => [
            'required' => '範本組名稱為必填',
            'min_length' => '範本組名稱至少需要 3 個字元',
        ],
        'year' => [
            'required' => '年份為必填',
            'integer' => '年份必須為整數',
        ],
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = [];
    protected $afterInsert = [];
    protected $beforeUpdate = [];
    protected $afterUpdate = [];
    protected $beforeFind = [];
    protected $afterFind = ['processJsonFields'];
    protected $beforeDelete = [];
    protected $afterDelete = [];

    /**
     * 處理 JSON 欄位
     */
    protected function processJsonFields(array $data)
    {
        if (!isset($data['data'])) {
            return $data;
        }

        if (isset($data['data']['amrt_minerals']) && is_string($data['data']['amrt_minerals'])) {
            $data['data']['amrt_minerals'] = json_decode($data['data']['amrt_minerals'], true);
        }

        return $data;
    }

    /**
     * 取得範本組的摘要資訊
     */
    public function getTemplateSummary($templateSet): string
    {
        $templates = [];

        if (!empty($templateSet['cmrt_enabled'])) {
            $templates[] = 'CMRT ' . $templateSet['cmrt_version'];
        }

        if (!empty($templateSet['emrt_enabled'])) {
            $templates[] = 'EMRT ' . $templateSet['emrt_version'];
        }

        if (!empty($templateSet['amrt_enabled'])) {
            $mineralCount = is_array($templateSet['amrt_minerals'])
                ? count($templateSet['amrt_minerals'])
                : 0;
            $templates[] = 'AMRT ' . $templateSet['amrt_version'] . " ({$mineralCount} 種礦產)";
        }

        return !empty($templates) ? implode(' + ', $templates) : '未設定任何範本';
    }

    /**
     * 取得已啟用的範本類型
     */
    public function getEnabledTemplateTypes($templateSet): array
    {
        $types = [];

        if (!empty($templateSet['cmrt_enabled'])) {
            $types[] = 'CMRT';
        }

        if (!empty($templateSet['emrt_enabled'])) {
            $types[] = 'EMRT';
        }

        if (!empty($templateSet['amrt_enabled'])) {
            $types[] = 'AMRT';
        }

        return $types;
    }
}
