<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

function dumpExcel($file) {
    echo "File: $file\n";
    $spreadsheet = IOFactory::load($file);
    $worksheet = $spreadsheet->getActiveSheet();
    
    $rowLimit = 5;
    foreach ($worksheet->getRowIterator() as $row) {
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false); 
        $rowData = [];
        foreach ($cellIterator as $cell) {
            $val = $cell->getValue();
            if (\PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($cell)) {
                 $val = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($val)->format('Y-m-d');
            }
            $rowData[] = $val;
        }
        echo implode(" | ", $rowData) . "\n";
        
        $rowLimit--;
        if ($rowLimit <= 0) break;
    }
    echo "\n";
}

dumpExcel('mutasi bca.xlsx');
dumpExcel('MUTASI REKENING JULI.xlsx');
