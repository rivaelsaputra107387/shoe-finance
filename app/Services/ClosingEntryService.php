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
     * 3. Close Ikhtisar Laba Rugi → Laba Ditahan (3300)
     * 4. Close Prive (3120) → Laba Ditahan (3300), if any
     *    Modal Disetor (3110) only changes through actual capital contributions.
     * 5. Mark period as closed
     * 6. Take the next period from the shared calendar, adding it when missing
     *
     * @throws \Exception if period is already closed or validation fails
     */
    public function closePeriod(int $fiscalPeriodId): array
    {
        $period = FiscalPeriod::findOrFail($fiscalPeriodId);

        if ($period->status === 'closed') {
            throw new \Exception("Periode '{$period->name}' sudah ditutup.");
        }

        // Layer 1: Cek apakah ada jurnal berstatus draft/unapproved
        $unpostedJournals = JournalEntry::where('fiscal_period_id', $fiscalPeriodId)
            ->whereIn('status', ['draft', 'unapproved'])
            ->count();
            
        if ($unpostedJournals > 0) {
            throw new \Exception("Gagal menutup periode: Terdapat {$unpostedJournals} jurnal (manual/mutasi) yang masih berstatus Draft atau Unapproved. Silakan lengkapi, approve, atau hapus jurnal tersebut terlebih dahulu.");
        }

        // Layer 2: Cek saldo Akun Sementara / Suspense Account
        $suspenseAccount = Account::where('code', '9999')->first();
        if ($suspenseAccount) {
            $suspenseBalance = $suspenseAccount->getBalanceForPeriod($fiscalPeriodId);
            if (abs($suspenseBalance) > 0.01) {
                throw new \Exception("Gagal menutup periode: Akun Sementara (9999) memiliki saldo menggantung sebesar Rp " . number_format($suspenseBalance, 2, ',', '.') . ". Pastikan semua jurnal mutasi bank telah dilengkapi.");
            }
        }

        $ikhtisarAccount = Account::where('code', '3200')->firstOrFail();
        $labaDitahanAccount = Account::where('code', '3300')->firstOrFail();
        $createdBy = auth()->id() ?? 1;
        $closingDate = $period->end_date;

        $closingEntries = [];

        $calendar = app(FiscalCalendar::class);

        $result = DB::transaction(function () use ($period, $ikhtisarAccount, $labaDitahanAccount, $createdBy, $closingDate, $fiscalPeriodId, $calendar) {
            // Defensively cleanup any existing closing entries for this period (e.g. from prior reopen)
            $existingClosing = JournalEntry::where('fiscal_period_id', $fiscalPeriodId)
                ->where('is_closing', true)
                ->get();

            foreach ($existingClosing as $oldEntry) {
                $oldEntry->lines()->delete();
                $oldEntry->forceDelete();
            }

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
                $balance = $account->getBalanceForPeriod($fiscalPeriodId, excludeClosing: true);
                if (abs($balance) < 0.01) continue;

                // Revenue has credit normal balance, so to close: debit the account.
                // A contra balance (e.g. returns) is negative and closes on the opposite side.
                $revenueLines[] = $this->closingLine($account->id, $balance);
                $totalRevenue += $balance;
            }

            if (!empty($revenueLines)) {
                // Credit to Ikhtisar Laba Rugi
                if (abs($totalRevenue) >= 0.01) {
                    $revenueLines[] = $this->closingLine($ikhtisarAccount->id, -$totalRevenue);
                }

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
                $balance = $account->getBalanceForPeriod($fiscalPeriodId, excludeClosing: true);
                if (abs($balance) < 0.01) continue;

                // Expense has debit normal balance, so to close: credit the account.
                // A contra balance (e.g. refunds) is negative and closes on the opposite side.
                $expenseLines[] = $this->closingLine($account->id, -$balance);
                $totalExpenses += $balance;
            }

            if (!empty($expenseLines)) {
                // Debit Ikhtisar Laba Rugi
                if (abs($totalExpenses) >= 0.01) {
                    $expenseLines[] = $this->closingLine($ikhtisarAccount->id, $totalExpenses);
                }

                $entry = $this->createClosingEntry(
                    'Jurnal Penutup - Menutup Akun Beban',
                    $closingDate,
                    $fiscalPeriodId,
                    $createdBy,
                    $expenseLines
                );
                $closingEntries[] = $entry;
            }

            // ── STEP 3: Close Ikhtisar Laba Rugi → Laba Ditahan ──
            $ikhtisarBalance = $totalRevenue - $totalExpenses; // Positive = profit, negative = loss

            if (abs($ikhtisarBalance) >= 0.01) {
                // Profit: Debit Ikhtisar LR, Credit Laba Ditahan. Loss: the reverse.
                $ikhtisarLines = [
                    $this->closingLine($ikhtisarAccount->id, $ikhtisarBalance),
                    $this->closingLine($labaDitahanAccount->id, -$ikhtisarBalance),
                ];

                $entry = $this->createClosingEntry(
                    'Jurnal Penutup - Menutup Ikhtisar Laba Rugi ke Laba Ditahan',
                    $closingDate,
                    $fiscalPeriodId,
                    $createdBy,
                    $ikhtisarLines
                );
                $closingEntries[] = $entry;
            }

            // ── STEP 4: Close Prive → Laba Ditahan ──
            $priveAccount = Account::where('code', '3120')->first();
            if ($priveAccount) {
                $priveBalance = $priveAccount->getBalanceForPeriod($fiscalPeriodId, excludeClosing: true);

                if (abs($priveBalance) >= 0.01) {
                    $priveLines = [
                        // Credit Prive (has debit normal balance)
                        $this->closingLine($priveAccount->id, -$priveBalance),
                        // Debit Laba Ditahan
                        $this->closingLine($labaDitahanAccount->id, $priveBalance),
                    ];

                    $entry = $this->createClosingEntry(
                        'Jurnal Penutup - Menutup Prive ke Laba Ditahan',
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
                'closed_by' => auth()->id() ?? $createdBy,
            ]);

            // ── STEP 6: Create or retrieve next period ──
            $nextStart = Carbon::parse($period->end_date)->addDay();
            $nextEnd = $nextStart->copy()->endOfMonth();
            $nextName = $nextStart->translatedFormat('F Y');

            $nextPeriod = $calendar->nextPeriodForActiveUnit($nextStart, $nextEnd, $nextName);

            return [
                'closing_entries' => $closingEntries,
                'net_profit' => round($ikhtisarBalance, 2),
                'closed_period' => $period,
                'new_period' => $nextPeriod,
            ];
        });

        // Unit lain menyusul setelah transaksi selesai. Yang gagal bisa diulang dengan finlog:calendar-sync.
        $calendar->syncAllUnits();

        return $result;
    }

    /**
     * Build a journal line from a signed amount: positive debits, negative credits.
     */
    private function closingLine(int $accountId, float $amount): array
    {
        $amount = round($amount, 2);

        return [
            'account_id' => $accountId,
            'debit' => $amount > 0 ? $amount : 0,
            'credit' => $amount < 0 ? abs($amount) : 0,
        ];
    }

    /**
     * Create a closing journal entry with its lines.
     */
    private function createClosingEntry(
        string $description,
        $date,
        int $fiscalPeriodId,
        ?int $createdBy,
        array $lines
    ): JournalEntry {
        $userId = $createdBy ?? auth()->id() ?? 1;

        $entry = JournalEntry::create([
            'entry_date' => $date,
            'reference' => 'JP-' . now()->format('YmdHis'),
            'description' => $description,
            'fiscal_period_id' => $fiscalPeriodId,
            'created_by' => $userId,
            'is_closing' => true,
            'status' => 'posted',
            'posted_by' => $userId,
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
