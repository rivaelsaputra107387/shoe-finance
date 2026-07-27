<?php

namespace App\Services;

use App\Models\Account;
use App\Models\FiscalPeriod;
use Illuminate\Support\Facades\DB;

class TrialBalanceService
{
    /**
     * Generate trial balance (neraca lajur) for a given fiscal period.
     * Uses a SINGLE bulk query — no N+1.
     *
     * @return array ['accounts' => Collection, 'total_debit' => float, 'total_credit' => float, 'is_balanced' => bool]
     */
    public function generate(int $fiscalPeriodId): array
    {
        $period = FiscalPeriod::findOrFail($fiscalPeriodId);

        // ONE bulk query: cumulative totals for all accounts up to end of period
        $balanceSvc = new AccountBalanceService();
        $totals = $balanceSvc->getCumulativeTotalsUpTo($fiscalPeriodId);

        // Load all active leaf accounts (no individual DB calls inside loop)
        $accounts = Account::active()
            ->whereNotNull('parent_id')
            ->orderBy('code')
            ->get()
            ->map(function (Account $account) use ($totals) {
                $row    = $totals->get($account->id);
                $debit  = $row ? (float) $row->total_debit  : 0.0;
                $credit = $row ? (float) $row->total_credit : 0.0;
                $net    = $debit - $credit;

                return [
                    'id'             => $account->id,
                    'code'           => $account->code,
                    'name'           => $account->name,
                    'type'           => $account->type,
                    'normal_balance' => $account->normal_balance,
                    'debit'          => $net > 0 ? $net  : 0,
                    'credit'         => $net < 0 ? abs($net) : 0,
                ];
            })
            ->filter(fn ($row) => $row['debit'] > 0 || $row['credit'] > 0);

        $totalDebit  = $accounts->sum('debit');
        $totalCredit = $accounts->sum('credit');

        return [
            'accounts'    => $accounts->values(),
            'total_debit' => round($totalDebit, 2),
            'total_credit'=> round($totalCredit, 2),
            'is_balanced' => abs($totalDebit - $totalCredit) < 0.01,
            'period'      => $period,
        ];
    }
}
