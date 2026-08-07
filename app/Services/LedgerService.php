<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntryLine;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LedgerService
{
    /**
     * Generate ledger (buku besar) data for a specific account in a fiscal period.
     *
     * @return array ['account' => array, 'beginning_balance' => float, 'transactions' => array]
     */
    public function generateForAccount(int $accountId, int $fiscalPeriodId): array
    {
        $account = Account::findOrFail($accountId);

        $balanceSvc = new AccountBalanceService();
        $totalsBefore = $balanceSvc->getCumulativeTotalsBefore($fiscalPeriodId);
        $beginningBalance = $balanceSvc->getBalance($totalsBefore, $account->id, $account->normal_balance);

        $lines = JournalEntryLine::query()
            ->where('journal_entry_lines.account_id', $accountId)
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('journal_entries.fiscal_period_id', $fiscalPeriodId)
            ->where('journal_entries.status', 'posted')
            ->whereNull('journal_entries.deleted_at')
            ->select([
                'journal_entries.entry_date as date',
                'journal_entries.reference',
                'journal_entries.description as entry_description',
                'journal_entry_lines.description as line_description',
                'journal_entry_lines.debit',
                'journal_entry_lines.credit',
            ])
            ->orderBy('journal_entries.entry_date')
            ->orderBy('journal_entries.id')
            ->orderBy('journal_entry_lines.id')
            ->get();

        $runningBalance = $beginningBalance;
        $isDebitNormal  = $account->normal_balance === 'Debet';
        $totalDebit     = 0;
        $totalCredit    = 0;

        $transactions = $lines->map(function ($line) use (&$runningBalance, &$totalDebit, &$totalCredit, $isDebitNormal) {
            $debit  = (float) $line->debit;
            $credit = (float) $line->credit;

            $totalDebit  += $debit;
            $totalCredit += $credit;

            if ($isDebitNormal) {
                $runningBalance += ($debit - $credit);
            } else {
                $runningBalance += ($credit - $debit);
            }

            return [
                'date'            => substr($line->date, 0, 10),
                'reference'       => $line->reference,
                'description'     => $line->line_description ?: $line->entry_description,
                'debit'           => $debit,
                'credit'          => $credit,
                'running_balance' => round($runningBalance, 2),
            ];
        })->toArray();

        return [
            'account' => [
                'id'             => $account->id,
                'code'           => $account->code,
                'name'           => $account->name,
                'type'           => $account->type,
                'normal_balance' => $account->normal_balance,
            ],
            'beginning_balance' => round($beginningBalance, 2),
            'starting_balance'  => round($beginningBalance, 2),
            'total_debit'       => round($totalDebit, 2),
            'total_credit'      => round($totalCredit, 2),
            'ending_balance'    => round($runningBalance, 2),
            'rows'              => $transactions,
            'transactions'      => $transactions,
        ];
    }

    /**
     * Get ledger (buku besar) entries for a specific account within a date range.
     *
     * WARNING: Running balance di method ini dimulai dari 0 (tidak memperhitungkan
     * saldo kumulatif sebelum startDate). Gunakan generateForAccount() untuk laporan
     * Buku Besar resmi yang menyertakan saldo awal yang benar.
     * Method ini hanya untuk query per rentang tanggal tanpa saldo awal.
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
