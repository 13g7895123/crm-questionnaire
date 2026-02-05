<?php

namespace App\Libraries\RM;

class EMRTParser extends BaseRMParser
{
    /**
     * 解析 EMRT 資料
     */
    public function parse()
    {
        return [
            'type'                => 'EMRT',
            'declaration'         => $this->parseDeclaration(),
            'smelterList'         => $this->parseSmelterList(),
            'mineList'            => $this->parseMineList(),
            'mineralDeclaration' => $this->parseMineralDeclaration(),
        ];
    }

    /**
     * 解析公司基本宣告 (完整版)
     */
    protected function parseDeclaration()
    {
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
            'declarationScope'         => $this->getCellValue('Declaration', 'C15'),
            'productsIncludeMinerals'  => $this->getCellValue('Declaration', 'C16'),
        ];
    }

    /**
     * 解析礦產回答狀況 (EMRT 涵蓋 Cobalt, Mica, Copper, Graphite, Lithium, Nickel)
     */
    protected function parseMineralDeclaration()
    {
        return [
            // Policy Information
            'policy' => [
                'hasExtendedMineralsPolicy'    => $this->getCellValue('Declaration', 'C19'),
                'policyPubliclyAvailable'      => $this->getCellValue('Declaration', 'C20'),
                'requiresEMRTFromSuppliers'    => $this->getCellValue('Declaration', 'C21'),
                'hasSupplierManagementSystem'  => $this->getCellValue('Declaration', 'C22'),
                'conductsDueDiligence'         => $this->getCellValue('Declaration', 'C23'),
            ],
            
            // Mineral Declaration
            'minerals' => [
                'Cobalt' => [
                    'used'         => $this->getCellValue('Declaration', 'C25'),
                    'countries'    => $this->getCellValue('Declaration', 'C26'),
                    'smelterCount' => $this->getCellValue('Declaration', 'C27'),
                    'mineCount'    => $this->getCellValue('Declaration', 'C28'),
                ],
                'Mica' => [
                    'used'         => $this->getCellValue('Declaration', 'C29'),
                    'countries'    => $this->getCellValue('Declaration', 'C30'),
                    'mineCount'    => $this->getCellValue('Declaration', 'C31'),
                ],
                'Copper' => [
                    'used'         => $this->getCellValue('Declaration', 'C32'),
                    'countries'    => $this->getCellValue('Declaration', 'C33'),
                    'smelterCount' => $this->getCellValue('Declaration', 'C34'),
                ],
                'Graphite' => [
                    'used'         => $this->getCellValue('Declaration', 'C35'),
                    'countries'    => $this->getCellValue('Declaration', 'C36'),
                    'smelterCount' => $this->getCellValue('Declaration', 'C37'),
                ],
                'Lithium' => [
                    'used'         => $this->getCellValue('Declaration', 'C38'),
                    'countries'    => $this->getCellValue('Declaration', 'C39'),
                    'smelterCount' => $this->getCellValue('Declaration', 'C40'),
                ],
                'Nickel' => [
                    'used'         => $this->getCellValue('Declaration', 'C41'),
                    'countries'    => $this->getCellValue('Declaration', 'C42'),
                    'smelterCount' => $this->getCellValue('Declaration', 'C43'),
                ],
            ],
            
            'comments' => $this->getCellValue('Declaration', 'C50'),
        ];
    }

    /**
     * 解析冶煉廠/加工廠清單
     */
    protected function parseSmelterList()
    {
        // EMRT 的工作表名稱可能是 Smelter List 或 Smelter Refiner Processor List
        $sheetName = 'Smelter List';
        if (!$this->spreadsheet->getSheetByName($sheetName)) {
            $sheetName = 'Smelter Refiner Processor List'; // EMRT 2.x 的可能名稱
        }

        $sheet = $this->spreadsheet->getSheetByName($sheetName);
        if (!$sheet) return [];

        $smelters = [];
        $highestRow = $sheet->getHighestRow();

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

    /**
     * 解析礦場清單
     */
    protected function parseMineList()
    {
        $sheet = $this->spreadsheet->getSheetByName('Mine List');
        if (!$sheet) return [];

        $mines = [];
        $highestRow = $sheet->getHighestRow();

        for ($row = 5; $row <= $highestRow; $row++) {
            $metal = $sheet->getCell('A' . $row)->getCalculatedValue();
            $mineName = $sheet->getCell('B' . $row)->getCalculatedValue();

            if (empty($metal) || empty($mineName)) continue;

            $mines[] = [
                'metal_type'    => $metal,
                'mine_name'     => $mineName,
                'mine_country'  => $sheet->getCell('C' . $row)->getCalculatedValue(),
                'mine_location' => $sheet->getCell('D' . $row)->getCalculatedValue(),
            ];
        }

        return $mines;
    }
}
