<?php

namespace App\Filament\Widgets;

use App\Models\FiscalPeriod;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class RevenuePieChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Komposisi Pendapatan (Periode Aktif)';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 1;
    protected static ?string $maxHeight = '300px';

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['owner', 'finance']) ?? false;
    }

    protected static ?string $pollingInterval = null;
    protected static bool $isLazy = false;

    protected function getData(): array
    {
        return \Illuminate\Support\Facades\Cache::remember('revenue_pie_chart_data', 600, function () {
            $period = FiscalPeriod::active();
            if (!$period) {
                return [
                    'datasets' => [],
                    'labels' => [],
                ];
            }

            $revenues = \App\Models\JournalEntryLine::join('accounts', 'journal_entry_lines.account_id', '=', 'accounts.id')
                ->where('accounts.code', 'like', '4%')
                ->whereHas('journalEntry', function($q) use ($period) {
                    $q->where('fiscal_period_id', $period->id)->where('status', 'posted')->where('is_closing', false)->whereNull('deleted_at');
                })
                ->select('accounts.name', DB::raw('SUM(journal_entry_lines.credit) - SUM(journal_entry_lines.debit) as balance'))
                ->groupBy('accounts.id', 'accounts.name')
                ->havingRaw('SUM(journal_entry_lines.credit) - SUM(journal_entry_lines.debit) > 0')
                ->orderByRaw('balance DESC')
                ->get();

            $labels = $revenues->pluck('name')->toArray();
            $data = $revenues->pluck('balance')->toArray();

            $colors = [
                '#3b82f6', '#0ea5e9', '#06b6d4', '#14b8a6', '#10b981', '#22c55e', '#84cc16', '#f59e0b', '#f97316', '#ef4444', '#f43f5e', '#ec4899', '#d946ef', '#a855f7', '#8b5cf6', '#6366f1'
            ];

            $bgColors = [];
            for ($i = 0; $i < count($data); $i++) {
                $bgColors[] = $colors[$i % count($colors)];
            }

            if (empty($data)) {
                $labels = ['Belum Ada Data'];
                $data = [1];
                $bgColors = ['#e5e7eb']; // gray-200
            }

            return [
                'datasets' => [
                    [
                        'label' => 'Total Pendapatan',
                        'data' => $data,
                        'backgroundColor' => $bgColors,
                    ],
                ],
                'labels' => $labels,
            ];
        });
    }

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getOptions(): array
    {
        return [
            'maintainAspectRatio' => false,
            'scales' => [
                'x' => ['display' => false],
                'y' => ['display' => false],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'left',
                ],
            ],
        ];
    }
}
