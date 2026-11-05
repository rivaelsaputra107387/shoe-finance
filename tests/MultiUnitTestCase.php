<?php

namespace Tests;

use App\Models\Central\Unit;
use App\Models\User;
use App\Support\UnitContext;
use Database\Seeders\AccountSeeder;
use Database\Seeders\FiscalPeriodSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Tiga unit nyata di database uji. Skema dibuat sekali per proses, dan setiap tes
 * berjalan di dalam transaksi semua koneksi sehingga datanya hilang setelah tes selesai.
 */
abstract class MultiUnitTestCase extends TestCase
{
    private const UNITS = ['unit_a', 'unit_b', 'unit_c'];

    private static bool $prepared = false;

    protected Unit $unitA;

    protected Unit $unitB;

    protected Unit $unitC;

    private array $transactedConnections = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        if (! self::$prepared) {
            $this->prepareDatabases();
            self::$prepared = true;
        }

        [$this->unitA, $this->unitB, $this->unitC] = array_map(
            fn (string $code) => Unit::where('code', $code)->firstOrFail(),
            self::UNITS
        );

        $this->beginTransactions();
    }

    protected function context(): UnitContext
    {
        return app(UnitContext::class);
    }

    /**
     * Masuk sebagai pengguna dengan unit aktif, seperti halaman yang sudah memilih pembukuan.
     */
    protected function actingInUnit(User $user, Unit $unit): static
    {
        return $this->actingAs($user)
            ->withSession(['active_unit_id' => $unit->id])
            ->withHeader('X-Finlog-Unit', $unit->code);
    }

    protected function userWithRole(string $role, array $units = []): User
    {
        $user = User::factory()->create()->assignRole($role);
        $user->units()->sync(collect($units)->pluck('id')->all());

        return $user;
    }

    private function prepareDatabases(): void
    {
        $context = $this->context();

        $this->assertTestDatabase(config('database.connections.central.database'));
        Artisan::call('migrate:fresh', ['--database' => 'central', '--path' => 'database/migrations/central', '--force' => true]);
        $this->seed(RolePermissionSeeder::class);

        foreach (self::UNITS as $index => $code) {
            $database = "finlog_test_{$code}";
            $this->assertTestDatabase($database);

            $unit = Unit::create([
                'code' => $code,
                'name' => 'Unit '.strtoupper(substr($code, -1)),
                'database' => $database,
                'status' => Unit::STATUS_ACTIVE,
                'books_start_date' => '2026-07-01',
                'sort_order' => $index,
            ]);

            Artisan::call('migrate:fresh', ['--database' => $context->connectionName($unit), '--path' => 'database/migrations/unit', '--force' => true]);
            Artisan::call('finlog:drop-identity-tables', ['unit' => $code, '--force' => true]);

            $context->run($unit, fn () => $this->seed([FiscalPeriodSeeder::class, AccountSeeder::class]));
        }
    }

    private function beginTransactions(): void
    {
        $context = $this->context();

        $this->transactedConnections = ['central', ...array_map(
            fn (Unit $unit) => $context->connectionName($unit),
            [$this->unitA, $this->unitB, $this->unitC]
        )];

        foreach ($this->transactedConnections as $name) {
            DB::connection($name)->beginTransaction();
        }

        $this->beforeApplicationDestroyed(function () {
            foreach ($this->transactedConnections as $name) {
                DB::connection($name)->rollBack();
                DB::purge($name);
            }
        });
    }

    private function assertTestDatabase(?string $database): void
    {
        if (! is_string($database) || ! str_starts_with($database, 'finlog_test')) {
            throw new RuntimeException("Tes dibatalkan: database '{$database}' bukan database tes.");
        }
    }
}
