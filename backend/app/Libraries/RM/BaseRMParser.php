<?php

namespace App\Libraries\RM;

use PhpOffice\PhpSpreadsheet\IOFactory;

abstract class BaseRMParser
{
    protected $spreadsheet;
    protected $filePath;

    public function __construct($filePath)
    {
        $this->filePath = $filePath;
        $this->spreadsheet = IOFactory::load($filePath);
    }

    /**
     * 解析完整資料
     */
    abstract public function parse();

    /**
     * 讀取儲存格工具方法
     * 使用 getCalculatedValue() 來取得公式計算後的結果，而非公式字串
     */
    protected function getCellValue($sheetName, $coordinate)
    {
        $sheet = $this->spreadsheet->getSheetByName($sheetName);
        if (!$sheet) return null;
        
        try {
            // 嘗試取得計算值，若失敗則回傳原始值
            return $sheet->getCell($coordinate)->getCalculatedValue();
        } catch (\Exception $e) {
            // 若公式計算失敗，回傳原始值
            return $sheet->getCell($coordinate)->getValue();
        }
    }
}
