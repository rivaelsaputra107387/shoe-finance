<?php

namespace App\Services;

use App\Models\BankMutation;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Account;
use App\Models\FiscalPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Exception;

class BankMutationService
{
    /**
     * Map bank source to their respective Account ID (based on Code).
     */
    protected function getBankAccountId(string $bankSource): ?int
    {
        $code = match (strtoupper($bankSource)) {
            'BCA' => '1120',
            'MANDIRI' => '1121',
            default => null,
        };
        
        if (!$code) return null;

        $account = Account::where('code', $code)->first();
        return $account?->id;
    }

    /**
     * Matches the mutation with internal web invoice via mock API.
     */
    public function matchWithApi(BankMutation $mutation, int $userId): void
    {
        // Simulasi hit API
        // $response = Http::get("https://api.internalweb.com/invoices/match", ['amount' => $mutation->amount]);
        
        // Mocking successful response
        $mockResponse = [
            'invoice_id' => 'INV-' . date('Ymd') . '-' . rand(1000, 9999),
            'customer' => 'Customer ' . rand(1, 100),
            'matched_at' => now()->toDateTimeString(),
            'confidence' => '95%',
        ];

        $mutation->update([
            'matched_invoice_ref' => $mockResponse['invoice_id'],
            'matched_invoice_data' => $mockResponse,
            'status' => 'matched',
            'matched_by' => $userId,
        ]);
    }

    /**
     * Generates a draft journal entry using the Suspense Account.
     */
    public function draftJournalEntry(BankMutation $mutation, int $userId): void
    {
        DB::beginTransaction();
        try {
            // Check fiscal period
            $fiscalPeriod = FiscalPeriod::where('start_date', '<=', $mutation->date)
                ->where('end_date', '>=', $mutation->date)
                ->first();

            if (!$fiscalPeriod || $fiscalPeriod->status === 'closed') {
                throw new Exception("Tanggal mutasi berada di luar periode aktif atau periode sudah ditutup.");
            }

            // Create Journal Entry
            $journalEntry = JournalEntry::create([
                'entry_date' => $mutation->date,
                'reference' => 'MUT-' . str_pad($mutation->id, 5, '0', STR_PAD_LEFT),
                'description' => $mutation->description,
                'fiscal_period_id' => $fiscalPeriod->id,
                'created_by' => $userId,
                'status' => 'draft',
            ]);

            $bankAccountId = $this->getBankAccountId($mutation->bank_source);
            if (!$bankAccountId) {
                throw new Exception("Akun Bank untuk source {$mutation->bank_source} tidak ditemukan.");
            }
            
            $suspenseAccount = Account::where('code', '9999')->first();
            if (!$suspenseAccount) {
                throw new Exception("Master Data Akun Sementara (9999) belum dibuat di sistem.");
            }

            // IN: Uang Masuk -> Bank bertambah (Debit) -> Suspense (Kredit)
            // OUT: Uang Keluar -> Bank berkurang (Kredit) -> Suspense (Debit)
            
            if ($mutation->mutation_type === 'IN') {
                JournalEntryLine::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $bankAccountId,
                    'description' => $mutation->description,
                    'debit' => $mutation->amount,
                    'credit' => 0,
                ]);

                JournalEntryLine::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $suspenseAccount->id,
                    'description' => $mutation->description,
                    'debit' => 0,
                    'credit' => $mutation->amount,
                ]);
            } else {
                JournalEntryLine::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $suspenseAccount->id,
                    'description' => $mutation->description,
                    'debit' => $mutation->amount,
                    'credit' => 0,
                ]);

                JournalEntryLine::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $bankAccountId,
                    'description' => $mutation->description,
                    'debit' => 0,
                    'credit' => $mutation->amount,
                ]);
            }

            $mutation->update([
                'journal_entry_id' => $journalEntry->id,
                'status' => 'drafted',
                'completed_by' => $userId,
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
