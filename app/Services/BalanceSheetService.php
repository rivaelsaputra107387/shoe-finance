<?php

namespace App\Services;

use App\Models\Account;
use App\Models\FiscalPeriod;

class BalanceSheetService
{
    /**
     * Generate Balance Sheet (Neraca) for a given fiscal period.
     * Uses a SINGLE bulk query via AccountBalanceService — no N+1.
     *
     * Structure:
     * Left side: Aset (Lancar + Tetap)
     * Right side: Kewajiban (Lancar + Jangka Panjang) + Ekuitas
     * Must balance: Total Aset = Total Kewajiban + Total Ekuitas
     */
    public function generate(int $fiscalPeriodId): array
    {
        $period = FiscalPeriod::findOrFail($fiscalPeriodId);

        // ONE bulk query for all cumulative totals
        $balanceSvc = new AccountBalanceService();
        $totals = $balanceSvc->getCumulativeTotalsUpTo($fiscalPeriodId);

        // ── ASET ──
        $currentAssets   = $this->getAccountsByPrefix('11', $totals, 'Aset');
        $fixedAssets     = $this->getAccountsByPrefix('12', $totals, 'Aset');
        $totalCurrentAssets = $currentAssets->sum('balance');
        $totalFixedAssets   = $fixedAssets->sum('balance');
        $totalAssets        = $totalCurrentAssets + $totalFixedAssets;

        // ── KEWAJIBAN ──
        $currentLiabilities      = $this->getAccountsByPrefix('21', $totals, 'Kewajiban');
        $longTermLiabilities     = $this->getAccountsByPrefix('22', $totals, 'Kewajiban');
        $totalCurrentLiabilities = $currentLiabilities->sum('balance');
        $totalLongTermLiabilities= $longTermLiabilities->sum('balance');
        $totalLiabilities        = $totalCurrentLiabilities + $totalLongTermLiabilities;

        // ── EKUITAS ──
        $equity = $this->getAccountsByPrefix('3', $totals, 'Ekuitas');

        // Tambahkan Laba Bersih hanya untuk periode OPEN
        if ($period->status === 'open') {
            $incomeData = (new IncomeStatementService())->generate($fiscalPeriodId);
            $netProfit  = (float) $incomeData['net_profit'];
            if (abs($netProfit) > 0.01) {
                $equity->push([
                    'code'           => '-',
                    'name'           => 'Laba Bersih Periode Berjalan',
                    'type'           => 'Ekuitas',
                    'normal_balance' => 'Kredit',
                    'balance'        => $netProfit,
                ]);
            }
        }

        $totalEquity              = $equity->sum('balance');
        $totalLiabilitiesAndEquity= $totalLiabilities + $totalEquity;
        $isBalanced               = abs($totalAssets - $totalLiabilitiesAndEquity) < 0.01;

        return [
            'period'                      => $period,
            'current_assets'              => $currentAssets,
            'total_current_assets'        => round($totalCurrentAssets, 2),
            'fixed_assets'                => $fixedAssets,
            'total_fixed_assets'          => round($totalFixedAssets, 2),
            'total_assets'                => round($totalAssets, 2),
            'current_liabilities'         => $currentLiabilities,
            'total_current_liabilities'   => round($totalCurrentLiabilities, 2),
            'long_term_liabilities'       => $longTermLiabilities,
            'total_long_term_liabilities' => round($totalLongTermLiabilities, 2),
            'total_liabilities'           => round($totalLiabilities, 2),
            'equity'                      => $equity,
            'total_equity'                => round($totalEquity, 2),
            'total_liabilities_and_equity'=> round($totalLiabilitiesAndEquity, 2),
            'is_balanced'                 => $isBalanced,
        ];
    }

    /**
     * Get leaf accounts by code prefix using pre-fetched bulk totals.
     * NO additional DB queries — pure in-memory lookup.
     */
    private function getAccountsByPrefix(string $prefix, $totals, string $category)
    {
        return Account::active()
            ->where('code', 'like', $prefix . '%')
            ->whereNotNull('parent_id')
            ->orderBy('code')
            ->get()
            ->map(function (Account $account) use ($totals, $category) {
                $row    = $totals->get($account->id);
                $debit  = $row ? (float) $row->total_debit  : 0.0;
                $credit = $row ? (float) $row->total_credit : 0.0;

                $balance = $category === 'Aset'
                    ? ($debit - $credit)
                    : ($credit - $debit);

                return [
                    'code'           => $account->code,
                    'name'           => $account->name,
                    'type'           => $account->type,
                    'normal_balance' => $account->normal_balance,
                    'balance'        => $balance,
                ];
            })
            ->filter(fn ($row) => abs($row['balance']) > 0.01)
            ->values();
    }
}
