<?php

namespace App\Repositories;

use App\Models\ProjectBasicInfoModel;
use App\Entities\ProjectBasicInfo;

class ProjectBasicInfoRepository
{
    protected ProjectBasicInfoModel $model;

    public function __construct()
    {
        $this->model = new ProjectBasicInfoModel();
    }

    /**
     * Get basic info by project supplier ID
     */
    public function getByProjectSupplierId(int $projectSupplierId): ?ProjectBasicInfo
    {
        return $this->model->getByProjectSupplierId($projectSupplierId);
    }

    /**
     * Save or update basic info
     * 
     * @param int $projectSupplierId
     * @param array $data Should contain: companyName, companyAddress, employees, facilities, certifications, rbaOnlineMember, contacts
     * @return bool
     */
    public function saveBasicInfo(int $projectSupplierId, array $data): bool
    {
        // Transform API format to database format
        $dbData = [
            'company_name' => $data['companyName'],
            'company_address' => $data['companyAddress'] ?? null,
            'employee_count' => $data['employees']['total'] ?? null,
            'male_count' => $data['employees']['male'] ?? null,
            'female_count' => $data['employees']['female'] ?? null,
            'foreign_count' => $data['employees']['foreign'] ?? null,
            'facilities' => !empty($data['facilities']) ? json_encode($data['facilities']) : null,
            'certifications' => !empty($data['certifications']) ? json_encode($data['certifications']) : null,
            'rba_online_member' => isset($data['rbaOnlineMember']) ? ($data['rbaOnlineMember'] ? 1 : 0) : null,
            'contacts' => !empty($data['contacts']) ? json_encode($data['contacts']) : null,
        ];

        return $this->model->saveBasicInfo($projectSupplierId, $dbData);
    }

    /**
     * Validate basic info data structure
     */
    public function validateBasicInfoData(array $data): array
    {
        $errors = [];

        if (empty($data['companyName'])) {
            $errors['companyName'] = '公司名稱為必填';
        }

        if (isset($data['employees'])) {
            if (!is_array($data['employees'])) {
                $errors['employees'] = '員工統計格式錯誤';
            } else {
                $total = $data['employees']['total'] ?? 0;
                $male = $data['employees']['male'] ?? 0;
                $female = $data['employees']['female'] ?? 0;
                
                if ($total > 0 && ($male + $female) > $total) {
                    $errors['employees'] = '男女員工總數不能超過總員工數';
                }
            }
        }

        if (isset($data['facilities']) && !is_array($data['facilities'])) {
            $errors['facilities'] = '設施資料格式錯誤';
        }

        if (isset($data['contacts']) && !is_array($data['contacts'])) {
            $errors['contacts'] = '聯絡人資料格式錯誤';
        }

        return $errors;
    }
}
