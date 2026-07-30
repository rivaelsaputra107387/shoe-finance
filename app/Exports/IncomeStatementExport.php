<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class IncomeStatementExport implements FromView, ShouldAutoSize, WithStyles, WithColumnFormatting, WithColumnWidths
{
    public function __construct(public array $data, public string $periodName)
    {
    }

    public function view(): View
    {
        return view('exports.income-statement', [
            'data' => $this->data,
            'periodName' => $this->periodName,
        ]);
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 45,
            'B' => 25,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // No need for complex styles here because the blade template 
            // uses inline styles (like font-weight: bold, background-color)
            // which Maatwebsite\Excel processes automatically via HTML reader.
        ];
    }
}
