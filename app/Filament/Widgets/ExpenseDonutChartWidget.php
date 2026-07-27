<?php

namespace App\Filament\Widgets;

use App\Models\FiscalPeriod;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ExpenseDonutChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Komposisi Beban (Periode Aktif)';
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
        return \Illuminate\Support\Facades\Cache::remember('expense_donut_chart_data', 600, function () {
            $period = FiscalPeriod::active();
            if (!$period) {
                return [
                    'datasets' => [],
                    'labels' => [],
                ];
            }

            $expenses = \App\Models\JournalEntryLine::join('accounts', 'journal_entry_lines.account_id', '=', 'accounts.id')
                ->where(function ($q) {
                    $q->where('accounts.code', 'like', '5%')
                      ->orWhere('accounts.code', 'like', '6%')
                      ->orWhere('accounts.code', 'like', '72%')
                      ->orWhere('accounts.code', 'like', '8%');
                })
                ->whereHas('journalEntry', function($q) use ($period) {
                    $q->where('fiscal_period_id', $period->id)->where('status', 'posted')->where('is_closing', false)->whereNull('deleted_at');
                })
                ->select('accounts.name', DB::raw('SUM(journal_entry_lines.debit) - SUM(journal_entry_lines.credit) as balance'))
                ->groupBy('accounts.id', 'accounts.name')
                ->havingRaw('SUM(journal_entry_lines.debit) - SUM(journal_entry_lines.credit) > 0')
                ->orderByRaw('balance DESC')
                ->get();

            $labels = $expenses->pluck('name')->toArray();
            $data = $expenses->pluck('balance')->toArray();

            $colors = [
                '#ef4444', '#f97316', '#f59e0b', '#84cc16', '#22c55e', '#10b981', '#14b8a6', '#06b6d4', '#0ea5e9', '#3b82f6', '#6366f1', '#8b5cf6', '#a855f7', '#d946ef', '#ec4899', '#f43f5e'
            ];

            // If we have more data than colors, repeat colors
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
                        'label' => 'Total Beban',
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
        return 'doughnut';
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
                    'position' => 'right',
                ],
            ],
            'cutout' => '70%',
        ];
    }
}
