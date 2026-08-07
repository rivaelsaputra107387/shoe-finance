<?php
ini_set('memory_limit', '-1');
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

function updateExcelDates($file) {
    echo "Updating: $file\n";
    $spreadsheet = IOFactory::load($file);
    $worksheet = $spreadsheet->getActiveSheet();
    
    $updated = 0;
    foreach ($worksheet->getRowIterator() as $row) {
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false); 
        foreach ($cellIterator as $cell) {
            $val = $cell->getValue();
            
            // Check if string contains JULI 2026
            if (is_string($val) && strpos(strtoupper($val), 'JULI 2026') !== false) {
                $newVal = str_ireplace('JULI 2026', 'AGUSTUS 2026', $val);
                $cell->setValue($newVal);
                $updated++;
            }
            
            // Check if string contains JULI
            if (is_string($val) && strpos(strtoupper($val), 'JULI') !== false) {
                $newVal = str_ireplace('JULI', 'AGUSTUS', $val);
                $cell->setValue($newVal);
                $updated++;
            }

            // Check if string contains /07/
            if (is_string($val) && strpos($val, '/07/') !== false) {
                $newVal = str_replace('/07/', '/08/', $val);
                $cell->setValue($newVal);
                $updated++;
            }

            // Check if string contains -07-
            if (is_string($val) && strpos($val, '-07-') !== false) {
                $newVal = str_replace('-07-', '-08-', $val);
                $cell->setValue($newVal);
                $updated++;
            }
            
            // Check if date object
            if (Date::isDateTime($cell)) {
                 $dateObj = Date::excelToDateTimeObject($val);
                 if ($dateObj->format('m') === '07' && $dateObj->format('Y') === '2026') {
                     $dateObj->modify('+1 month');
                     $excelDate = Date::PHPToExcel($dateObj);
                     $cell->setValue($excelDate);
                     $updated++;
                 }
            }
        }
    }
    
    if ($updated > 0) {
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($file);
        echo "Updated $updated cells in $file\n";
    } else {
        echo "No changes made in $file\n";
    }
}

updateExcelDates('MUTASI REKENING JULI.xlsx');
