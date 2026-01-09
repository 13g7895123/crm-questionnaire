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
     * 解析公司基本宣告
     */
    protected function parseDeclaration()
    {
        return [
            'companyName'      => $this->getCellValue('Declaration', 'C7'), // 同 CMRT 預設位置
            'companyCountry'   => $this->getCellValue('Declaration', 'C8'),
            'declarationScope' => $this->getCellValue('Declaration', 'C15'),
        ];
    }

    /**
     * 解析礦產回答狀況 (EMRT 涵蓋 Cobalt, Mica, Copper, Graphite, Lithium, Nickel)
     */
    protected function parseMineralDeclaration()
    {
        return [
            'Cobalt'   => ['used' => $this->getCellValue('Declaration', 'C25')],
            'Mica'     => ['used' => $this->getCellValue('Declaration', 'C26')],
            'Copper'   => ['used' => $this->getCellValue('Declaration', 'C27')],
            'Graphite' => ['used' => $this->getCellValue('Declaration', 'C28')],
            'Lithium'  => ['used' => $this->getCellValue('Declaration', 'C29')],
            'Nickel'   => ['used' => $this->getCellValue('Declaration', 'C30')],
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
            $metal = $sheet->getCell('A' . $row)->getValue();
            $mineName = $sheet->getCell('B' . $row)->getValue();

            if (empty($metal) || empty($mineName)) continue;

            $mines[] = [
                'metal_type'    => $metal,
                'mine_name'     => $mineName,
                'mine_country'  => $sheet->getCell('C' . $row)->getValue(),
                'mine_location' => $sheet->getCell('D' . $row)->getValue(),
            ];
        }

        return $mines;
    }
}
