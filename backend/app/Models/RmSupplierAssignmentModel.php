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
        'supplier_id'   => 'required',
        'supplier_name' => 'required',
        'status'        => 'required|in_list[not_assigned,assigned,in_progress,submitted,reviewing,completed]',
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
        if (isset($data['data']['amrt_minerals']) && is_string($data['data']['amrt_minerals'])) {
            $data['data']['amrt_minerals'] = json_decode($data['data']['amrt_minerals'], true);
        }

        // 處理多個結果
        if (isset($data['data'][0])) {
            foreach ($data['data'] as &$row) {
                if (isset($row['amrt_minerals']) && is_string($row['amrt_minerals'])) {
                    $row['amrt_minerals'] = json_decode($row['amrt_minerals'], true);
                }
            }
        }

        return $data;
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
