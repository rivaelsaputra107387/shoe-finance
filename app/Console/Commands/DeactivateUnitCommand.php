<?php

namespace App\Console\Commands;

use App\Models\Central\Unit;
use App\Support\AccessAudit;
use Illuminate\Console\Command;

class DeactivateUnitCommand extends Command
{
    protected $signature = 'finlog:deactivate-unit {code : Kode unit} {--force : Jalankan tanpa konfirmasi}';

    protected $description = 'Jadikan unit hanya baca. Datanya tidak dihapus.';

    public function handle(): int
    {
        $unit = Unit::where('code', $this->argument('code'))->first();

        if (! $unit) {
            $this->error('Unit tidak ditemukan.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm("Nonaktifkan unit {$unit->code}?")) {
            return self::FAILURE;
        }

        $old = ['status' => $unit->status];
        $unit->update(['status' => Unit::STATUS_INACTIVE, 'deactivated_at' => now()]);

        AccessAudit::record('unit.deactivated', 'unit', $unit->id, $old, ['status' => $unit->status]);

        $this->info("Unit {$unit->code} nonaktif dan hanya bisa dibaca.");

        return self::SUCCESS;
    }
}
