<?php

namespace App\Services;

use App\Models\Account;
use App\Models\FiscalPeriod;

class IncomeStatementService
{
    /**
     * Generate Income Statement (Laporan Laba Rugi) for a given fiscal period.
     * Uses a SINGLE bulk query via AccountBalanceService — no N+1.
     *
     * Structure:
     * - Pendapatan Usaha (4xxx)
     * - HPP & Beban Produksi (5xxx)
     * - Laba Kotor = Pendapatan - HPP
     * - Beban Operasional (6xxx)
     * - Laba Operasi = Laba Kotor - Beban Operasional
     * - Pendapatan Lain-lain (71xx)
     * - Beban Lain-lain (72xx)
     * - Beban Admin & Pajak (8xxx)
     * - Laba Bersih
     */
    public function generate(int $fiscalPeriodId): array
    {
        $period = FiscalPeriod::findOrFail($fiscalPeriodId);

        // ONE bulk query for this period (excluding closing entries)
        $balanceSvc = new AccountBalanceService();
        $totals = $balanceSvc->getPeriodTotals($fiscalPeriodId, excludeClosing: true);

        // ── Pendapatan Usaha (4xxx) ──
        $revenue      = $this->getAccountGroup('4', $totals, 'Kredit');
        $totalRevenue = $revenue->sum('balance');

        // ── HPP & Beban Produksi (5xxx) ──
        $hpp      = $this->getAccountGroup('5', $totals, 'Debet');
        $totalHpp = $hpp->sum('balance');

        $grossProfit = $totalRevenue - $totalHpp;

        // ── Beban Operasional (6xxx) ──
        $operationalExpenses      = $this->getAccountGroup('6', $totals, 'Debet');
        $totalOperationalExpenses = $operationalExpenses->sum('balance');

        $operatingProfit = $grossProfit - $totalOperationalExpenses;

        // ── Pendapatan Lain-lain (71xx) ──
        $otherRevenue      = $this->getAccountsByPrefix('71', $totals, 'Kredit');
        $totalOtherRevenue = $otherRevenue->sum('balance');

        // ── Beban Lain-lain (72xx) ──
        $otherExpenses      = $this->getAccountsByPrefix('72', $totals, 'Debet');
        $totalOtherExpenses = $otherExpenses->sum('balance');

        // ── Beban Admin & Pajak (8xxx) ──
        $adminExpenses      = $this->getAccountGroup('8', $totals, 'Debet');
        $totalAdminExpenses = $adminExpenses->sum('balance');

        $netProfit = $operatingProfit + $totalOtherRevenue - $totalOtherExpenses - $totalAdminExpenses;

        return [
            'period'                      => $period,
            'revenue'                     => $revenue,
            'total_revenue'               => round($totalRevenue, 2),
            'hpp'                         => $hpp,
            'total_hpp'                   => round($totalHpp, 2),
            'gross_profit'                => round($grossProfit, 2),
            'operational_expenses'        => $operationalExpenses,
            'total_operational_expenses'  => round($totalOperationalExpenses, 2),
            'operating_profit'            => round($operatingProfit, 2),
            'other_revenue'               => $otherRevenue,
            'total_other_revenue'         => round($totalOtherRevenue, 2),
            'other_expenses'              => $otherExpenses,
            'total_other_expenses'        => round($totalOtherExpenses, 2),
            'admin_expenses'              => $adminExpenses,
            'total_admin_expenses'        => round($totalAdminExpenses, 2),
            'net_profit'                  => round($netProfit, 2),
        ];
    }

    /**
     * Get accounts starting with a single prefix digit.
     * NO DB queries — pure in-memory lookup from pre-fetched totals.
     */
    private function getAccountGroup(string $prefixDigit, $totals, string $normalBalance)
    {
        return Account::active()
            ->where('code', 'like', $prefixDigit . '%')
            ->whereNotNull('parent_id')
            ->orderBy('code')
            ->get()
            ->map(function (Account $account) use ($totals, $normalBalance) {
                $row    = $totals->get($account->id);
                $debit  = $row ? (float) $row->total_debit  : 0.0;
                $credit = $row ? (float) $row->total_credit : 0.0;

                // For income accounts (Kredit normal): balance = credit - debit
                // For expense accounts (Debet normal): balance = debit - credit
                $balance = $normalBalance === 'Kredit'
                    ? abs($credit - $debit)
                    : abs($debit - $credit);

                return [
                    'code'    => $account->code,
                    'name'    => $account->name,
                    'balance' => $balance,
                ];
            })
            ->filter(fn ($row) => $row['balance'] > 0);
    }

    /**
     * Get accounts by 2-digit code prefix.
     * NO DB queries — pure in-memory lookup from pre-fetched totals.
     */
    private function getAccountsByPrefix(string $prefix, $totals, string $normalBalance)
    {
        return Account::active()
            ->where('code', 'like', $prefix . '%')
            ->whereNotNull('parent_id')
            ->orderBy('code')
            ->get()
            ->map(function (Account $account) use ($totals, $normalBalance) {
                $row    = $totals->get($account->id);
                $debit  = $row ? (float) $row->total_debit  : 0.0;
                $credit = $row ? (float) $row->total_credit : 0.0;

                $balance = $normalBalance === 'Kredit'
                    ? abs($credit - $debit)
                    : abs($debit - $credit);

                return [
                    'code'    => $account->code,
                    'name'    => $account->name,
                    'balance' => $balance,
                ];
            })
            ->filter(fn ($row) => $row['balance'] > 0);
    }
}
