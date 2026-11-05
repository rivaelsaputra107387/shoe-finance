<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateCentralCommand extends Command
{
    protected $signature = 'finlog:migrate-central {--force : Jalankan tanpa konfirmasi}';

    protected $description = 'Jalankan migrasi database pusat.';

    public function handle(): int
    {
        $connection = DB::connection('central');
        $this->info("Tujuan: {$connection->getConfig('host')} / {$connection->getDatabaseName()}");

        if (! $this->option('force') && ! $this->confirm('Lanjutkan migrasi database pusat?')) {
            return self::FAILURE;
        }

        return $this->call('migrate', [
            '--database' => 'central',
            '--path' => 'database/migrations/central',
            '--force' => true,
        ]);
    }
}
