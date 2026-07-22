<?php

namespace App\Services;

use App\Models\Account;
use App\Models\FiscalPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TrialBalanceService
{
    /**
     * Generate trial balance (neraca lajur) for a given fiscal period.
     *
     * @return array ['accounts' => Collection, 'total_debit' => float, 'total_credit' => float, 'is_balanced' => bool]
     */
    public function generate(int $fiscalPeriodId): array
    {
        $period = FiscalPeriod::findOrFail($fiscalPeriodId);

        // Get all active accounts with their balances
        $accounts = Account::active()
            ->orderBy('code')
            ->get()
            ->map(function (Account $account) use ($fiscalPeriodId) {
                $trialBalance = $account->getTrialBalanceForPeriod($fiscalPeriodId);

                return [
                    'id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'type' => $account->type,
                    'normal_balance' => $account->normal_balance,
                    'debit' => $trialBalance['debit'],
                    'credit' => $trialBalance['credit'],
                ];
            })
            ->filter(fn ($row) => $row['debit'] > 0 || $row['credit'] > 0); // Only accounts with balance

        $totalDebit = $accounts->sum('debit');
        $totalCredit = $accounts->sum('credit');

        return [
            'accounts' => $accounts->values(),
            'total_debit' => round($totalDebit, 2),
            'total_credit' => round($totalCredit, 2),
            'is_balanced' => abs($totalDebit - $totalCredit) < 0.01,
            'period' => $period,
        ];
    }
}
