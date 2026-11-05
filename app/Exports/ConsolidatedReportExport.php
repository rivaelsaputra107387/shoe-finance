<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ConsolidatedReportExport implements FromView, ShouldAutoSize
{
    public function __construct(public array $report) {}

    public function view(): View
    {
        return view('exports.consolidated', ['report' => $this->report]);
    }
}
