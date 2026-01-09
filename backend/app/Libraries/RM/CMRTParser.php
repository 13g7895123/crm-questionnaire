<?php

namespace App\Libraries\RM;

class CMRTParser extends BaseRMParser
{
    /**
     * 解析 CMRT 6.x 資料
     */
    public function parse()
    {
        return [
            'type'                => 'CMRT',
            'declaration'         => $this->parseDeclaration(),
            'smelterList'         => $this->parseSmelterList(),
            'mineralDeclaration' => $this->parseMineralDeclaration(),
        ];
    }

    /**
     * 解析公司基本宣告
     */
    protected function parseDeclaration()
    {
        // 假設 CMRT 6.x 的欄位位置
        return [
            'companyName'      => $this->getCellValue('Declaration', 'C7'),
            'companyCountry'   => $this->getCellValue('Declaration', 'C8'),
            'declarationScope' => $this->getCellValue('Declaration', 'C15'),
        ];
    }

    /**
     * 解析礦產回答狀況
     */
    protected function parseMineralDeclaration()
    {
        // 3TG 宣告
        return [
            'Tantalum' => ['used' => $this->getCellValue('Declaration', 'C25')],
            'Tin'      => ['used' => $this->getCellValue('Declaration', 'C26')],
            'Tungsten' => ['used' => $this->getCellValue('Declaration', 'C27')],
            'Gold'     => ['used' => $this->getCellValue('Declaration', 'C28')],
        ];
    }

    /**
     * 解析冶煉廠清單
     */
    protected function parseSmelterList()
    {
        $sheet = $this->spreadsheet->getSheetByName('Smelter List');
        if (!$sheet) return [];

        $smelters = [];
        $highestRow = $sheet->getHighestRow();

        // 通常從第 5 行左右開始是資料
        for ($row = 5; $row <= $highestRow; $row++) {
            $metal = $sheet->getCell('A' . $row)->getValue();
            $smelterId = $sheet->getCell('B' . $row)->getValue();
            $smelterName = $sheet->getCell('C' . $row)->getValue();

            if (empty($metal) || empty($smelterName)) continue;

            $smelters[] = [
                'metal_type'           => $metal,
                'smelter_id'           => $smelterId,
                'smelter_name'         => $smelterName,
                'smelter_country'      => $sheet->getCell('D' . $row)->getValue(),
                'source_of_smelter_id' => $sheet->getCell('E' . $row)->getValue(),
            ];
        }

        return $smelters;
    }
}
