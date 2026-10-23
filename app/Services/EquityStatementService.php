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
        // Modal Accounts (3110 Modal Disetor & 3300 Laba Ditahan)
        $modalAccounts = Account::active()
            ->whereIn('code', ['3110', '3300'])
            ->get();

        $modalAccountIds = $modalAccounts->pluck('id')->toArray();

        // 1. Beginning Capital from periods BEFORE this period
        $beginningCapital = 0.0;
        foreach ($modalAccounts as $account) {
            $row    = $beforeTotals->get($account->id);
            $debit  = $row ? (float) $row->total_debit  : 0.0;
            $credit = $row ? (float) $row->total_credit : 0.0;
            $beginningCapital += ($credit - $debit); // Ekuitas: Kredit normal
        }

        // 2. Include any Opening Balance Journal (is_opening = true) in the current period into beginning capital
        $openingModalLines = \App\Models\JournalEntryLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('journal_entries.fiscal_period_id', $fiscalPeriodId)
            ->where('journal_entries.is_opening', true)
            ->where('journal_entries.status', 'posted')
            ->whereNull('journal_entries.deleted_at')
            ->whereIn('journal_entry_lines.account_id', $modalAccountIds)
            ->selectRaw('journal_entry_lines.account_id, COALESCE(SUM(journal_entry_lines.credit), 0) - COALESCE(SUM(journal_entry_lines.debit), 0) as balance')
            ->groupBy('journal_entry_lines.account_id')
            ->pluck('balance', 'account_id');

        $openingModalAmount = 0.0;
        foreach ($modalAccounts as $account) {
            $openingModalAmount += (float) ($openingModalLines[$account->id] ?? 0);
        }
        $beginningCapital += $openingModalAmount;

        // ONE bulk query: period-only totals (excluding closing and opening entries)
        $periodTotals = $balanceSvc->getPeriodTotals($fiscalPeriodId, excludeClosing: true);

        // ── Setoran Modal Tambahan (3110) selama periode ini saja (murni transaksi baru, bukan saldo awal) ──
        $modalAccount      = Account::where('code', '3110')->first();
        $additionalCapital = 0.0;
        if ($modalAccount) {
            $additionalCapital = (float) \App\Models\JournalEntryLine::query()
                ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
                ->where('journal_entries.fiscal_period_id', $fiscalPeriodId)
                ->where('journal_entries.status', 'posted')
                ->where('journal_entries.is_closing', false)
                ->where('journal_entries.is_opening', false)
                ->whereNull('journal_entries.deleted_at')
                ->where('journal_entry_lines.account_id', $modalAccount->id)
                ->selectRaw('COALESCE(SUM(journal_entry_lines.credit), 0) - COALESCE(SUM(journal_entry_lines.debit), 0) as balance')
                ->value('balance') ?? 0.0;
        }

        // ── Prive (3120) selama periode ini saja ──
        $priveAccount = Account::where('code', '3120')->first();
        $priveRow     = $priveAccount ? $periodTotals->get($priveAccount->id) : null;
        $priveAmount  = $priveRow
            ? ((float) $priveRow->total_debit - (float) $priveRow->total_credit)
            : 0.0;

        // ── Laba Ditahan (3300) ──
        $retainedAccount  = Account::where('code', '3300')->first();
        $retainedRow      = $retainedAccount ? $beforeTotals->get($retainedAccount->id) : null;
        $beforeRetained   = $retainedRow
            ? ((float) $retainedRow->total_credit - (float) $retainedRow->total_debit)
            : 0.0;
        $openingRetained  = $retainedAccount ? (float) ($openingModalLines[$retainedAccount->id] ?? 0) : 0.0;
        $retainedEarnings = $beforeRetained + $openingRetained;

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
