<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;

class RmTemplates extends BaseController
{
    use ResponseTrait;

    /**
     * 下載 CMRT/EMRT/AMRT 官方範本
     */
    public function download($type = null)
    {
        $type = strtoupper($type);
        
        // 定義範本檔案路徑
        $templateFiles = [
            'CMRT' => WRITEPATH . 'uploads/templates/CMRT-Template.xlsx',
            'EMRT' => WRITEPATH . 'uploads/templates/EMRT-Template.xlsx',
            'AMRT' => WRITEPATH . 'uploads/templates/AMRT-Template.xlsx'
        ];

        if (!isset($templateFiles[$type])) {
            return $this->failNotFound('找不到指定的範本類型');
        }

        $filePath = $templateFiles[$type];

        if (!file_exists($filePath)) {
            return $this->failNotFound('範本檔案不存在，請聯繫系統管理員');
        }

        // 設定下載檔名
        $downloadName = "{$type}_Template_" . date('Ymd') . ".xlsx";

        // 下載檔案
        return $this->response->download($filePath, null)
            ->setFileName($downloadName);
    }
}
