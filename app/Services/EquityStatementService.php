<?php

namespace App\Services;

use App\Models\Account;
use App\Models\FiscalPeriod;

class EquityStatementService
{
    /**
     * Generate Statement of Changes in Equity (Laporan Perubahan Ekuitas).
     *
     * Structure:
     * Modal Awal Periode
     * + Laba/Rugi Bersih Periode Berjalan
     * - Prive (jika ada)
     * = Modal Akhir Periode
     */
    public function generate(int $fiscalPeriodId): array
    {
        $period = FiscalPeriod::findOrFail($fiscalPeriodId);

        // Get net profit from Income Statement
        $incomeStatementService = new IncomeStatementService();
        $incomeStatement = $incomeStatementService->generate($fiscalPeriodId);
        $netProfit = $incomeStatement['net_profit'];

        // Get Modal Disetor (3110) beginning balance
        // For the first period, this is just the balance from journal entries
        $modalAccount = Account::where('code', '3110')->first();
        $modalBalance = $modalAccount ? $modalAccount->getBalanceForPeriod($fiscalPeriodId) : 0;

        // For beginning balance, we need to consider the modal before current period adjustments
        // In the first period, modal awal = modal disetor initial entry
        $beginningCapital = $modalBalance;

        // Get Prive (3120) - withdrawals
        $priveAccount = Account::where('code', '3120')->first();
        $priveAmount = $priveAccount ? abs($priveAccount->getBalanceForPeriod($fiscalPeriodId)) : 0;

        // Get Laba Ditahan (3300)
        $retainedEarningsAccount = Account::where('code', '3300')->first();
        $retainedEarnings = $retainedEarningsAccount ? $retainedEarningsAccount->getBalanceForPeriod($fiscalPeriodId) : 0;

        $endingCapital = $beginningCapital + $netProfit - $priveAmount + $retainedEarnings;

        return [
            'period' => $period,
            'beginning_capital' => round($beginningCapital, 2),
            'net_profit' => round($netProfit, 2),
            'prive' => round($priveAmount, 2),
            'retained_earnings' => round($retainedEarnings, 2),
            'ending_capital' => round($endingCapital, 2),
            'modal_account_name' => $modalAccount?->name ?? 'Modal Disetor',
            'prive_account_name' => $priveAccount?->name ?? 'Prive',
        ];
    }
}
