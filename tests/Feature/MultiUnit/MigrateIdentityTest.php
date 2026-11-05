<?php

namespace Tests\Feature\MultiUnit;

use App\Models\Central\Unit;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\MultiUnitTestCase;

class MigrateIdentityTest extends MultiUnitTestCase
{
    private const SOURCE = 'finlog_test_identity';

    private static bool $sourceReady = false;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.connections.identity_source', array_merge(config('database.connections.unit'), ['database' => self::SOURCE]));

        if (! self::$sourceReady) {
            Artisan::call('migrate:fresh', ['--database' => 'identity_source', '--path' => 'database/migrations/unit', '--force' => true]);
            self::$sourceReady = true;
        }

        $source = DB::connection('identity_source');
        Schema::connection('identity_source')->withoutForeignKeyConstraints(function () use ($source) {
            foreach (['model_has_roles', 'role_has_permissions', 'roles', 'permissions', 'users'] as $table) {
                $source->table($table)->delete();
            }
        });

        $source->table('users')->insert([
            ['id' => 7, 'name' => 'Pemilik', 'email' => 'pemilik@contoh.test', 'password' => 'hash-asli-7', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 12, 'name' => 'Keuangan', 'email' => 'keuangan@contoh.test', 'password' => 'hash-asli-12', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $source->table('roles')->insert([
            ['id' => 1, 'name' => 'owner', 'guard_name' => 'web'],
            ['id' => 3, 'name' => 'finance', 'guard_name' => 'web'],
        ]);
        $source->table('model_has_roles')->insert([
            ['role_id' => 1, 'model_type' => User::class, 'model_id' => 7],
            ['role_id' => 3, 'model_type' => User::class, 'model_id' => 12],
        ]);

        // Pusat kosong, seperti saat perpindahan. Dikembalikan oleh rollback di akhir tes.
        foreach (['model_has_roles', 'role_has_permissions', 'unit_user'] as $table) {
            DB::connection('central')->table($table)->delete();
        }
        DB::connection('central')->table('roles')->delete();
        DB::connection('central')->table('permissions')->delete();
        DB::connection('central')->table('users')->delete();
    }

    private function runCommand(array $options = []): int
    {
        return Artisan::call('finlog:migrate-identity', array_merge([
            '--code' => 'asal',
            '--name' => 'Asal',
            '--database' => self::SOURCE,
            '--force' => true,
        ], $options));
    }

    public function test_users_keep_id_password_and_role(): void
    {
        $this->assertSame(0, $this->runCommand());

        $finance = User::findOrFail(12);
        $this->assertSame('keuangan@contoh.test', $finance->email);
        $this->assertSame('hash-asli-12', $finance->getAuthPassword());
        $this->assertTrue($finance->hasRole('finance'));
        $this->assertTrue(User::findOrFail(7)->hasRole('owner'));
    }

    public function test_unit_is_registered_and_non_owners_get_access(): void
    {
        $this->runCommand();

        $unit = Unit::where('code', 'asal')->firstOrFail();
        $this->assertSame(self::SOURCE, $unit->database);
        $this->assertTrue($unit->isActive());
        $this->assertSame(['asal'], User::findOrFail(12)->units()->pluck('code')->all());
        $this->assertSame([], User::findOrFail(7)->units()->pluck('code')->all());
    }

    public function test_dry_run_writes_nothing(): void
    {
        $this->assertSame(0, $this->runCommand(['--dry-run' => true]));

        $this->assertSame(0, User::query()->count());
        $this->assertFalse(Unit::where('code', 'asal')->exists());
    }

    public function test_refuses_when_central_already_has_users(): void
    {
        $this->runCommand();

        $this->assertSame(1, $this->runCommand(['--code' => 'lain']));
    }

    public function test_refuses_when_source_queue_is_not_empty(): void
    {
        DB::connection('identity_source')->table('jobs')->insert([
            'queue' => 'default', 'payload' => '{}', 'attempts' => 0, 'available_at' => 0, 'created_at' => 0,
        ]);

        $this->assertSame(1, $this->runCommand());

        DB::connection('identity_source')->table('jobs')->delete();
    }

    public function test_identity_tables_can_only_be_dropped_when_users_exist_centrally(): void
    {
        $unit = $this->unitA;
        $connection = $this->context()->connectionName($unit);

        Schema::connection($connection)->create('users', function ($table) {
            $table->id();
        });

        DB::connection($connection)->table('users')->insert(['id' => 999]);

        try {
            $this->assertSame(1, Artisan::call('finlog:drop-identity-tables', ['unit' => $unit->code, '--force' => true]));
            $this->assertTrue(Schema::connection($connection)->hasTable('users'));
        } finally {
            Schema::connection($connection)->dropIfExists('users');
        }
    }
}
