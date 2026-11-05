<?php

namespace App\Http\Controllers;

use App\Exports\ConsolidatedReportExport;
use App\Models\Central\CalendarPeriod;
use App\Services\Consolidation\ConsolidatedReports;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class ConsolidatedReportController extends Controller
{
    private const REPORTS = [
        'income-statement' => ['method' => 'incomeStatement', 'file' => 'Laba_Rugi_Gabungan'],
        'balance-sheet' => ['method' => 'balanceSheet', 'file' => 'Neraca_Gabungan'],
        'equity-statement' => ['method' => 'equityStatement', 'file' => 'Perubahan_Ekuitas_Gabungan'],
        'cash-flow' => ['method' => 'cashFlow', 'file' => 'Arus_Kas_Gabungan'],
    ];

    public function __construct(private ConsolidatedReports $reports) {}

    public function show(Request $request, string $report): Response
    {
        $period = $this->period($request);

        return Inertia::render('Consolidated/Report', [
            'reportKey' => $report,
            'report' => $period ? $this->reports->{self::REPORTS[$report]['method']}($period) : null,
            'periods' => $this->periods(),
            'selectedPeriod' => $period?->start_date->toDateString(),
        ]);
    }

    public function export(Request $request, string $report)
    {
        $period = $this->period($request);
        abort_unless($period, 404);

        $data = $this->reports->{self::REPORTS[$report]['method']}($period);
        $name = self::REPORTS[$report]['file'].'_'.str_replace(' ', '_', $period->name);

        if ($request->input('format') === 'excel') {
            return Excel::download(new ConsolidatedReportExport($data), "{$name}.xlsx");
        }

        return Pdf::loadView('reports.consolidated.report', [
            'report' => $data,
            'title' => strtoupper($data['title']),
            'subtitle' => "Periode: {$data['period']['name']}",
            'heading' => $this->heading($data),
        ])->setPaper('a4', 'landscape')->download("{$name}.pdf");
    }

    public function reconciliation(Request $request): Response
    {
        $period = $this->period($request);

        return Inertia::render('Consolidated/Reconciliation', [
            'reconciliation' => $period ? $this->reports->reconciliation($period) : null,
            'periods' => $this->periods(),
            'selectedPeriod' => $period?->start_date->toDateString(),
        ]);
    }

    private function heading(array $report): string
    {
        return 'GABUNGAN: '.strtoupper(collect($report['units'])->pluck('name')->implode(', '));
    }

    /**
     * Periode yang diminta, atau periode terbaru yang sudah berjalan.
     */
    private function period(Request $request): ?CalendarPeriod
    {
        if ($request->filled('period')) {
            return CalendarPeriod::where('start_date', $request->input('period'))->first();
        }

        return CalendarPeriod::where('start_date', '<=', now()->toDateString())->orderByDesc('start_date')->first()
            ?? CalendarPeriod::orderBy('start_date')->first();
    }

    private function periods(): array
    {
        return CalendarPeriod::orderByDesc('start_date')->get()
            ->map(fn (CalendarPeriod $period) => ['name' => $period->name, 'start_date' => $period->start_date->toDateString()])
            ->all();
    }
}
