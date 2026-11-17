<?php

namespace App\Console\Commands;

use App\Models\Central\Unit;
use App\Models\User;
use App\Support\UnitContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Memindahkan pengguna dan role dari database lama ke pusat dengan ID yang sama, karena jurnal,
 * mutasi, dan audit trail menyimpan ID pengguna. Database sumber hanya dibaca.
 */
class MigrateIdentityCommand extends Command
{
    private const COPIED_TABLES = [
        'permissions',
        'roles',
        'role_has_permissions',
        'model_has_roles',
        'model_has_permissions',
        'personal_access_tokens',
        'notifications',
    ];

    protected $signature = 'finlog:migrate-identity
        {--code=shoeworkshop : Kode unit}
        {--name=Shoeworkshop : Nama unit}
        {--database=sql_finlog_shoew : Database sumber, yang menjadi database unit ini}
        {--start=2026-10-01 : Awal pembukuan unit}
        {--dry-run : Hanya tampilkan yang akan dipindah}
        {--force : Jalankan tanpa konfirmasi}';

    protected $description = 'Salin pengguna dan role dari database lama ke pusat, lalu daftarkan unitnya.';

    public function handle(UnitContext $context): int
    {
        $unit = new Unit([
            'code' => $this->option('code'),
            'name' => $this->option('name'),
            'database' => $this->option('database'),
            'status' => Unit::STATUS_ACTIVE,
            'books_start_date' => $this->option('start'),
            'activated_at' => now(),
            'intercompany_slot' => (int) Unit::query()->max('intercompany_slot') + 1,
        ]);

        $source = DB::connection($context->connectionName($unit));
        $central = DB::connection('central');

        $this->info("Sumber: {$source->getConfig('host')} / {$source->getDatabaseName()}");
        $this->info("Tujuan: {$central->getConfig('host')} / {$central->getDatabaseName()}");

        if (! Schema::connection($source->getName())->hasTable('users')) {
            $this->error('Tabel users tidak ada di database sumber.');

            return self::FAILURE;
        }

        if ($central->table('users')->exists() || $central->table('roles')->exists()) {
            $this->error('Dibatalkan: database pusat sudah berisi pengguna atau role.');

            return self::FAILURE;
        }

        if (Unit::where('code', $unit->code)->exists()) {
            $this->error("Dibatalkan: unit {$unit->code} sudah terdaftar.");

            return self::FAILURE;
        }

        if ($source->table('jobs')->exists()) {
            $this->error('Dibatalkan: antrean jobs di database sumber masih berisi. Selesaikan dulu.');

            return self::FAILURE;
        }

        $users = $source->table('users')->orderBy('id')->get();

        $this->table(['ID', 'Nama', 'Email'], $users->map(fn ($user) => [$user->id, $user->name, $user->email])->all());

        foreach (self::COPIED_TABLES as $table) {
            $this->line("{$table}: ".$source->table($table)->count().' baris');
        }

        if ($this->option('dry-run')) {
            $this->warn('Dry-run: tidak ada yang ditulis.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Lanjutkan pemindahan?')) {
            return self::FAILURE;
        }

        $central->transaction(function () use ($central, $source, $users, $unit) {
            foreach ($users as $user) {
                $central->table('users')->insert([
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone ?? null,
                    'profile_photo_path' => $user->profile_photo_path ?? null,
                    'email_verified_at' => $user->email_verified_at,
                    'password' => $user->password,
                    'remember_token' => $user->remember_token,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ]);
            }

            foreach (self::COPIED_TABLES as $table) {
                $rows = $source->table($table)->get()->map(fn ($row) => (array) $row)->all();

                if ($rows !== []) {
                    $central->table($table)->insert($rows);
                }
            }

            $unit->save();

            // Owner otomatis mengakses semua unit, jadi tidak perlu baris akses.
            $unit->users()->syncWithoutDetaching(
                User::query()->get()->reject(fn (User $user) => $user->hasRole('owner'))->pluck('id')->all()
            );
        });

        $this->info('Selesai: '.User::query()->count()." pengguna dipindah, dan unit {$unit->code} terdaftar.");

        return self::SUCCESS;
    }
}
