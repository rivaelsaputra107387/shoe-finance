<?php

namespace App\Filament\Widgets;

use App\Models\Account;
use App\Models\FiscalPeriod;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CashBalanceWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static ?string $pollingInterval = null;
    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['owner', 'finance']) ?? false;
    }

    protected function getStats(): array
    {
        return \Illuminate\Support\Facades\Cache::remember('cash_balance_widget_data', 600, function () {
            $period = FiscalPeriod::active();
            if (!$period) {
                return [Stat::make('Tidak ada periode aktif', '-')];
            }

            $stats = [];

            // 1. Total Kas & Bank (1110 & 1120 series)
            $kasBankAccounts = Account::active()
                ->where(function ($q) {
                    $q->where('code', 'like', '111%')
                      ->orWhere('code', 'like', '112%');
                })
                ->whereNotNull('parent_id')
                ->get();
            
            $totalKasBank = $kasBankAccounts->sum(function($account) use ($period) {
                return $account->getBalanceForPeriod($period->id);
            });

            $stats[] = Stat::make('Total Kas & Bank', 'Rp ' . number_format($totalKasBank, 0, ',', '.'))
                ->description('Saldo Kas Tunai & Bank')
                ->descriptionIcon('heroicon-m-wallet')
                ->color('success');

            // 2. Piutang Usaha (1130 series)
            $piutangAccounts = Account::active()
                ->where('code', 'like', '113%')
                ->whereNotNull('parent_id')
                ->get();
            
            $totalPiutang = $piutangAccounts->sum(function($account) use ($period) {
                return $account->getBalanceForPeriod($period->id); // Piutang is Debit normal
            });

            $stats[] = Stat::make('Total Piutang', 'Rp ' . number_format($totalPiutang, 0, ',', '.'))
                ->description('Uang masuk yang tertunda')
                ->descriptionIcon('heroicon-m-arrow-right-on-rectangle')
                ->color('info');

            // 3. Hutang Lancar (2100 series)
            $hutangAccounts = Account::active()
                ->where('code', 'like', '2%')
                ->whereNotNull('parent_id')
                ->get();
            
            $totalHutang = $hutangAccounts->sum(function($account) use ($period) {
                // Hutang is Credit normal.
                return abs($account->getBalanceForPeriod($period->id)); 
            });

            $stats[] = Stat::make('Total Hutang', 'Rp ' . number_format($totalHutang, 0, ',', '.'))
                ->description('Kewajiban Perusahaan')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger');

            return $stats;
        });
    }
}
