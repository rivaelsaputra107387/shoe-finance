<?php

namespace Tests\Feature\MultiUnit;

use App\Models\Account;
use App\Models\Central\CalendarPeriod;
use App\Models\Central\CentralAuditTrail;
use App\Models\Central\CoaMaster;
use App\Models\Central\Unit;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\MultiUnitTestCase;

class UnitLifecycleTest extends MultiUnitTestCase
{
    private const DATABASE = 'finlog_test_new_unit';

    protected function tearDown(): void
    {
        $this->app['db']->connection('unit')->statement('DROP DATABASE IF EXISTS `'.self::DATABASE.'`');

        parent::tearDown();
    }

    private function create(array $options = []): int
    {
        CalendarPeriod::firstOrCreate(['start_date' => '2026-10-01'], ['name' => 'Oktober 2026', 'end_date' => '2026-10-31']);

        return Artisan::call('finlog:create-unit', array_merge([
            'code' => 'baru',
            '--name' => 'Unit Baru',
            '--database' => self::DATABASE,
            '--force' => true,
        ], $options));
    }

    public function test_new_unit_is_complete_and_in_preparation(): void
    {
        $this->assertSame(0, $this->create(), Artisan::output());

        $unit = Unit::where('code', 'baru')->firstOrFail();
        $this->assertTrue($unit->isInPreparation());

        $this->context()->run($unit, function () {
            $this->assertSame(
                CoaMaster::where('is_locked', true)->count() + CoaMaster::where('is_template', true)->count() + 12,
                Account::count()
            );
            $this->assertTrue((bool) Account::where('code', '6100')->value('is_locked'));
            $this->assertFalse((bool) Account::where('code', '6110')->value('is_locked'));
            $this->assertTrue(FiscalPeriod::where('start_date', '2026-10-01')->exists());
            $this->assertFalse(FiscalPeriod::where('start_date', '2026-07-01')->exists());
            $this->assertFalse(Schema::hasTable('users'));
        });
    }

    public function test_other_units_get_intercompany_accounts_for_the_new_unit(): void
    {
        $this->create();

        $unit = Unit::where('code', 'baru')->firstOrFail();
        $code = (string) (1131 + $unit->intercompany_slot);

        $this->context()->run($this->unitA, function () use ($code) {
            $this->assertSame('baru', Account::where('code', $code)->value('counterparty_unit_code'));
        });
    }

    public function test_creation_can_be_rerun_safely(): void
    {
        $this->create();
        $unit = Unit::where('code', 'baru')->firstOrFail();
        $count = $this->context()->run($unit, fn () => Account::count());

        $this->assertSame(0, $this->create());

        $this->assertSame(1, Unit::where('code', 'baru')->count());
        $this->assertSame($count, $this->context()->run($unit, fn () => Account::count()));
    }

    public function test_invalid_names_are_rejected_before_touching_anything(): void
    {
        $this->assertSame(1, $this->create(['code' => 'Salah Kode']));
        $this->assertSame(1, $this->create(['--database' => 'bad`; DROP DATABASE x']));
        $this->assertFalse(Unit::where('code', 'baru')->exists());
    }

    public function test_database_of_another_unit_is_refused(): void
    {
        $this->assertSame(1, $this->create(['--database' => $this->unitA->database]));
    }

    public function test_activation_requires_opening_balance_or_a_reason(): void
    {
        $this->create();

        $this->assertSame(1, Artisan::call('finlog:activate-unit', ['code' => 'baru']));
        $this->assertSame(1, Artisan::call('finlog:activate-unit', ['code' => 'baru', '--allow-without-opening' => true]));
        $this->assertTrue(Unit::where('code', 'baru')->first()->isInPreparation());

        $this->assertSame(0, Artisan::call('finlog:activate-unit', ['code' => 'baru', '--allow-without-opening' => true, '--reason' => 'uji']));
        $this->assertTrue(Unit::where('code', 'baru')->first()->isActive());

        $audit = CentralAuditTrail::where('action', 'unit.activated')->latest('id')->firstOrFail();
        $this->assertSame('uji', $audit->new_data['reason']);
    }

    public function test_activation_succeeds_once_opening_balance_is_posted(): void
    {
        $this->create();
        $unit = Unit::where('code', 'baru')->firstOrFail();

        $this->context()->run($unit, fn () => JournalEntry::create([
            'entry_date' => '2026-10-01', 'description' => 'Saldo awal', 'created_by' => 1, 'status' => 'posted', 'is_opening' => true,
            'fiscal_period_id' => FiscalPeriod::where('start_date', '2026-10-01')->value('id'),
        ]));

        $this->assertSame(0, Artisan::call('finlog:activate-unit', ['code' => 'baru']));
    }

    public function test_deactivated_unit_keeps_data_and_is_read_only(): void
    {
        $this->assertSame(0, Artisan::call('finlog:deactivate-unit', ['code' => 'unit_c', '--force' => true]));

        $unit = $this->unitC->fresh();
        $this->assertTrue($unit->isInactive());
        $this->assertGreaterThan(0, $this->context()->run($unit, fn () => Account::count()));

        $owner = User::factory()->create()->assignRole('owner');
        $this->actingInUnit($owner, $unit)->get('/app/draft-journals')->assertOk();
        $this->actingInUnit($owner, $unit)->post('/app/accounts', [])->assertForbidden();
    }
}
