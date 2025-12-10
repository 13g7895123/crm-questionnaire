<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Entities\ProjectBasicInfo;

class ProjectBasicInfoModel extends Model
{
    protected $table = 'project_basic_info';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = ProjectBasicInfo::class;
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'project_supplier_id',
        'company_name',
        'company_address',
        'employee_count',
        'male_count',
        'female_count',
        'foreign_count',
        'facilities',
        'certifications',
        'rba_online_member',
        'contacts',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'project_supplier_id' => 'required|is_natural_no_zero',
        'company_name' => 'required|max_length[200]',
    ];

    /**
     * Get basic info by project supplier ID
     */
    public function getByProjectSupplierId(int $projectSupplierId): ?ProjectBasicInfo
    {
        return $this->where('project_supplier_id', $projectSupplierId)->first();
    }

    /**
     * Save or update basic info
     */
    public function saveBasicInfo(int $projectSupplierId, array $data): bool
    {
        $existing = $this->getByProjectSupplierId($projectSupplierId);
        
        $data['project_supplier_id'] = $projectSupplierId;
        
        if ($existing) {
            return $this->update($existing->id, $data);
        }
        
        return $this->insert($data) !== false;
    }
}
