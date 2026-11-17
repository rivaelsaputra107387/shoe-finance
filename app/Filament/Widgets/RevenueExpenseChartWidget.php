<?php

namespace App\Filament\Widgets;

use App\Models\Account;
use App\Models\FiscalPeriod;
use Filament\Widgets\ChartWidget;

class RevenueExpenseChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Pendapatan vs Beban';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 1;
    protected static ?string $maxHeight = '400px';
    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['owner', 'finance']) ?? false;
    }

    protected function getData(): array
    {
        return \Illuminate\Support\Facades\Cache::remember('revenue_expense_chart_data', 600, function () {
            // Get last 6 fiscal periods
            $periods = FiscalPeriod::orderBy('start_date', 'desc')->limit(6)->get()->reverse();

            $revenueData = [];
            $expenseData = [];
            $labels = [];

            foreach ($periods as $period) {
                $labels[] = $period->name;

                // Aggregate revenue for this period (Normal Balance: Credit)
                $revenue = \App\Models\JournalEntryLine::whereHas('account', function($q) {
                        $q->where('code', 'like', '4%')->whereNotNull('parent_id');
                    })
                    ->whereHas('journalEntry', function($q) use ($period) {
                        $q->where('fiscal_period_id', $period->id)->where('status', 'posted')->where('is_closing', false)->whereNull('deleted_at');
                    })
                    ->selectRaw('COALESCE(SUM(credit) - SUM(debit), 0) as balance')
                    ->value('balance');
                
                $revenueData[] = abs((float) $revenue);

                // Aggregate expenses for this period (Normal Balance: Debit)
                $expenses = \App\Models\JournalEntryLine::whereHas('account', function($q) {
                        $q->where('code', 'like', '5%')
                          ->orWhere('code', 'like', '6%')
                          ->orWhere('code', 'like', '72%')
                          ->orWhere('code', 'like', '8%');
                    })
                    ->whereNotNull('account_id')
                    ->whereHas('journalEntry', function($q) use ($period) {
                        $q->where('fiscal_period_id', $period->id)->where('status', 'posted')->where('is_closing', false)->whereNull('deleted_at');
                    })
                    ->selectRaw('COALESCE(SUM(debit) - SUM(credit), 0) as balance')
                    ->value('balance');
                    
                $expenseData[] = abs((float) $expenses);
            }

            return [
                'datasets' => [
                    [
                        'label' => 'Pendapatan',
                        'data' => $revenueData,
                        'backgroundColor' => '#3b82f6', // blue-500
                    ],
                    [
                        'label' => 'Beban & HPP',
                        'data' => $expenseData,
                        'backgroundColor' => '#ef4444', // red-500
                    ],
                ],
                'labels' => $labels,
            ];
        });
    }

    protected function getType(): string
    {
        return 'bar';
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
