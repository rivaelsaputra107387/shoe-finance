<?php

namespace App\Console\Commands;

use App\Models\Central\Unit;
use App\Models\JournalEntry;
use App\Support\AccessAudit;
use App\Support\UnitContext;
use Illuminate\Console\Command;

class ActivateUnitCommand extends Command
{
    protected $signature = 'finlog:activate-unit
        {code : Kode unit}
        {--allow-without-opening : Aktifkan walau saldo awal belum ada}
        {--reason= : Alasan, wajib bersama --allow-without-opening}';

    protected $description = 'Aktifkan unit setelah saldo awal terinput, sehingga ikut Laporan Gabungan.';

    public function handle(UnitContext $context): int
    {
        $unit = Unit::where('code', $this->argument('code'))->first();

        if (! $unit) {
            $this->error('Unit tidak ditemukan.');

            return self::FAILURE;
        }

        if ($unit->isActive()) {
            $this->info("Unit {$unit->code} sudah aktif.");

            return self::SUCCESS;
        }

        $hasOpening = $context->run($unit, fn () => JournalEntry::where('is_opening', true)->where('status', 'posted')->exists());
        $reason = trim((string) $this->option('reason'));

        if (! $hasOpening && ! $this->option('allow-without-opening')) {
            $this->error('Saldo awal belum diinput. Input dulu, atau pakai --allow-without-opening beserta --reason.');

            return self::FAILURE;
        }

        if (! $hasOpening && $reason === '') {
            $this->error('Isi --reason untuk mengaktifkan tanpa saldo awal.');

            return self::FAILURE;
        }

        $old = ['status' => $unit->status];
        $unit->update(['status' => Unit::STATUS_ACTIVE, 'activated_at' => now(), 'deactivated_at' => null]);

        AccessAudit::record('unit.activated', 'unit', $unit->id, $old, [
            'status' => $unit->status,
            'has_opening_balance' => $hasOpening,
            'reason' => $reason ?: null,
        ]);

        $this->info("Unit {$unit->code} aktif.");

        return self::SUCCESS;
    }
}
