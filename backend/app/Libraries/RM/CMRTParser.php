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
     * 解析公司基本宣告 (Section A & C)
     */
    protected function parseDeclaration()
    {
        // CMRT 6.x 的完整欄位位置
        return [
            // Section A: Company Information
            'companyName'              => $this->getCellValue('Declaration', 'C7'),
            'companyCountry'           => $this->getCellValue('Declaration', 'C8'),
            'companyAddress'           => $this->getCellValue('Declaration', 'C9'),
            'companyContactName'       => $this->getCellValue('Declaration', 'C10'),
            'companyContactEmail'      => $this->getCellValue('Declaration', 'C11'),
            'companyContactPhone'      => $this->getCellValue('Declaration', 'C12'),
            'authorizer'               => $this->getCellValue('Declaration', 'C13'),
            'effectiveDate'            => $this->getCellValue('Declaration', 'C14'),
            
            // Section C: Product Level Information
            'declarationScope'         => $this->getCellValue('Declaration', 'C15'),
            'productsInclude3TG'       => $this->getCellValue('Declaration', 'C16'),
            'percentageRecycled'       => $this->getCellValue('Declaration', 'C17'),
        ];
    }

    /**
     * 解析礦產回答狀況 (包含政策與盡職調查資訊)
     */
    protected function parseMineralDeclaration()
    {
        return [
            // Section B: Company Level Information (Policy)
            'policy' => [
                'hasConflictMineralsPolicy'    => $this->getCellValue('Declaration', 'C19'),
                'policyPubliclyAvailable'      => $this->getCellValue('Declaration', 'C20'),
                'requiresCMRTFromSuppliers'    => $this->getCellValue('Declaration', 'C21'),
                'hasSupplierManagemSystem'  => $this->getCellValue('Declaration', 'C22'),
                'conductsDueDiligence'         => $this->getCellValue('Declaration', 'C23'),
                'requiresConflictFreeSource'   => $this->getCellValue('Declaration', 'C24'),
            ],
            
            // Section C: Mineral Declaration (3TG)
            'minerals' => [
                'Tantalum' => [
                    'used'            => $this->getCellValue('Declaration', 'C25'),
                    'countries'       => $this->getCellValue('Declaration', 'C26'),
                    'smelterCount'    => $this->getCellValue('Declaration', 'C27'),
                ],
                'Tin' => [
                    'used'            => $this->getCellValue('Declaration', 'C28'),
                    'countries'       => $this->getCellValue('Declaration', 'C29'),
                    'smelterCount'    => $this->getCellValue('Declaration', 'C30'),
                ],
                'Tungsten' => [
                    'used'            => $this->getCellValue('Declaration', 'C31'),
                    'countries'       => $this->getCellValue('Declaration', 'C32'),
                    'smelterCount'    => $this->getCellValue('Declaration', 'C33'),
                ],
                'Gold' => [
                    'used'            => $this->getCellValue('Declaration', 'C34'),
                    'countries'       => $this->getCellValue('Declaration', 'C35'),
                    'smelterCount'    => $this->getCellValue('Declaration', 'C36'),
                ],
            ],
            
            // Additional Information
            'comments' => $this->getCellValue('Declaration', 'C40'),
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
            $metal = $sheet->getCell('A' . $row)->getCalculatedValue();
            $smelterId = $sheet->getCell('B' . $row)->getCalculatedValue();
            $smelterName = $sheet->getCell('C' . $row)->getCalculatedValue();

            if (empty($metal) || empty($smelterName)) continue;

            $smelters[] = [
                'metal_type'           => $metal,
                'smelter_id'           => $smelterId,
                'smelter_name'         => $smelterName,
                'smelter_country'      => $sheet->getCell('D' . $row)->getCalculatedValue(),
                'source_of_smelter_id' => $sheet->getCell('E' . $row)->getCalculatedValue(),
            ];
        }

        return $smelters;
    }
}
