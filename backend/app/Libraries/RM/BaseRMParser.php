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
     */
    protected function getCellValue($sheetName, $coordinate)
    {
        $sheet = $this->spreadsheet->getSheetByName($sheetName);
        if (!$sheet) return null;
        return $sheet->getCell($coordinate)->getValue();
    }
}
