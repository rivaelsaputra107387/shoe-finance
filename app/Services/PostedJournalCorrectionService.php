<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AuditTrail;
use App\Models\BankMutation;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;
use App\Support\UnitContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Koreksi sementara untuk jurnal posted. Hanya baris jurnal yang berubah, dan setiap koreksi
 * menyimpan isi sebelum dan sesudah di audit trail karena baris lama tidak disimpan di tempat lain.
 */
class PostedJournalCorrectionService
{
    public const AUDIT_ACTION = 'correct_posted';

    private const SOURCE_ACCOUNT_CODES = [
        'BCA' => '1120',
        'MANDIRI' => '1121',
        'CASH' => '1110',
        'KAS' => '1110',
    ];

    public function isEnabled(): bool
    {
        return (bool) config('journal.allow_posted_correction');
    }

    /**
     * Alasan jurnal tidak bisa dikoreksi, atau null jika boleh.
     */
    public function blocker(JournalEntry $journal): ?string
    {
        if (! $this->isEnabled()) {
            return 'Fitur koreksi jurnal posted sedang tidak aktif.';
        }

        if ($journal->status !== 'posted') {
            return 'Hanya jurnal berstatus posted yang memakai koreksi ini.';
        }

        if ($journal->is_opening) {
            return 'Jurnal saldo awal diubah dari halaman Saldo Awal.';
        }

        if ($journal->is_closing) {
            return 'Jurnal penutup tidak dapat dikoreksi.';
        }

        if ($journal->fiscalPeriod?->status === 'closed') {
            return 'Periode jurnal ini sudah ditutup.';
        }

        return null;
    }

    public function selectableAccounts(): Collection
    {
        return Account::active()
            ->where(fn ($query) => $query->whereNotNull('parent_id')->orWhere('code', '3300'))
            ->where('code', '!=', '9999')
            ->orderBy('code')
            ->get();
    }

    /**
     * Sisi kas atau bank yang harus tetap sama dengan mutasi sumber jurnal.
     *
     * @return array<int, array{account_id: int, code: string, name: string, side: string, amount: float, bank_source: string}>
     */
    public function requiredSourceLines(JournalEntry $journal): array
    {
        return BankMutation::where('journal_entry_id', $journal->id)
            ->get()
            ->map(function (BankMutation $mutation) {
                $source = strtoupper((string) $mutation->bank_source);
                $code = self::SOURCE_ACCOUNT_CODES[$source] ?? null;
                $account = $code ? Account::where('code', $code)->first() : null;

                if (! $account) {
                    throw ValidationException::withMessages([
                        'lines' => "Akun sumber mutasi {$mutation->bank_source} tidak ditemukan.",
                    ]);
                }

                return [
                    'account_id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'side' => $mutation->mutation_type === 'IN' ? 'debit' : 'credit',
                    'amount' => round((float) $mutation->amount, 2),
                    'bank_source' => $mutation->bank_source,
                ];
            })
            ->all();
    }

    public function correct(JournalEntry $journal, array $lines, string $reason, User $user, ?string $ipAddress = null): void
    {
        DB::transaction(function () use ($journal, $lines, $reason, $user, $ipAddress) {
            $locked = JournalEntry::with(['lines.account', 'fiscalPeriod'])->lockForUpdate()->findOrFail($journal->id);

            if ($blocker = $this->blocker($locked)) {
                throw ValidationException::withMessages(['lines' => $blocker]);
            }

            $newLines = $this->normalizedLines($lines);
            $this->assertBalanced($newLines);
            $this->assertSourceLinesKept($locked, $newLines);

            $before = $this->snapshotExisting($locked);
            $after = $this->snapshotNew($newLines);

            if ($this->comparable($before) === $this->comparable($after)) {
                throw ValidationException::withMessages(['lines' => 'Tidak ada perubahan pada baris jurnal.']);
            }

            $locked->lines()->delete();

            foreach ($newLines as $line) {
                JournalEntryLine::create($line + ['journal_entry_id' => $locked->id]);
            }

            $locked->touch();

            AuditTrail::create([
                'user_id' => $user->id,
                'table_name' => 'journal_entries',
                'record_id' => $locked->id,
                'action' => self::AUDIT_ACTION,
                'old_data' => [
                    'reference' => $locked->reference,
                    'lines' => $before,
                ],
                'new_data' => [
                    'reference' => $locked->reference,
                    'reason' => $reason,
                    'ip_address' => $ipAddress,
                    'lines' => $after,
                ],
            ]);
        });

        $this->forgetDashboardCache();
    }

    public function history(JournalEntry $journal): Collection
    {
        return AuditTrail::with('user:id,name')
            ->where('table_name', 'journal_entries')
            ->where('record_id', $journal->id)
            ->where('action', self::AUDIT_ACTION)
            ->latest('created_at')
            ->latest('id')
            ->get()
            ->map(fn (AuditTrail $audit) => [
                'id' => $audit->id,
                'user' => $audit->user?->name ?? 'Sistem',
                'created_at' => $audit->created_at?->toIso8601String(),
                'reason' => $audit->new_data['reason'] ?? '-',
                'before' => $audit->old_data['lines'] ?? [],
                'after' => $audit->new_data['lines'] ?? [],
            ]);
    }

    private function normalizedLines(array $lines): array
    {
        $accounts = $this->selectableAccounts()->keyBy('id');
        $normalized = [];

        foreach ($lines as $index => $line) {
            $number = $index + 1;
            $debit = round((float) ($line['debit'] ?? 0), 2);
            $credit = round((float) ($line['credit'] ?? 0), 2);

            if ($debit == 0.0 && $credit == 0.0) {
                continue;
            }

            if ($debit > 0 && $credit > 0) {
                throw ValidationException::withMessages(['lines' => "Baris {$number} hanya boleh berisi debit atau kredit."]);
            }

            if (! $accounts->has((int) ($line['account_id'] ?? 0))) {
                throw ValidationException::withMessages(['lines' => "Akun pada baris {$number} tidak dapat dipakai. Pilih sub-akun yang aktif."]);
            }

            $description = trim((string) ($line['description'] ?? ''));

            $normalized[] = [
                'account_id' => (int) $line['account_id'],
                'debit' => $debit,
                'credit' => $credit,
                'description' => $description === '' ? null : $description,
            ];
        }

        if (count($normalized) < 2) {
            throw ValidationException::withMessages(['lines' => 'Jurnal minimal memiliki dua baris bernilai.']);
        }

        return $normalized;
    }

    private function assertBalanced(array $lines): void
    {
        $debit = round(array_sum(array_column($lines, 'debit')), 2);
        $credit = round(array_sum(array_column($lines, 'credit')), 2);

        if (abs($debit - $credit) >= 0.01) {
            throw ValidationException::withMessages(['lines' => 'Total debit dan kredit harus sama.']);
        }
    }

    private function assertSourceLinesKept(JournalEntry $journal, array $lines): void
    {
        foreach ($this->requiredSourceLines($journal) as $required) {
            $matching = array_filter($lines, fn (array $line) => $line['account_id'] === $required['account_id']);
            $debit = array_sum(array_column($matching, 'debit'));
            $credit = array_sum(array_column($matching, 'credit'));
            $net = round($required['side'] === 'debit' ? $debit - $credit : $credit - $debit, 2);

            if (abs($net - $required['amount']) >= 0.01) {
                $side = $required['side'] === 'debit' ? 'debit' : 'kredit';
                $amount = number_format($required['amount'], 2, ',', '.');

                throw ValidationException::withMessages([
                    'lines' => "Jurnal ini berasal dari mutasi {$required['bank_source']}, jadi {$required['code']} {$required['name']} harus tetap di sisi {$side} sebesar Rp {$amount}.",
                ]);
            }
        }
    }

    private function snapshotExisting(JournalEntry $journal): array
    {
        return $journal->lines->map(fn (JournalEntryLine $line) => [
            'account_id' => $line->account_id,
            'code' => $line->account?->code,
            'name' => $line->account?->name,
            'debit' => round((float) $line->debit, 2),
            'credit' => round((float) $line->credit, 2),
            'description' => $line->description,
        ])->values()->all();
    }

    private function snapshotNew(array $lines): array
    {
        $accounts = Account::whereIn('id', array_column($lines, 'account_id'))->get()->keyBy('id');

        return array_map(fn (array $line) => [
            'account_id' => $line['account_id'],
            'code' => $accounts[$line['account_id']]->code,
            'name' => $accounts[$line['account_id']]->name,
            'debit' => $line['debit'],
            'credit' => $line['credit'],
            'description' => $line['description'],
        ], $lines);
    }

    private function comparable(array $snapshot): array
    {
        $rows = array_map(
            fn (array $line) => implode('|', [$line['account_id'], number_format($line['debit'], 2, '.', ''), number_format($line['credit'], 2, '.', ''), $line['description'] ?? '']),
            $snapshot
        );

        sort($rows);

        return $rows;
    }

    private function forgetDashboardCache(): void
    {
        $periodIds = FiscalPeriod::pluck('id')->all();

        foreach ($periodIds as $periodId) {
            Cache::forget($this->dashboardCacheKey('inertia_cash_balance_stats_' . $periodId));
            Cache::forget($this->dashboardCacheKey('inertia_dashboard_charts_' . $periodId));
            Cache::forget($this->dashboardCacheKey('inertia_financial_summary_' . $periodId));
        }

        Cache::forget($this->dashboardCacheKey('inertia_dashboard_charts_null'));
    }

    private function dashboardCacheKey(string $key): string
    {
        return app(UnitContext::class)->cacheKey($key);
    }
}
