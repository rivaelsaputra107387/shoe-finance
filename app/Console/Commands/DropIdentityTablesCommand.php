<?php

namespace App\Console\Commands;

use App\Models\Central\Unit;
use App\Models\User;
use App\Support\IdentityTables;
use App\Support\UnitContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DropIdentityTablesCommand extends Command
{
    protected $signature = 'finlog:drop-identity-tables {unit : Kode unit} {--force : Wajib diisi, karena tabel dihapus permanen}';

    protected $description = 'Hapus tabel identitas lama pada database unit, setelah datanya ada di pusat.';

    public function handle(UnitContext $context): int
    {
        $unit = Unit::where('code', $this->argument('unit'))->first();

        if (! $unit) {
            $this->error('Unit tidak ditemukan.');

            return self::FAILURE;
        }

        $name = $context->connectionName($unit);
        $connection = DB::connection($name);
        $this->info("Tujuan: {$connection->getConfig('host')} / {$connection->getDatabaseName()}");

        if (! $this->option('force')) {
            $this->error('Tabel akan dihapus permanen. Ulangi dengan --force setelah ada backup.');

            return self::FAILURE;
        }

        $schema = Schema::connection($name);

        if ($schema->hasTable('users')) {
            $missing = $connection->table('users')->pluck('id')->diff(User::query()->pluck('id'));

            if ($missing->isNotEmpty()) {
                $this->error('Dibatalkan: pengguna ini belum ada di pusat: '.$missing->implode(', '));

                return self::FAILURE;
            }
        }

        $schema->withoutForeignKeyConstraints(function () use ($schema) {
            foreach (IdentityTables::NAMES as $table) {
                $schema->dropIfExists($table);
            }
        });

        $this->info('Tabel identitas lama dihapus.');

        return self::SUCCESS;
    }
}
