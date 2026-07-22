<?php

namespace App\Services;

use App\Models\Account;
use App\Models\FiscalPeriod;

class BalanceSheetService
{
    /**
     * Generate Balance Sheet (Neraca) for a given fiscal period.
     *
     * Structure:
     * Left side: Aset (Lancar + Tetap)
     * Right side: Kewajiban (Lancar + Jangka Panjang) + Ekuitas
     * Must balance: Total Aset = Total Kewajiban + Total Ekuitas
     */
    public function generate(int $fiscalPeriodId): array
    {
        $period = FiscalPeriod::findOrFail($fiscalPeriodId);

        // ── ASET (Normal Balance: Debet. Hitung: Debit - Kredit) ──
        $currentAssets = $this->getAccountsByPrefix('11', $fiscalPeriodId, 'Aset');
        $totalCurrentAssets = $currentAssets->sum('balance');

        $fixedAssets = $this->getAccountsByPrefix('12', $fiscalPeriodId, 'Aset');
        $totalFixedAssets = $fixedAssets->sum('balance');

        $totalAssets = $totalCurrentAssets + $totalFixedAssets;

        // ── KEWAJIBAN (Normal Balance: Kredit. Hitung: Kredit - Debit) ──
        $currentLiabilities = $this->getAccountsByPrefix('21', $fiscalPeriodId, 'Kewajiban');
        $totalCurrentLiabilities = $currentLiabilities->sum('balance');

        $longTermLiabilities = $this->getAccountsByPrefix('22', $fiscalPeriodId, 'Kewajiban');
        $totalLongTermLiabilities = $longTermLiabilities->sum('balance');

        $totalLiabilities = $totalCurrentLiabilities + $totalLongTermLiabilities;

        // ── EKUITAS (Normal Balance: Kredit. Hitung: Kredit - Debit) ──
        $equity = $this->getAccountsByPrefix('3', $fiscalPeriodId, 'Ekuitas');
        
        // Dapatkan Laba/Rugi Bersih dari Laporan Laba Rugi untuk periode ini
        $incomeStatementService = new IncomeStatementService();
        $incomeData = $incomeStatementService->generate($fiscalPeriodId);
        $netProfit = (float)$incomeData['net_profit'];

        // Tambahkan baris Laba Bersih secara dinamis ke koleksi Ekuitas
        if (abs($netProfit) > 0.01) {
            $equity->push([
                'code' => '-',
                'name' => 'Laba Bersih Periode Berjalan',
                'type' => 'Ekuitas',
                'normal_balance' => 'Kredit',
                'balance' => $netProfit,
            ]);
        }

        $totalEquity = $equity->sum('balance');

        $totalLiabilitiesAndEquity = $totalLiabilities + $totalEquity;
        $isBalanced = abs($totalAssets - $totalLiabilitiesAndEquity) < 0.01;

        return [
            'period' => $period,
            // Assets
            'current_assets' => $currentAssets,
            'total_current_assets' => round($totalCurrentAssets, 2),
            'fixed_assets' => $fixedAssets,
            'total_fixed_assets' => round($totalFixedAssets, 2),
            'total_assets' => round($totalAssets, 2),
            // Liabilities
            'current_liabilities' => $currentLiabilities,
            'total_current_liabilities' => round($totalCurrentLiabilities, 2),
            'long_term_liabilities' => $longTermLiabilities,
            'total_long_term_liabilities' => round($totalLongTermLiabilities, 2),
            'total_liabilities' => round($totalLiabilities, 2),
            // Equity
            'equity' => $equity,
            'total_equity' => round($totalEquity, 2),
            // Totals
            'total_liabilities_and_equity' => round($totalLiabilitiesAndEquity, 2),
            'is_balanced' => $isBalanced,
        ];
    }

    /**
     * Get leaf accounts by code prefix with calculated balances.
     * $category: 'Aset' (Debit - Credit) atau 'Kewajiban'/'Ekuitas' (Credit - Debit)
     */
    private function getAccountsByPrefix(string $prefix, int $fiscalPeriodId, string $category)
    {
        return Account::active()
            ->where('code', 'like', $prefix . '%')
            ->whereNotNull('parent_id')
            ->orderBy('code')
            ->get()
            ->map(function (Account $account) use ($fiscalPeriodId, $category) {
                $raw = $account->getRawTotalsForPeriod($fiscalPeriodId);
                
                // Jika Aset, saldo = debit - credit.
                // Jika Kewajiban/Ekuitas, saldo = credit - debit.
                $balance = $category === 'Aset' 
                    ? ($raw['debit'] - $raw['credit'])
                    : ($raw['credit'] - $raw['debit']);

                return [
                    'code' => $account->code,
                    'name' => $account->name,
                    'type' => $account->type,
                    'normal_balance' => $account->normal_balance,
                    'balance' => $balance,
                ];
            })
            ->filter(fn ($row) => abs($row['balance']) > 0.01)
            ->values();
    }
}
