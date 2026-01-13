<?php

namespace App\Models;

use CodeIgniter\Model;

class RmSupplierAssignmentModel extends Model
{
    protected $table = 'rm_supplier_assignments';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false; // 此表不使用軟刪除
    protected $protectFields = true;
    protected $allowedFields = [
        'project_id',
        'supplier_id',
        'supplier_name',
        'supplier_email',
        'cmrt_required',
        'emrt_required',
        'amrt_required',
        'amrt_minerals',
        'status',
        'notified_at',
        'submitted_at',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Validation
    protected $validationRules = [
        'project_id'    => 'required|is_not_unique[rm_projects.id]',
        'supplier_id'   => 'permit_empty',
        'supplier_name' => 'permit_empty',
        'status'        => 'required|in_list[not_assigned,assigned,in_progress,submitted,reviewing,completed]',
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $afterFind = ['processJsonFields'];

    /**
     * 處理 JSON 欄位與型別轉換
     */
    protected function processJsonFields(array $data)
    {
        if (!isset($data['data']) || empty($data['data'])) {
            return $data;
        }

        $fieldsToCast = ['cmrt_required', 'emrt_required', 'amrt_required'];
        $isSingleton = $data['singleton'] ?? false;

        if ($isSingleton) {
            // 單一結果：$data['data'] 是該列資料陣列
            $this->castRow($data['data'], $fieldsToCast);
        } else {
            // 多個結果：$data['data'] 是列表陣列
            foreach ($data['data'] as &$row) {
                $this->castRow($row, $fieldsToCast);
            }
        }

        return $data;
    }

    /**
     * 對單一列進行轉型與 JSON 處理
     */
    protected function castRow(&$row, $fieldsToCast)
    {
        if (!is_array($row)) return;

        // JSON 處理
        if (isset($row['amrt_minerals']) && is_string($row['amrt_minerals'])) {
            $row['amrt_minerals'] = json_decode($row['amrt_minerals'], true);
        }

        // 型別轉換：將字串 "1"/"0" 轉為布林 true/false
        foreach ($fieldsToCast as $field) {
            if (isset($row[$field])) {
                $row[$field] = (bool)$row[$field];
            }
        }
    }

    /**
     * 取得專案的所有供應商指派狀況
     */
    public function getAssignmentsByProject(int $projectId)
    {
        return $this->where('project_id', $projectId)
            ->findAll();
    }

    /**
     * 批量更新供應商範本指派
     */
    public function batchAssign(int $projectId, array $supplierIds, array $templateData)
    {
        if (empty($supplierIds)) return false;

        return $this->where('project_id', $projectId)
            ->whereIn('supplier_id', $supplierIds)
            ->set($templateData)
            ->set('status', 'assigned')
            ->update();
    }
}
