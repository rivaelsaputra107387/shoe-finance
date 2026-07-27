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
     * Modal Awal Periode
     * + Laba/Rugi Bersih Periode Berjalan
     * - Prive (jika ada)
     * = Modal Akhir Periode
     */
    public function generate(int $fiscalPeriodId): array
    {
        $period = FiscalPeriod::findOrFail($fiscalPeriodId);

        $balanceSvc = new AccountBalanceService();

        // Get net profit from Income Statement (already uses bulk query internally)
        $incomeStatement = (new IncomeStatementService())->generate($fiscalPeriodId);
        $netProfit = $incomeStatement['net_profit'];

        // ONE bulk query: cumulative totals BEFORE this period (for beginning capital)
        $beforeTotals = $balanceSvc->getCumulativeTotalsBefore($fiscalPeriodId);

        // Modal Awal = total semua akun ekuitas (3xxx) SEBELUM periode ini
        $equityAccounts   = Account::active()->where('code', 'like', '3%')->get();
        $beginningCapital = 0;
        foreach ($equityAccounts as $account) {
            $row = $beforeTotals->get($account->id);
            $debit  = $row ? (float) $row->total_debit  : 0.0;
            $credit = $row ? (float) $row->total_credit : 0.0;
            $beginningCapital += ($credit - $debit); // Ekuitas: Kredit normal
        }

        // ONE bulk query: period-only totals (excluding closing entries)
        $periodTotals = $balanceSvc->getPeriodTotals($fiscalPeriodId, excludeClosing: true);

        // Setoran Modal Tambahan (3110) selama periode ini saja
        $modalAccount = Account::where('code', '3110')->first();
        $modalRow = $modalAccount ? $periodTotals->get($modalAccount->id) : null;
        $additionalCapital = $modalRow
            ? ((float) $modalRow->total_credit - (float) $modalRow->total_debit)
            : 0.0;

        // Prive (3120) selama periode ini saja
        $priveAccount = Account::where('code', '3120')->first();
        $priveRow = $priveAccount ? $periodTotals->get($priveAccount->id) : null;
        $priveAmount = $priveRow
            ? ((float) $priveRow->total_debit - (float) $priveRow->total_credit)
            : 0.0;

        // Modal Akhir = Modal Awal + Laba Bersih + Setoran Tambahan - Prive
        $endingCapital = $beginningCapital + $netProfit + $additionalCapital - $priveAmount;

        // Retained earnings account (3300)
        $retainedAccount = Account::where('code', '3300')->first();
        $retainedRow = $retainedAccount ? $beforeTotals->get($retainedAccount->id) : null;
        $retainedEarnings = $retainedRow
            ? ((float) $retainedRow->total_credit - (float) $retainedRow->total_debit)
            : 0.0;

        return [
            'period'            => $period,
            'beginning_capital' => round($beginningCapital, 2),
            'additional_capital'=> round($additionalCapital, 2),
            'net_profit'        => round($netProfit, 2),
            'prive'             => round($priveAmount, 2),
            'retained_earnings' => round($retainedEarnings, 2),
            'ending_capital'    => round($endingCapital, 2),
            'modal_account_name'=> $modalAccount?->name ?? 'Modal Disetor',
            'prive_account_name'=> $priveAccount?->name ?? 'Prive',
        ];
    }
}
