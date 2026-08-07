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
     * Left side:  Aset (Lancar + Tetap)
     * Right side: Kewajiban (Lancar + Jangka Panjang) + Ekuitas
     * Must balance: Total Aset = Total Kewajiban + Total Ekuitas
     *
     * Laba Bersih Berjalan:
     * - Untuk periode AKTIF: dihitung dari akun 4xxx/5xxx/6xxx/7xxx/8xxx
     *   menggunakan period-only totals (excludeClosing=true), karena akun-akun
     *   tersebut belum ditutup ke Modal.
     * - Untuk periode TERTUTUP: akun pendapatan/beban sudah nol (sudah di-close),
     *   sehingga cumulativeNetProfit otomatis = 0 dan tidak perlu ditambahkan.
     *
     * Ekuitas hanya menampilkan akun Modal (3110) dan Laba Ditahan (3300),
     * TIDAK termasuk Prive (3120) dan Ikhtisar LR (3200).
     * Prive ditampilkan sebagai baris pengurang terpisah.
     */
    public function generate(int $fiscalPeriodId): array
    {
        $period = FiscalPeriod::findOrFail($fiscalPeriodId);

        $balanceSvc = new AccountBalanceService();

        // ONE bulk query: cumulative totals for all accounts up to end of period
        $cumulativeTotals = $balanceSvc->getCumulativeTotalsUpTo($fiscalPeriodId);

        // ── ASET ──
        $currentAssets      = $this->getAccountsByPrefix('11', $cumulativeTotals, 'Aset');
        $fixedAssets        = $this->getAccountsByPrefix('12', $cumulativeTotals, 'Aset');
        $totalCurrentAssets = $currentAssets->sum('balance');
        $totalFixedAssets   = $fixedAssets->sum('balance');
        $totalAssets        = $totalCurrentAssets + $totalFixedAssets;

        // ── KEWAJIBAN ──
        $currentLiabilities       = $this->getAccountsByPrefix('21', $cumulativeTotals, 'Kewajiban');
        $longTermLiabilities      = $this->getAccountsByPrefix('22', $cumulativeTotals, 'Kewajiban');
        $totalCurrentLiabilities  = $currentLiabilities->sum('balance');
        $totalLongTermLiabilities = $longTermLiabilities->sum('balance');
        $totalLiabilities         = $totalCurrentLiabilities + $totalLongTermLiabilities;

        // ── EKUITAS ──
        // Hanya ambil akun Modal (3110) dan Laba Ditahan (3300).
        // Kecualikan: Prive (3120) dan Ikhtisar LR (3200).
        $equityAccounts = $this->getEquityAccounts($cumulativeTotals);

        // ── LABA BERSIH (YANG BELUM DITUTUP) ──
        // Menggunakan getCumulativeTotalsUpTo ($cumulativeTotals) BUKAN getPeriodTotals.
        // Kenapa? Karena Jurnal Penutup sudah mengurangi saldo di $cumulativeTotals.
        // Jadi, sisa saldo di akun 4xxx-8xxx dalam $cumulativeTotals adalah murni 
        // "Laba Bersih yang Belum Ditutup" (misal ada transaksi yang telat diposting 
        // setelah tutup buku). Ini WAJIB dimasukkan ke Ekuitas agar neraca seimbang.
        
        $revenuePrefixes = ['4', '71'];
        $expensePrefixes = ['5', '6', '72', '8'];

        $cumulativeRevenue = Account::active()
            ->where(function ($q) use ($revenuePrefixes) {
                foreach ($revenuePrefixes as $pfx) {
                    $q->orWhere('code', 'like', $pfx . '%');
                }
            })
            ->whereNotNull('parent_id')
            ->get()
            ->sum(fn ($a) => $balanceSvc->getBalance($cumulativeTotals, $a->id, 'Kredit'));

        $cumulativeExpenses = Account::active()
            ->where(function ($q) use ($expensePrefixes) {
                foreach ($expensePrefixes as $pfx) {
                    $q->orWhere('code', 'like', $pfx . '%');
                }
            })
            ->whereNotNull('parent_id')
            ->get()
            ->sum(fn ($a) => $balanceSvc->getBalance($cumulativeTotals, $a->id, 'Debet'));

        $unclosedNetProfit = $cumulativeRevenue - $cumulativeExpenses;

        // Prive (3120) — pengurang modal, tampilkan terpisah
        $priveAccount = Account::where('code', '3120')->first();
        $priveBalance = 0.0;
        if ($priveAccount) {
            $row = $cumulativeTotals->get($priveAccount->id);
            if ($row) {
                // Prive normal balance = Debet, jadi nilai positif = pengurang modal
                $priveBalance = (float) $row->total_debit - (float) $row->total_credit;
            }
        }

        // Tambahkan Laba Bersih Belum Ditutup ke koleksi ekuitas (jika ada sisa)
        if (abs($unclosedNetProfit) > 0.01) {
            $equityAccounts->push([
                'code'           => '-',
                'name'           => 'Laba Bersih (Belum Ditutup)',
                'type'           => 'Ekuitas',
                'normal_balance' => 'Kredit',
                'balance'        => $unclosedNetProfit,
            ]);
        }

        // Prive sebagai pengurang (nilai negatif di ekuitas)
        if (abs($priveBalance) > 0.01) {
            $equityAccounts->push([
                'code'           => '3120',
                'name'           => $priveAccount->name ?? 'Prive',
                'type'           => 'Ekuitas',
                'normal_balance' => 'Debet',
                'balance'        => -$priveBalance, // negatif = pengurang
            ]);
        }

        $totalEquity               = $equityAccounts->sum('balance');
        $totalLiabilitiesAndEquity = $totalLiabilities + $totalEquity;
        $isBalanced                = abs($totalAssets - $totalLiabilitiesAndEquity) < 0.01;

        return [
            'period'                       => $period,
            'current_assets'               => $currentAssets,
            'total_current_assets'         => round($totalCurrentAssets, 2),
            'fixed_assets'                 => $fixedAssets,
            'total_fixed_assets'           => round($totalFixedAssets, 2),
            'total_assets'                 => round($totalAssets, 2),
            'current_liabilities'          => $currentLiabilities,
            'total_current_liabilities'    => round($totalCurrentLiabilities, 2),
            'long_term_liabilities'        => $longTermLiabilities,
            'total_long_term_liabilities'  => round($totalLongTermLiabilities, 2),
            'total_liabilities'            => round($totalLiabilities, 2),
            'equity'                       => $equityAccounts,
            'total_equity'                 => round($totalEquity, 2),
            'total_liabilities_and_equity' => round($totalLiabilitiesAndEquity, 2),
            'is_balanced'                  => $isBalanced,
            'current_net_profit'           => round($unclosedNetProfit, 2),
        ];
    }

    /**
     * Get equity accounts: ONLY Modal (3110) and Laba Ditahan (3300).
     * Excludes Prive (3120) and Ikhtisar LR (3200).
     */
    private function getEquityAccounts($totals)
    {
        return Account::active()
            ->whereIn('code', ['3110', '3300'])
            ->orderBy('code')
            ->get()
            ->map(function (Account $account) use ($totals) {
                $row    = $totals->get($account->id);
                $debit  = $row ? (float) $row->total_debit  : 0.0;
                $credit = $row ? (float) $row->total_credit : 0.0;

                // Ekuitas: Kredit normal
                $balance = $credit - $debit;

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
