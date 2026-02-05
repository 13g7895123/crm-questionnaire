<?php

namespace App\Libraries\RM;

class AMRTParser extends BaseRMParser
{
    /**
     * 解析 AMRT 資料
     */
    public function parse()
    {
        return [
            'type'                => 'AMRT',
            'declaration'         => $this->parseDeclaration(),
            'smelterList'         => $this->parseSmelterList(),
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
            'selectedMinerals'         => $this->getCellValue('Declaration', 'C16'), // AMRT 自選礦產清單
        ];
    }

    /**
     * 解析礦產回答狀況
     * AMRT 是自選礦產，通常在 Declaration 頁面會列出選取的礦產及其回答
     */
    protected function parseMineralDeclaration()
    {
        // AMRT 的礦產通常在 C25 之後開始列出
        // 這裡暫時抓取前 10 個可能的礦產位址
        $minerals = [];
        for ($i = 25; $i <= 34; $i++) {
            $mineralName = $this->getCellValue('Declaration', 'B' . $i);
            $answer = $this->getCellValue('Declaration', 'C' . $i);

            if (!empty($mineralName)) {
                $minerals[$mineralName] = ['used' => $answer];
            }
        }

        return $minerals;
    }

    /**
     * 解析冶煉廠清單
     */
    protected function parseSmelterList()
    {
        $sheetName = 'Smelter List';
        if (!$this->spreadsheet->getSheetByName($sheetName)) {
            $sheetName = 'Smelter Refiner Processor List';
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
}
