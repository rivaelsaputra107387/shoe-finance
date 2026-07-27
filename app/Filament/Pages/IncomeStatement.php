<?php

namespace App\Filament\Pages;

use App\Models\FiscalPeriod;
use App\Services\IncomeStatementService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Pages\Page;

class IncomeStatement extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Laporan Laba Rugi';
    protected static ?string $navigationGroup = 'Laporan Keuangan';
    protected static ?string $title = 'Laporan Laba Rugi';
    protected static ?int $navigationSort = 3;
    protected static string $view = 'filament.pages.income-statement';

    public ?int $fiscal_period_id = null;
    public ?array $data = [];
    public array $reportData = [];

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole(['owner', 'finance']) ?? false;
    }

    public function mount(): void
    {
        if (!auth()->user()?->hasAnyRole(['owner', 'finance'])) {
            abort(403, 'Unauthorized access.');
        }

        $activePeriod = FiscalPeriod::active();
        $this->fiscal_period_id = $activePeriod?->id;
        if ($this->fiscal_period_id) {
            $this->generateReport();
        }
    }

    public function updatedFiscalPeriodId(): void
    {
        $this->generateReport();
    }

    public function getAvailablePeriodsProperty()
    {
        return FiscalPeriod::orderByDesc('start_date')->get();
    }

    public function generateReport(): void
    {
        if (!$this->fiscal_period_id) {
            $this->reportData = [];
            return;
        }

        $service = new IncomeStatementService();
        $result = $service->generate($this->fiscal_period_id);

        $this->reportData = [
            'period_name' => $result['period']->name,
            'revenue' => $result['revenue']->toArray(),
            'total_revenue' => (float)$result['total_revenue'],
            'hpp' => $result['hpp']->toArray(),
            'total_hpp' => (float)$result['total_hpp'],
            'gross_profit' => (float)$result['gross_profit'],
            'operational_expenses' => $result['operational_expenses']->toArray(),
            'total_operational_expenses' => (float)$result['total_operational_expenses'],
            'operating_profit' => (float)$result['operating_profit'],
            'other_revenue' => $result['other_revenue']->toArray(),
            'total_other_revenue' => (float)$result['total_other_revenue'],
            'other_expenses' => $result['other_expenses'] ? $result['other_expenses']->toArray() : [],
            'total_other_expenses' => (float)($result['total_other_expenses'] ?? 0),
            'admin_expenses' => $result['admin_expenses']->toArray(),
            'total_admin_expenses' => (float)$result['total_admin_expenses'],
            'net_profit' => (float)$result['net_profit'],
        ];
    }

    public function exportPdf()
    {
        if (empty($this->reportData)) {
            $this->generateReport();
        }

        $pdf = Pdf::loadView('reports.income-statement-pdf', ['data' => $this->reportData]);
        return response()->streamDownload(
            fn () => print($pdf->output()),
            "laporan-laba-rugi-{$this->reportData['period_name']}.pdf"
        );
    }

    public function exportExcel()
    {
        if (empty($this->reportData)) {
            $this->generateReport();
        }

        $fileName = "laporan-laba-rugi-{$this->reportData['period_name']}.csv";

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            
            // Laporan Laba Rugi Title
            fputcsv($file, ['LAPORAN LABA RUGI']);
            fputcsv($file, ['Periode: ' . $this->reportData['period_name']]);
            fputcsv($file, []); // Empty line

            fputcsv($file, ['AKUN', 'TOTAL']);
            fputcsv($file, []);
            
            // PENDAPATAN
            fputcsv($file, ['PENDAPATAN USAHA']);
            foreach ($this->reportData['revenue'] as $item) {
                fputcsv($file, [$item['code'] . ' - ' . $item['name'], $item['balance']]);
            }
            fputcsv($file, ['TOTAL PENDAPATAN USAHA', $this->reportData['total_revenue']]);
            fputcsv($file, []);
            
            // HPP
            fputcsv($file, ['HARGA POKOK PENJUALAN']);
            foreach ($this->reportData['hpp'] as $item) {
                fputcsv($file, [$item['code'] . ' - ' . $item['name'], $item['balance']]);
            }
            fputcsv($file, ['TOTAL HPP', $this->reportData['total_hpp']]);
            fputcsv($file, []);

            // LABA KOTOR
            fputcsv($file, ['LABA KOTOR', $this->reportData['gross_profit']]);
            fputcsv($file, []);

            // BEBAN OPERASIONAL
            fputcsv($file, ['BEBAN OPERASIONAL']);
            foreach ($this->reportData['operational_expenses'] as $item) {
                fputcsv($file, [$item['code'] . ' - ' . $item['name'], $item['balance']]);
            }
            fputcsv($file, ['TOTAL BEBAN OPERASIONAL', $this->reportData['total_operational_expenses']]);
            fputcsv($file, []);

            // LABA OPERASI
            fputcsv($file, ['LABA OPERASI', $this->reportData['operating_profit']]);
            fputcsv($file, []);
            
            // PENDAPATAN/BEBAN LAIN-LAIN
            if (!empty($this->reportData['other_revenue'])) {
                fputcsv($file, ['PENDAPATAN LAIN-LAIN']);
                foreach ($this->reportData['other_revenue'] as $item) {
                    fputcsv($file, [$item['code'] . ' - ' . $item['name'], $item['balance']]);
                }
                fputcsv($file, ['TOTAL PENDAPATAN LAIN', $this->reportData['total_other_revenue']]);
                fputcsv($file, []);
            }

            if (!empty($this->reportData['other_expenses'])) {
                fputcsv($file, ['BEBAN LAIN-LAIN']);
                foreach ($this->reportData['other_expenses'] as $item) {
                    fputcsv($file, [$item['code'] . ' - ' . $item['name'], $item['balance']]);
                }
                fputcsv($file, ['TOTAL BEBAN LAIN', $this->reportData['total_other_expenses']]);
                fputcsv($file, []);
            }

            if (!empty($this->reportData['admin_expenses'])) {
                fputcsv($file, ['BEBAN ADMIN & PAJAK']);
                foreach ($this->reportData['admin_expenses'] as $item) {
                    fputcsv($file, [$item['code'] . ' - ' . $item['name'], $item['balance']]);
                }
                fputcsv($file, ['TOTAL ADMIN & PAJAK', $this->reportData['total_admin_expenses']]);
                fputcsv($file, []);
            }

            // LABA BERSIH
            fputcsv($file, ['LABA BERSIH', $this->reportData['net_profit']]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
