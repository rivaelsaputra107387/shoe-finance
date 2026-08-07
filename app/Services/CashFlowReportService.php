<?php

namespace App\Services;

use App\Models\Account;
use App\Models\FiscalPeriod;
use App\Models\JournalEntryLine;
use Illuminate\Support\Facades\DB;

class CashFlowReportService
{
    /**
     * Generate Cash Flow Statement (Laporan Arus Kas).
     *
     * Uses DIRECT method: classifies cash movements by analyzing
     * counter-accounts in journal entries involving cash/bank accounts.
     * Each counter-account receives a cash flow amount proportional to
     * its own value relative to total counter-amount (not divided equally).
     *
     * Structure:
     * - Arus Kas dari Aktivitas Operasi
     * - Arus Kas dari Aktivitas Investasi
     * - Arus Kas dari Aktivitas Pendanaan
     * - Kenaikan/Penurunan Kas Bersih
     * - Saldo Kas Awal + Kenaikan = Saldo Kas Akhir
     */
    public function generate(int $fiscalPeriodId): array
    {
        $period = FiscalPeriod::findOrFail($fiscalPeriodId);

        // Identify Cash & Bank accounts (1110, 1111, 1120, 1121)
        $cashAccountIds = Account::where(function ($q) {
            $q->where('code', 'like', '111%')
              ->orWhere('code', 'like', '112%');
        })->pluck('id')->toArray();

        if (empty($cashAccountIds)) {
            return $this->emptyResult($period);
        }

        // Get all journal entry lines involving cash/bank accounts
        $cashLines = JournalEntryLine::query()
            ->whereIn('account_id', $cashAccountIds)
            ->whereHas('journalEntry', function ($q) use ($fiscalPeriodId) {
                $q->where('fiscal_period_id', $fiscalPeriodId)
                  ->where('status', 'posted')
                  ->whereNull('deleted_at');
            })
            ->with(['journalEntry.lines.account'])
            ->get();

        $operating = [];
        $investing  = [];
        $financing  = [];

        // Track processed journal entry IDs to avoid double-counting
        // when multiple cash accounts appear in the same journal entry
        $processedEntries = [];

        foreach ($cashLines as $cashLine) {
            $journalEntry = $cashLine->journalEntry;

            // Skip if we've already processed this journal entry
            if (in_array($journalEntry->id, $processedEntries)) {
                continue;
            }
            $processedEntries[] = $journalEntry->id;

            // Total net cash movement in this journal entry
            // (sum of all cash/bank lines in the entry)
            $totalCashAmount = 0.0;
            foreach ($journalEntry->lines as $line) {
                if (in_array($line->account_id, $cashAccountIds)) {
                    $totalCashAmount += (float) $line->debit - (float) $line->credit;
                }
            }

            // Skip if no net cash movement (e.g. transfer within same account)
            if (abs($totalCashAmount) < 0.01) {
                continue;
            }

            // Find counter-accounts (non-cash accounts in the same journal entry)
            $counterLines = $journalEntry->lines
                ->filter(fn ($line) => !in_array($line->account_id, $cashAccountIds));

            // If all lines are cash accounts (inter-bank transfer), skip
            if ($counterLines->isEmpty()) {
                continue;
            }

            // Calculate total absolute counter-amount for proportional allocation
            $totalCounterAbs = $counterLines->sum(fn ($line) =>
                abs((float) $line->debit - (float) $line->credit)
            );

            foreach ($counterLines as $counterLine) {
                $counterAccount = $counterLine->account;
                if (!$counterAccount) continue;

                $category = $counterAccount->cash_flow_category
                    ?? $this->inferCategory($counterAccount);

                $counterAmount = abs((float) $counterLine->debit - (float) $counterLine->credit);

                // Allocate cash proportionally based on this counter-line's value
                // FIX: was dividing by count(), now using proportional value
                $proportion = $totalCounterAbs > 0.01
                    ? $counterAmount / $totalCounterAbs
                    : (1.0 / max(1, $counterLines->count()));

                $flowAmount = $totalCashAmount * $proportion;

                $item = [
                    'description'  => $journalEntry->description,
                    'account_code' => $counterAccount->code,
                    'account_name' => $counterAccount->name,
                    'amount'       => $flowAmount,
                    'date'         => $journalEntry->entry_date->format('d/m/Y'),
                ];

                match ($category) {
                    'Operasi'   => $operating[]  = $item,
                    'Investasi' => $investing[]  = $item,
                    'Pendanaan' => $financing[]  = $item,
                    default     => $operating[]  = $item,
                };
            }
        }

        // Aggregate by account
        $operatingGrouped = $this->aggregateByAccount($operating);
        $investingGrouped  = $this->aggregateByAccount($investing);
        $financingGrouped  = $this->aggregateByAccount($financing);

        $totalOperating = collect($operatingGrouped)->sum('amount');
        $totalInvesting  = collect($investingGrouped)->sum('amount');
        $totalFinancing  = collect($financingGrouped)->sum('amount');

        $netIncrease = $totalOperating + $totalInvesting + $totalFinancing;

        // Calculate beginning cash balance (cumulative cash before this period)
        $beginningCashBalance = $this->getBeginningCashBalance($cashAccountIds, $period);
        $endingCashBalance    = $beginningCashBalance + $netIncrease;

        // Actual ending balance from ledger for validation
        // Uses CUMULATIVE balance (all entries up to period end).
        $actualEndingBalance = (float) JournalEntryLine::query()
            ->whereIn('account_id', $cashAccountIds)
            ->whereHas('journalEntry', function ($q) use ($period) {
                $q->whereDate('entry_date', '<=', $period->end_date)
                  ->where('status', 'posted')
                  ->whereNull('deleted_at');
            })
            ->selectRaw('COALESCE(SUM(debit), 0) - COALESCE(SUM(credit), 0) as balance')
            ->value('balance');

        return [
            'period'              => $period,
            'operating'           => $operatingGrouped,
            'total_operating'     => round($totalOperating, 2),
            'investing'           => $investingGrouped,
            'total_investing'     => round($totalInvesting, 2),
            'financing'           => $financingGrouped,
            'total_financing'     => round($totalFinancing, 2),
            'net_increase'        => round($netIncrease, 2),
            'beginning_cash'      => round($beginningCashBalance, 2),
            'ending_cash'         => round($endingCashBalance, 2),
            'actual_ending_cash'  => round($actualEndingBalance, 2),
            'is_valid'            => abs($endingCashBalance - $actualEndingBalance) < 0.01,
        ];
    }

    /**
     * Infer cash flow category from account type when no explicit mapping exists.
     */
    private function inferCategory(Account $account): string
    {
        return match (true) {
            in_array($account->type, ['Pendapatan', 'Beban'])                          => 'Operasi',
            $account->type === 'Aset' && str_starts_with($account->code, '12')        => 'Investasi',
            $account->type === 'Aset'                                                  => 'Operasi',
            $account->type === 'Kewajiban' && str_starts_with($account->code, '22')   => 'Pendanaan',
            $account->type === 'Kewajiban'                                             => 'Operasi',
            $account->type === 'Ekuitas'                                               => 'Pendanaan',
            default                                                                    => 'Operasi',
        };
    }

    /**
     * Aggregate cash flow items by account (combine same-account transactions).
     */
    private function aggregateByAccount(array $items): array
    {
        $grouped = collect($items)->groupBy('account_code');

        return $grouped->map(function ($group, $code) {
            return [
                'account_code' => $code,
                'account_name' => $group->first()['account_name'],
                'amount'       => round($group->sum('amount'), 2),
                'description'  => $group->pluck('description')->unique()->implode(', '),
            ];
        })->values()->toArray();
    }

    /**
     * Get beginning cash balance for the period.
     * Cumulative sum of cash-related journal entries BEFORE this period.
     */
    private function getBeginningCashBalance(array $cashAccountIds, FiscalPeriod $period): float
    {
        $balance = JournalEntryLine::query()
            ->whereIn('account_id', $cashAccountIds)
            ->whereHas('journalEntry', function ($q) use ($period) {
                $q->where('entry_date', '<', $period->start_date)
                  ->where('status', 'posted')
                  ->whereNull('deleted_at');
            })
            ->selectRaw('COALESCE(SUM(debit), 0) - COALESCE(SUM(credit), 0) as balance')
            ->value('balance');

        return (float) $balance;
    }

    /**
     * Return empty result structure.
     */
    private function emptyResult(FiscalPeriod $period): array
    {
        return [
            'period'             => $period,
            'operating'          => [],
            'total_operating'    => 0,
            'investing'          => [],
            'total_investing'    => 0,
            'financing'          => [],
            'total_financing'    => 0,
            'net_increase'       => 0,
            'beginning_cash'     => 0,
            'ending_cash'        => 0,
            'actual_ending_cash' => 0,
            'is_valid'           => true,
        ];
    }
}
