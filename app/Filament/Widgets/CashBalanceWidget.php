<?php

namespace App\Filament\Widgets;

use App\Models\Account;
use App\Models\FiscalPeriod;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CashBalanceWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['owner', 'finance']) ?? false;
    }

    protected function getStats(): array
    {
        $period = FiscalPeriod::active();
        if (!$period) {
            return [Stat::make('Tidak ada periode aktif', '-')];
        }

        $stats = [];

        // 1. Total Kas & Bank
        $kasBankAccounts = Account::active()
            ->where('code', 'like', '11%')
            ->whereNotNull('parent_id')
            ->get();
        
        $totalKasBank = $kasBankAccounts->sum(fn ($a) => $a->getBalanceForPeriod($period->id));

        $stats[] = Stat::make('Total Kas & Bank', 'Rp ' . number_format($totalKasBank, 0, ',', '.'))
            ->description('Saldo seluruh kas dan rekening')
            ->descriptionIcon('heroicon-m-wallet')
            ->color($totalKasBank >= 0 ? 'success' : 'danger')
            ->chart([7, 10, 13, 15, 14, 16, 20]);

        // 2. Total Revenue this period
        $totalRevenue = Account::active()
            ->where('code', 'like', '4%')
            ->whereNotNull('parent_id')
            ->get()
            ->sum(fn ($a) => abs($a->getBalanceForPeriod($period->id)));

        $stats[] = Stat::make('Total Pendapatan', 'Rp ' . number_format($totalRevenue, 0, ',', '.'))
            ->description('Periode: ' . $period->name)
            ->descriptionIcon('heroicon-m-arrow-trending-up')
            ->color('primary')
            ->chart([3, 5, 4, 8, 7, 12, 15]);

        // 3. Laba Bersih
        $expenses = Account::active()
            ->where(function ($q) {
                $q->where('code', 'like', '5%')
                  ->orWhere('code', 'like', '6%')
                  ->orWhere('code', 'like', '72%')
                  ->orWhere('code', 'like', '8%');
            })
            ->whereNotNull('parent_id')
            ->get()
            ->sum(fn ($a) => abs($a->getBalanceForPeriod($period->id)));

        $netProfit = $totalRevenue - $expenses;

        $stats[] = Stat::make('Laba Bersih', 'Rp ' . number_format($netProfit, 0, ',', '.'))
            ->description($netProfit >= 0 ? 'Profitabilitas Surplus' : 'Profitabilitas Defisit')
            ->descriptionIcon($netProfit >= 0 ? 'heroicon-m-check-badge' : 'heroicon-m-exclamation-circle')
            ->color($netProfit >= 0 ? 'success' : 'danger')
            ->chart($netProfit >= 0 ? [2, 4, 6, 10, 14, 17, 22] : [22, 17, 14, 10, 6, 4, 2]);

        return $stats;
    }
}
