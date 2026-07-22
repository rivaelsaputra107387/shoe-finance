<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class IncomeStatementExport implements FromView, ShouldAutoSize, WithStyles
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

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
