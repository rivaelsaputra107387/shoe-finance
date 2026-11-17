<?php

namespace App\Filament\Widgets;

use App\Models\FiscalPeriod;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class NetProfitLineChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Tren Laba Bersih';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 1;
    protected static ?string $maxHeight = '400px';

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['owner', 'finance']) ?? false;
    }

    protected static ?string $pollingInterval = null;
    protected static bool $isLazy = false;

    protected function getData(): array
    {
        return \Illuminate\Support\Facades\Cache::remember('net_profit_line_chart_data', 600, function () {
            $periods = FiscalPeriod::orderBy('start_date', 'desc')->limit(6)->get()->reverse();

            $netProfitData = [];
            $labels = [];

            foreach ($periods as $period) {
                $labels[] = $period->name;

                // Revenue
                $revenue = \App\Models\JournalEntryLine::whereHas('account', function($q) {
                        $q->where('code', 'like', '4%')->whereNotNull('parent_id');
                    })
                    ->whereHas('journalEntry', function($q) use ($period) {
                        $q->where('fiscal_period_id', $period->id)->where('status', 'posted')->where('is_closing', false)->whereNull('deleted_at');
                    })
                    ->selectRaw('COALESCE(SUM(credit) - SUM(debit), 0) as balance')
                    ->value('balance');

                // Expense
                $expenses = \App\Models\JournalEntryLine::whereHas('account', function($q) {
                        $q->where('code', 'like', '5%')
                          ->orWhere('code', 'like', '6%')
                          ->orWhere('code', 'like', '72%')
                          ->orWhere('code', 'like', '8%');
                    })
                    ->whereHas('journalEntry', function($q) use ($period) {
                        $q->where('fiscal_period_id', $period->id)->where('status', 'posted')->where('is_closing', false)->whereNull('deleted_at');
                    })
                    ->selectRaw('COALESCE(SUM(debit) - SUM(credit), 0) as balance')
                    ->value('balance');

                $netProfit = (float) $revenue - (float) $expenses;
                $netProfitData[] = $netProfit;
            }

            return [
                'datasets' => [
                    [
                        'label' => 'Laba Bersih',
                        'data' => $netProfitData,
                        'borderColor' => '#10b981', // emerald-500
                        'backgroundColor' => 'rgba(16, 185, 129, 0.2)',
                        'fill' => true,
                        'tension' => 0.4,
                    ],
                ],
                'labels' => $labels,
            ];
        });
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}
