<?php

namespace App\Services;

use App\Models\Account;
use App\Models\FiscalPeriod;

class IncomeStatementService
{
    /**
     * Generate Income Statement (Laporan Laba Rugi) for a given fiscal period.
     *
     * Structure:
     * - Pendapatan Usaha (4xxx)
     * - HPP & Beban Produksi (5xxx)
     * - Laba Kotor = Pendapatan - HPP
     * - Beban Operasional (6xxx)
     * - Laba Operasi = Laba Kotor - Beban Operasional
     * - Pendapatan Lain-lain (7100)
     * - Beban Lain-lain (7200)
     * - Beban Admin & Pajak (8xxx)
     * - Laba Bersih
     */
    public function generate(int $fiscalPeriodId): array
    {
        $period = FiscalPeriod::findOrFail($fiscalPeriodId);

        // ── Pendapatan Usaha (4xxx) ──
        $revenue = $this->getAccountGroup('4', $fiscalPeriodId);
        $totalRevenue = $revenue->sum('balance');

        // ── HPP & Beban Produksi (5xxx) ──
        $hpp = $this->getAccountGroup('5', $fiscalPeriodId);
        $totalHpp = $hpp->sum('balance');

        $grossProfit = $totalRevenue - $totalHpp;

        // ── Beban Operasional (6xxx) ──
        $operationalExpenses = $this->getAccountGroup('6', $fiscalPeriodId);
        $totalOperationalExpenses = $operationalExpenses->sum('balance');

        $operatingProfit = $grossProfit - $totalOperationalExpenses;

        // ── Pendapatan Lain-lain (71xx) ──
        $otherRevenue = $this->getAccountsByPrefix('71', $fiscalPeriodId);
        $totalOtherRevenue = $otherRevenue->sum('balance');

        // ── Beban Lain-lain (72xx) ──
        $otherExpenses = $this->getAccountsByPrefix('72', $fiscalPeriodId);
        $totalOtherExpenses = $otherExpenses->sum('balance');

        // ── Beban Admin & Pajak (8xxx) ──
        $adminExpenses = $this->getAccountGroup('8', $fiscalPeriodId);
        $totalAdminExpenses = $adminExpenses->sum('balance');

        $netProfit = $operatingProfit + $totalOtherRevenue - $totalOtherExpenses - $totalAdminExpenses;

        return [
            'period' => $period,
            'revenue' => $revenue,
            'total_revenue' => round($totalRevenue, 2),
            'hpp' => $hpp,
            'total_hpp' => round($totalHpp, 2),
            'gross_profit' => round($grossProfit, 2),
            'operational_expenses' => $operationalExpenses,
            'total_operational_expenses' => round($totalOperationalExpenses, 2),
            'operating_profit' => round($operatingProfit, 2),
            'other_revenue' => $otherRevenue,
            'total_other_revenue' => round($totalOtherRevenue, 2),
            'other_expenses' => $otherExpenses,
            'total_other_expenses' => round($totalOtherExpenses, 2),
            'admin_expenses' => $adminExpenses,
            'total_admin_expenses' => round($totalAdminExpenses, 2),
            'net_profit' => round($netProfit, 2),
        ];
    }

    /**
     * Get accounts starting with a prefix digit and their balances.
     */
    private function getAccountGroup(string $prefixDigit, int $fiscalPeriodId)
    {
        return Account::active()
            ->where('code', 'like', $prefixDigit . '%')
            ->whereNotNull('parent_id') // Only leaf accounts
            ->orderBy('code')
            ->get()
            ->map(function (Account $account) use ($fiscalPeriodId) {
                return [
                    'code' => $account->code,
                    'name' => $account->name,
                    'balance' => abs($account->getBalanceForPeriod($fiscalPeriodId)),
                ];
            })
            ->filter(fn ($row) => $row['balance'] > 0);
    }

    /**
     * Get accounts by code prefix (2 digit).
     */
    private function getAccountsByPrefix(string $prefix, int $fiscalPeriodId)
    {
        return Account::active()
            ->where('code', 'like', $prefix . '%')
            ->whereNotNull('parent_id')
            ->orderBy('code')
            ->get()
            ->map(function (Account $account) use ($fiscalPeriodId) {
                return [
                    'code' => $account->code,
                    'name' => $account->name,
                    'balance' => abs($account->getBalanceForPeriod($fiscalPeriodId)),
                ];
            })
            ->filter(fn ($row) => $row['balance'] > 0);
    }
}
