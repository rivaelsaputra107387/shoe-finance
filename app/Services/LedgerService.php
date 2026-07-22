<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntryLine;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LedgerService
{
    /**
     * Get ledger (buku besar) entries for a specific account within a date range.
     *
     * @return Collection of ['date', 'reference', 'description', 'debit', 'credit', 'balance']
     */
    public function getLedger(int $accountId, ?string $startDate = null, ?string $endDate = null): Collection
    {
        $account = Account::findOrFail($accountId);

        $query = JournalEntryLine::query()
            ->where('journal_entry_lines.account_id', $accountId)
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('journal_entries.status', 'posted')
            ->whereNull('journal_entries.deleted_at')
            ->select([
                'journal_entries.entry_date as date',
                'journal_entries.reference',
                'journal_entries.description',
                'journal_entry_lines.debit',
                'journal_entry_lines.credit',
            ])
            ->orderBy('journal_entries.entry_date')
            ->orderBy('journal_entries.id');

        if ($startDate) {
            $query->where('journal_entries.entry_date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('journal_entries.entry_date', '<=', $endDate);
        }

        $entries = $query->get();

        // Calculate running balance
        $runningBalance = 0;
        $isDebitNormal = $account->normal_balance === 'Debet';

        return $entries->map(function ($entry) use (&$runningBalance, $isDebitNormal) {
            $debit = (float) $entry->debit;
            $credit = (float) $entry->credit;

            if ($isDebitNormal) {
                $runningBalance += ($debit - $credit);
            } else {
                $runningBalance += ($credit - $debit);
            }

            return [
                'date' => $entry->date,
                'reference' => $entry->reference,
                'description' => $entry->description,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $runningBalance,
            ];
        });
    }
}
