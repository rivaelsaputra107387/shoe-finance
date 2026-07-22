<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\File;

class ExcelInspectorSeeder extends Seeder
{
    /**
     * Inspect the uploaded Excel file and write contents to a text file
     * so that the agent can read and understand the exact layout.
     */
    public function run(): void
    {
        $filePath = base_path('LAPORAN KEUANGAN SHOE WORKSHOP (1).xlsx');

        if (!File::exists($filePath)) {
            $this->command->error("Excel file not found at: {$filePath}");
            return;
        }

        $spreadsheet = IOFactory::load($filePath);
        $output = "=== EXCEL INSPECTION REPORT ===\n";
        $output .= "File: LAPORAN KEUANGAN SHOE WORKSHOP (1).xlsx\n";
        $output .= "Sheet Names: " . implode(', ', $spreadsheet->getSheetNames()) . "\n\n";

        // Sheets to inspect
        $sheetsToInspect = ['COA', 'CONTROL', 'JURNAL'];

        foreach ($sheetsToInspect as $sheetName) {
            if (!$spreadsheet->sheetNameExists($sheetName)) {
                $output .= "=== Sheet: {$sheetName} (NOT FOUND) ===\n\n";
                continue;
            }

            $sheet = $spreadsheet->getSheetByName($sheetName);
            $maxRow = min($sheet->getHighestRow(), 40); // Inspect first 40 rows
            $maxCol = $sheet->getHighestColumn();

            $output .= "=== Sheet: {$sheetName} (Highest Row: {$sheet->getHighestRow()}, Highest Col: {$sheet->getHighestColumn()}) ===\n";
            
            for ($row = 1; $row <= $maxRow; $row++) {
                $rowValues = [];
                // Get cells up to column H or highest
                $colLimit = min(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($maxCol), 12);
                
                for ($col = 1; $col <= $colLimit; $col++) {
                    $cellValue = $sheet->getCell([$col, $row])->getFormattedValue();
                    $rowValues[] = $cellValue !== null && $cellValue !== '' ? (string)$cellValue : '[null]';
                }
                
                // Only write row if it has non-null content
                if (collect($rowValues)->contains(fn($val) => $val !== '[null]')) {
                    $output .= "Row " . sprintf("%02d", $row) . ": " . implode(' | ', $rowValues) . "\n";
                }
            }
            $output .= "\n";
        }

        // Save output to workspace
        File::put(base_path('excel_structure.txt'), $output);
        $this->command->info("Inspection complete! Output written to excel_structure.txt");
    }
}
