<?php

namespace App\Services;

use App\Models\FiscalPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * Central service for fetching account balances efficiently.
 * ALL methods use a SINGLE bulk SQL query instead of N+1 per-account queries.
 */
class AccountBalanceService
{
    /**
     * Get cumulative debit/credit totals for ALL accounts up to the end of a fiscal period.
     * Returns a Collection keyed by account_id.
     * ONE query for all accounts — no N+1.
     */
    public function getCumulativeTotalsUpTo(int $fiscalPeriodId): Collection
    {
        $period = FiscalPeriod::findOrFail($fiscalPeriodId);

        $rows = DB::table('journal_entry_lines as jel')
            ->join('journal_entries as je', 'jel.journal_entry_id', '=', 'je.id')
            ->where('je.entry_date', '<=', $period->end_date)
            ->where('je.status', 'posted')
            ->whereNull('je.deleted_at')
            ->groupBy('jel.account_id')
            ->select(
                'jel.account_id',
                DB::raw('COALESCE(SUM(jel.debit), 0) as total_debit'),
                DB::raw('COALESCE(SUM(jel.credit), 0) as total_credit')
            )
            ->get()
            ->keyBy('account_id');

        return $rows;
    }

    /**
     * Get cumulative debit/credit totals for ALL accounts BEFORE the start of a fiscal period.
     * ONE query for all accounts — no N+1.
     */
    public function getCumulativeTotalsBefore(int $fiscalPeriodId): Collection
    {
        $period = FiscalPeriod::findOrFail($fiscalPeriodId);

        $rows = DB::table('journal_entry_lines as jel')
            ->join('journal_entries as je', 'jel.journal_entry_id', '=', 'je.id')
            ->where('je.entry_date', '<', $period->start_date)
            ->where('je.status', 'posted')
            ->whereNull('je.deleted_at')
            ->groupBy('jel.account_id')
            ->select(
                'jel.account_id',
                DB::raw('COALESCE(SUM(jel.debit), 0) as total_debit'),
                DB::raw('COALESCE(SUM(jel.credit), 0) as total_credit')
            )
            ->get()
            ->keyBy('account_id');

        return $rows;
    }

    /**
     * Get period-only debit/credit totals for ALL accounts (for Income Statement).
     * Optionally exclude closing entries.
     * ONE query for all accounts — no N+1.
     */
    public function getPeriodTotals(int $fiscalPeriodId, bool $excludeClosing = false): Collection
    {
        $query = DB::table('journal_entry_lines as jel')
            ->join('journal_entries as je', 'jel.journal_entry_id', '=', 'je.id')
            ->where('je.fiscal_period_id', $fiscalPeriodId)
            ->where('je.status', 'posted')
            ->whereNull('je.deleted_at');

        if ($excludeClosing) {
            $query->where('je.is_closing', false);
        }

        return $query
            ->groupBy('jel.account_id')
            ->select(
                'jel.account_id',
                DB::raw('COALESCE(SUM(jel.debit), 0) as total_debit'),
                DB::raw('COALESCE(SUM(jel.credit), 0) as total_credit')
            )
            ->get()
            ->keyBy('account_id');
    }

    /**
     * Helper: get balance for a single account_id from a pre-fetched totals collection.
     */
    public function getBalance(Collection $totals, int $accountId, string $normalBalance): float
    {
        $row = $totals->get($accountId);
        if (!$row) return 0.0;

        $debit  = (float) $row->total_debit;
        $credit = (float) $row->total_credit;

        return $normalBalance === 'Debet'
            ? ($debit - $credit)
            : ($credit - $debit);
    }

    /**
     * Helper: get raw debit & credit for a single account_id from pre-fetched totals.
     */
    public function getRaw(Collection $totals, int $accountId): array
    {
        $row = $totals->get($accountId);
        return [
            'debit'  => $row ? (float) $row->total_debit  : 0.0,
            'credit' => $row ? (float) $row->total_credit : 0.0,
        ];
    }
}
