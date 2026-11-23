<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\PostedJournalCorrectionService;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

/**
 * Mengganti akun satu baris di banyak jurnal posted sekaligus, lewat PostedJournalCorrectionService,
 * sehingga setiap jurnal tetap punya log koreksi sendiri. Tanpa --apply hanya memeriksa.
 *
 * Format CSV (dengan header): reference,from,to,amount,reason
 */
class BulkAccountCorrectionCommand extends Command
{
    protected $signature = 'finlog:koreksi-massal
        {file : File CSV berisi reference,from,to,amount,reason}
        {--user= : Email pengguna owner/finance yang menjalankan koreksi}
        {--apply : Simpan perubahan. Tanpa opsi ini hanya pemeriksaan}';

    protected $description = 'Koreksi akun pada banyak jurnal posted sekaligus, tercatat di audit trail atas nama pengguna yang menjalankan.';

    public function handle(PostedJournalCorrectionService $service): int
    {
        if (! $service->isEnabled()) {
            $this->error('Fitur koreksi jurnal posted sedang tidak aktif (FINLOG_ALLOW_POSTED_JOURNAL_CORRECTION).');

            return self::FAILURE;
        }

        $user = User::where('email', (string) $this->option('user'))->first();

        if (! $user || ! $user->hasAnyRole(['owner', 'finance'])) {
            $this->error('Isi --user dengan email pengguna owner atau finance yang menjalankan koreksi ini.');

            return self::FAILURE;
        }

        $rows = $this->readRows((string) $this->argument('file'));

        if ($rows === null) {
            return self::FAILURE;
        }

        $plans = [];
        $failed = false;

        foreach ($rows as $number => $row) {
            $result = $this->plan($service, $row);
            $label = sprintf('%-10s %s → %s  Rp %s', $row['reference'], $row['from'], $row['to'], number_format((float) $row['amount'], 2, ',', '.'));

            if (isset($result['error'])) {
                $failed = true;
                $this->line("<fg=red>GAGAL</>  {$label}  ({$result['error']})");
            } elseif (isset($result['skip'])) {
                $this->line("<fg=yellow>LEWATI</> {$label}  ({$result['skip']})");
            } else {
                $plans[] = $result + ['row' => $row];
                $this->line("<fg=green>SIAP</>   {$label}");
            }
        }

        if ($failed) {
            $this->error('Ada baris yang gagal diperiksa. Tidak ada yang disimpan.');

            return self::FAILURE;
        }

        if (! $this->option('apply')) {
            $this->info(count($plans).' jurnal siap dikoreksi. Jalankan lagi dengan --apply untuk menyimpan.');

            return self::SUCCESS;
        }

        foreach ($plans as $plan) {
            try {
                $service->correct($plan['journal'], $plan['lines'], $plan['row']['reason'], $user, 'CLI finlog:koreksi-massal');
            } catch (ValidationException $e) {
                $this->error("{$plan['row']['reference']}: ".collect($e->errors())->flatten()->implode(' '));
                $this->error('Proses berhenti. Jurnal sebelumnya sudah tersimpan; jalankan ulang untuk melanjutkan.');

                return self::FAILURE;
            }
        }

        $this->info(count($plans)." jurnal dikoreksi atas nama {$user->name}.");

        return self::SUCCESS;
    }

    /**
     * @return array<int, array{reference: string, from: string, to: string, amount: string, reason: string}>|null
     */
    private function readRows(string $path): ?array
    {
        if (! is_readable($path)) {
            $this->error("File {$path} tidak bisa dibaca.");

            return null;
        }

        $handle = fopen($path, 'r');
        $header = array_map('trim', fgetcsv($handle) ?: []);
        $expected = ['reference', 'from', 'to', 'amount', 'reason'];

        if ($header !== $expected) {
            $this->error('Header CSV harus: '.implode(',', $expected));

            return null;
        }

        $rows = [];

        while (($data = fgetcsv($handle)) !== false) {
            if (count(array_filter($data, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            $rows[] = array_combine($expected, array_map('trim', array_pad($data, 5, '')));
        }

        fclose($handle);

        $references = array_column($rows, 'reference');

        if (count($references) !== count(array_unique($references))) {
            $this->error('Setiap jurnal hanya boleh muncul sekali di CSV.');

            return null;
        }

        return $rows;
    }

    private function plan(PostedJournalCorrectionService $service, array $row): array
    {
        if ($row['reason'] === '' || mb_strlen($row['reason']) < 10) {
            return ['error' => 'alasan minimal 10 karakter'];
        }

        $to = Account::where('code', $row['to'])->first();

        if (! $to || ! $service->selectableAccounts()->contains('id', $to->id)) {
            return ['error' => "akun tujuan {$row['to']} tidak ada atau tidak bisa dipakai"];
        }

        $journals = JournalEntry::with(['lines.account', 'fiscalPeriod'])->where('reference', $row['reference'])->get();

        if ($journals->count() !== 1) {
            return ['error' => "ditemukan {$journals->count()} jurnal dengan nomor ini"];
        }

        $journal = $journals->first();

        if ($blocker = $service->blocker($journal)) {
            return ['error' => $blocker];
        }

        $amount = round((float) $row['amount'], 2);
        $matches = fn (string $code) => $journal->lines->filter(
            fn ($line) => $line->account?->code === $code
                && (abs((float) $line->debit - $amount) < 0.01 || abs((float) $line->credit - $amount) < 0.01)
        );

        $fromLines = $matches($row['from']);

        if ($fromLines->isEmpty() && $matches($row['to'])->isNotEmpty()) {
            return ['skip' => 'sudah dikoreksi'];
        }

        if ($fromLines->count() !== 1) {
            return ['error' => "baris {$row['from']} senilai itu ditemukan {$fromLines->count()} kali"];
        }

        $targetId = $fromLines->first()->id;

        $lines = $journal->lines->map(fn ($line) => [
            'account_id' => $line->id === $targetId ? $to->id : $line->account_id,
            'debit' => (float) $line->debit,
            'credit' => (float) $line->credit,
            'description' => $line->description,
        ])->values()->all();

        return ['journal' => $journal, 'lines' => $lines];
    }
}
