<?php

namespace App\Console\Commands;

use App\Models\Central\Unit;
use App\Support\UnitContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateUnitsCommand extends Command
{
    protected $signature = 'finlog:migrate-units {--unit= : Kode unit. Kosong berarti semua unit} {--force : Jalankan tanpa konfirmasi}';

    protected $description = 'Jalankan migrasi unit ke satu atau semua database unit.';

    public function handle(UnitContext $context): int
    {
        $units = Unit::ordered()
            ->when($this->option('unit'), fn ($query, $code) => $query->where('code', $code))
            ->get();

        if ($units->isEmpty()) {
            $this->error('Unit tidak ditemukan.');

            return self::FAILURE;
        }

        foreach ($units as $unit) {
            $name = $context->connectionName($unit);
            $connection = DB::connection($name);
            $this->info("Tujuan {$unit->code}: {$connection->getConfig('host')} / {$connection->getDatabaseName()}");
        }

        if (! $this->option('force') && ! $this->confirm('Lanjutkan migrasi?')) {
            return self::FAILURE;
        }

        foreach ($units as $unit) {
            $result = $this->call('migrate', [
                '--database' => $context->connectionName($unit),
                '--path' => 'database/migrations/unit',
                '--force' => true,
            ]);

            if ($result !== self::SUCCESS) {
                return $result;
            }
        }

        return self::SUCCESS;
    }
}
