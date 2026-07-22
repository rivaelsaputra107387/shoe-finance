<?php

namespace App\Services;

use App\Models\Account;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ClosingEntryService
{
    /**
     * Close a fiscal period by generating closing journal entries.
     *
     * Steps:
     * 1. Close all Revenue accounts (4xxx) → Ikhtisar Laba Rugi
     * 2. Close all Expense accounts (5xxx, 6xxx, 7200, 8xxx) → Ikhtisar Laba Rugi
     * 3. Close Ikhtisar Laba Rugi → Modal
     * 4. Close Prive (3120) → Modal (if any)
     * 5. Mark period as closed
     * 6. Create next period
     *
     * @throws \Exception if period is already closed or validation fails
     */
    public function closePeriod(int $fiscalPeriodId): array
    {
        $period = FiscalPeriod::findOrFail($fiscalPeriodId);

        if ($period->status === 'closed') {
            throw new \Exception("Periode '{$period->name}' sudah ditutup.");
        }

        $ikhtisarAccount = Account::where('code', '3200')->firstOrFail();
        $modalAccount = Account::where('code', '3110')->firstOrFail();
        $createdBy = auth()->id();
        $closingDate = $period->end_date;

        $closingEntries = [];

        return DB::transaction(function () use ($period, $ikhtisarAccount, $modalAccount, $createdBy, $closingDate, $fiscalPeriodId) {
            $closingEntries = [];

            // ── STEP 1: Close Revenue Accounts (4xxx, 71xx) ──
            $revenueAccounts = Account::active()
                ->where(function ($q) {
                    $q->where('code', 'like', '4%')
                      ->orWhere('code', 'like', '71%');
                })
                ->whereNotNull('parent_id')
                ->get();

            $totalRevenue = 0;
            $revenueLines = [];

            foreach ($revenueAccounts as $account) {
                $balance = $account->getBalanceForPeriod($fiscalPeriodId);
                if (abs($balance) < 0.01) continue;

                // Revenue has credit normal balance, so to close: debit the account
                $revenueLines[] = [
                    'account_id' => $account->id,
                    'debit' => abs($balance),
                    'credit' => 0,
                ];
                $totalRevenue += abs($balance);
            }

            if ($totalRevenue > 0) {
                // Credit to Ikhtisar Laba Rugi
                $revenueLines[] = [
                    'account_id' => $ikhtisarAccount->id,
                    'debit' => 0,
                    'credit' => $totalRevenue,
                ];

                $entry = $this->createClosingEntry(
                    'Jurnal Penutup - Menutup Akun Pendapatan',
                    $closingDate,
                    $fiscalPeriodId,
                    $createdBy,
                    $revenueLines
                );
                $closingEntries[] = $entry;
            }

            // ── STEP 2: Close Expense Accounts (5xxx, 6xxx, 72xx, 8xxx) ──
            $expenseAccounts = Account::active()
                ->where(function ($q) {
                    $q->where('code', 'like', '5%')
                      ->orWhere('code', 'like', '6%')
                      ->orWhere('code', 'like', '72%')
                      ->orWhere('code', 'like', '8%');
                })
                ->whereNotNull('parent_id')
                ->get();

            $totalExpenses = 0;
            $expenseLines = [];

            foreach ($expenseAccounts as $account) {
                $balance = $account->getBalanceForPeriod($fiscalPeriodId);
                if (abs($balance) < 0.01) continue;

                // Expense has debit normal balance, so to close: credit the account
                $expenseLines[] = [
                    'account_id' => $account->id,
                    'debit' => 0,
                    'credit' => abs($balance),
                ];
                $totalExpenses += abs($balance);
            }

            if ($totalExpenses > 0) {
                // Debit Ikhtisar Laba Rugi
                $expenseLines[] = [
                    'account_id' => $ikhtisarAccount->id,
                    'debit' => $totalExpenses,
                    'credit' => 0,
                ];

                $entry = $this->createClosingEntry(
                    'Jurnal Penutup - Menutup Akun Beban',
                    $closingDate,
                    $fiscalPeriodId,
                    $createdBy,
                    $expenseLines
                );
                $closingEntries[] = $entry;
            }

            // ── STEP 3: Close Ikhtisar Laba Rugi → Modal ──
            $ikhtisarBalance = $totalRevenue - $totalExpenses; // Positive = profit, negative = loss

            if (abs($ikhtisarBalance) > 0.01) {
                $ikhtisarLines = [];

                if ($ikhtisarBalance > 0) {
                    // Profit: Debit Ikhtisar LR, Credit Modal
                    $ikhtisarLines[] = ['account_id' => $ikhtisarAccount->id, 'debit' => $ikhtisarBalance, 'credit' => 0];
                    $ikhtisarLines[] = ['account_id' => $modalAccount->id, 'debit' => 0, 'credit' => $ikhtisarBalance];
                } else {
                    // Loss: Credit Ikhtisar LR, Debit Modal
                    $loss = abs($ikhtisarBalance);
                    $ikhtisarLines[] = ['account_id' => $ikhtisarAccount->id, 'debit' => 0, 'credit' => $loss];
                    $ikhtisarLines[] = ['account_id' => $modalAccount->id, 'debit' => $loss, 'credit' => 0];
                }

                $entry = $this->createClosingEntry(
                    'Jurnal Penutup - Menutup Ikhtisar Laba Rugi ke Modal',
                    $closingDate,
                    $fiscalPeriodId,
                    $createdBy,
                    $ikhtisarLines
                );
                $closingEntries[] = $entry;
            }

            // ── STEP 4: Close Prive → Modal ──
            $priveAccount = Account::where('code', '3120')->first();
            if ($priveAccount) {
                $priveBalance = $priveAccount->getBalanceForPeriod($fiscalPeriodId);

                if (abs($priveBalance) > 0.01) {
                    $priveLines = [
                        // Credit Prive (has debit normal balance)
                        ['account_id' => $priveAccount->id, 'debit' => 0, 'credit' => abs($priveBalance)],
                        // Debit Modal
                        ['account_id' => $modalAccount->id, 'debit' => abs($priveBalance), 'credit' => 0],
                    ];

                    $entry = $this->createClosingEntry(
                        'Jurnal Penutup - Menutup Prive ke Modal',
                        $closingDate,
                        $fiscalPeriodId,
                        $createdBy,
                        $priveLines
                    );
                    $closingEntries[] = $entry;
                }
            }

            // ── STEP 5: Mark period as closed ──
            $period->update([
                'status' => 'closed',
                'closed_by' => auth()->id(),
            ]);

            // ── STEP 6: Create next period ──
            $nextStart = Carbon::parse($period->end_date)->addDay();
            $nextEnd = $nextStart->copy()->endOfMonth();
            $nextName = $nextStart->translatedFormat('F Y');

            $nextPeriod = FiscalPeriod::create([
                'name' => $nextName,
                'start_date' => $nextStart,
                'end_date' => $nextEnd,
                'status' => 'open',
            ]);

            return [
                'closing_entries' => $closingEntries,
                'net_profit' => round($ikhtisarBalance, 2),
                'closed_period' => $period,
                'new_period' => $nextPeriod,
            ];
        });
    }

    /**
     * Create a closing journal entry with its lines.
     */
    private function createClosingEntry(
        string $description,
        $date,
        int $fiscalPeriodId,
        int $createdBy,
        array $lines
    ): JournalEntry {
        $entry = JournalEntry::create([
            'entry_date' => $date,
            'reference' => 'JP-' . now()->format('YmdHis'),
            'description' => $description,
            'fiscal_period_id' => $fiscalPeriodId,
            'created_by' => $createdBy,
            'is_closing' => true,
            'status' => 'posted',
            'posted_by' => $createdBy,
            'posted_at' => now(),
        ]);

        foreach ($lines as $line) {
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $line['account_id'],
                'debit' => $line['debit'],
                'credit' => $line['credit'],
            ]);
        }

        return $entry;
    }
}
