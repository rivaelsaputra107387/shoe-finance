<?php

namespace App\Console\Commands;

use App\Models\Central\Unit;
use App\Services\Coa\CoaSynchronizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CoaSyncCommand extends Command
{
    protected $signature = 'finlog:coa-sync {--unit= : Kode unit. Kosong berarti semua unit} {--dry-run : Hanya tampilkan perubahan} {--force : Jalankan tanpa konfirmasi}';

    protected $description = 'Samakan akun induk, akun sistem, dan akun antar unit dengan master. Sub-akun bebas tidak disentuh.';

    public function handle(CoaSynchronizer $synchronizer): int
    {
        $units = Unit::ordered()->when($this->option('unit'), fn ($query, $code) => $query->where('code', $code))->get();

        if ($units->isEmpty()) {
            $this->error('Unit tidak ditemukan.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        if (! $dryRun && ! $this->option('force') && ! $this->confirm('Terapkan ke '.$units->pluck('code')->implode(', ').'? Pastikan sudah melihat --dry-run.')) {
            return self::FAILURE;
        }

        foreach ($units as $unit) {
            $central = DB::connection('central')->getConfig('host');
            $this->info("Unit {$unit->code}: {$central} / {$unit->database}");

            $result = $synchronizer->sync($unit, $dryRun);

            foreach ($result['changes'] as $change) {
                $this->line("  {$change}");
            }

            foreach ($result['warnings'] as $warning) {
                $this->warn("  {$warning}");
            }

            $this->line('  '.count($result['changes']).($dryRun ? ' perubahan akan dibuat' : ' perubahan diterapkan'));
        }

        return self::SUCCESS;
    }
}
