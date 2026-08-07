<?php

namespace App\Services;

use App\Models\Account;
use App\Models\FiscalPeriod;

class EquityStatementService
{
    /**
     * Generate Statement of Changes in Equity (Laporan Perubahan Ekuitas).
     * Uses bulk queries via AccountBalanceService — no N+1.
     *
     * Structure:
     * Modal Awal Periode       (hanya akun 3110 + 3300, TIDAK termasuk Prive 3120 & Ikhtisar LR 3200)
     * + Laba/Rugi Bersih Periode Berjalan
     * + Setoran Modal Tambahan (3110 selama periode ini)
     * - Prive (3120)
     * = Modal Akhir Periode
     *
     * PERBAIKAN:
     * 1. Modal Awal sekarang hanya menghitung akun 3110 (Modal Disetor) dan 3300 (Laba Ditahan).
     *    Akun 3120 (Prive) dan 3200 (Ikhtisar LR) dikecualikan dari Modal Awal.
     * 2. retainedEarnings (3300) sekarang dimasukkan ke formula Modal Akhir jika Laba Ditahan
     *    dikelola via akun terpisah dari 3110.
     */
    public function generate(int $fiscalPeriodId): array
    {
        $period = FiscalPeriod::findOrFail($fiscalPeriodId);

        $balanceSvc = new AccountBalanceService();

        // Get net profit from Income Statement (period-only, excluding closing)
        $incomeStatement = (new IncomeStatementService())->generate($fiscalPeriodId);
        $netProfit = $incomeStatement['net_profit'];

        // ONE bulk query: cumulative totals BEFORE this period (for beginning capital)
        $beforeTotals = $balanceSvc->getCumulativeTotalsBefore($fiscalPeriodId);

        // ── Modal Awal ──
        // Hanya akun Modal Disetor (3110) dan Laba Ditahan (3300) sebelum periode ini.
        // Kecualikan 3120 (Prive) dan 3200 (Ikhtisar LR) — keduanya bukan komponen Modal Awal.
        $modalAccounts = Account::active()
            ->whereIn('code', ['3110', '3300'])
            ->get();

        $beginningCapital = 0.0;
        foreach ($modalAccounts as $account) {
            $row    = $beforeTotals->get($account->id);
            $debit  = $row ? (float) $row->total_debit  : 0.0;
            $credit = $row ? (float) $row->total_credit : 0.0;
            $beginningCapital += ($credit - $debit); // Ekuitas: Kredit normal
        }

        // ONE bulk query: period-only totals (excluding closing entries)
        $periodTotals = $balanceSvc->getPeriodTotals($fiscalPeriodId, excludeClosing: true);

        // ── Setoran Modal Tambahan (3110) selama periode ini saja ──
        $modalAccount      = Account::where('code', '3110')->first();
        $modalRow          = $modalAccount ? $periodTotals->get($modalAccount->id) : null;
        $additionalCapital = $modalRow
            ? ((float) $modalRow->total_credit - (float) $modalRow->total_debit)
            : 0.0;

        // ── Prive (3120) selama periode ini saja ──
        $priveAccount = Account::where('code', '3120')->first();
        $priveRow     = $priveAccount ? $periodTotals->get($priveAccount->id) : null;
        $priveAmount  = $priveRow
            ? ((float) $priveRow->total_debit - (float) $priveRow->total_credit)
            : 0.0;

        // ── Laba Ditahan dari periode sebelumnya (3300) ──
        // Ini sudah termasuk dalam $beginningCapital di atas (via whereIn ['3110','3300']).
        // Diekstrak terpisah hanya untuk ditampilkan di UI.
        $retainedAccount  = Account::where('code', '3300')->first();
        $retainedRow      = $retainedAccount ? $beforeTotals->get($retainedAccount->id) : null;
        $retainedEarnings = $retainedRow
            ? ((float) $retainedRow->total_credit - (float) $retainedRow->total_debit)
            : 0.0;

        // ── Modal Akhir ──
        // Modal Awal sudah mencakup 3110 + 3300 dari periode sebelumnya.
        // + Laba Bersih periode ini (belum masuk ke 3110/3300 sampai closing)
        // + Setoran tambahan 3110 periode ini
        // - Prive periode ini
        $endingCapital = $beginningCapital + $netProfit + $additionalCapital - $priveAmount;

        return [
            'period'             => $period,
            'beginning_capital'  => round($beginningCapital, 2),
            'additional_capital' => round($additionalCapital, 2),
            'net_profit'         => round($netProfit, 2),
            'prive'              => round($priveAmount, 2),
            'retained_earnings'  => round($retainedEarnings, 2),
            'ending_capital'     => round($endingCapital, 2),
            'modal_account_name' => $modalAccount?->name ?? 'Modal Disetor',
            'prive_account_name' => $priveAccount?->name ?? 'Prive',
        ];
    }
}
