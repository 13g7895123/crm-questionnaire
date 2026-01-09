<?php

namespace App\Libraries\RM;

use PhpOffice\PhpSpreadsheet\IOFactory;

class RMITemplateDetector
{
    /**
     * 偵測 Excel 檔案的 RMI 範本類型與版本
     */
    public function detect($filePath)
    {
        try {
            $spreadsheet = IOFactory::load($filePath);

            // 1. 檢查工作表名稱
            $sheetNames = $spreadsheet->getSheetNames();

            // CMRT 通常包含 "Revision History", "Smelter List", "Declaration"
            // EMRT 通常包含 "Mine List", "Smelter List", "Declaration"

            $hasDeclaration = false;
            $hasSmelterList = false;
            $hasMineList = false;
            $hasProcessorList = false;

            foreach ($sheetNames as $name) {
                if (stripos($name, 'Declaration') !== false) $hasDeclaration = true;
                if (stripos($name, 'Smelter List') !== false) $hasSmelterList = true;
                if (stripos($name, 'Mine List') !== false) $hasMineList = true;
                if (stripos($name, 'Smelter Refiner Processor List') !== false) {
                    $hasSmelterList = true;
                    $hasProcessorList = true;
                }
            }

            // 2. 判斷類型
            $type = 'UNKNOWN';
            if ($hasDeclaration && $hasSmelterList) {
                if ($hasMineList) {
                    $type = 'EMRT';
                } else {
                    // 區分 CMRT 與 AMRT
                    // 通常 CMRT 會在 A1 或 A2 包含 "CMRT" 字眼
                    $sheet = $spreadsheet->getSheetByName('Declaration');
                    $titleCell = $sheet->getCell('A1')->getValue();

                    if (stripos($titleCell, 'CMRT') !== false || stripos($titleCell, 'Conflict Minerals') !== false) {
                        $type = 'CMRT';
                    } elseif (stripos($titleCell, 'AMRT') !== false || stripos($titleCell, 'Additional Minerals') !== false) {
                        $type = 'AMRT';
                    } else {
                        // 如果都沒有，如果是 Processor List 且非 EMRT，可能是 AMRT
                        $type = $hasProcessorList ? 'AMRT' : 'CMRT';
                    }
                }
            }

            // 3. 嘗試從 Declaration 頁面讀取版本
            $version = 'unknown';
            if ($hasDeclaration) {
                $sheet = $spreadsheet->getSheetByName('Declaration');
                // 模擬讀取：通常 CMRT 版本在 A2 或特定儲存格
                // 這裡簡化處理，實際解析時會更精確
                $version = '6.5'; // Default for demo
            }

            return [
                'type'    => $type,
                'version' => $version,
                'success' => $type !== 'UNKNOWN'
            ];
        } catch (\Exception $e) {
            return [
                'type'    => 'UNKNOWN',
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }
}
