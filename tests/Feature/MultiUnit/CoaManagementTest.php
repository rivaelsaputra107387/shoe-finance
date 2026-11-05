<?php

namespace Tests\Feature\MultiUnit;

use App\Models\Account;
use App\Models\Central\CoaMaster;
use App\Models\Central\Unit;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;
use App\Services\Coa\CoaSynchronizer;
use App\Services\Coa\IntercompanyAccounts;
use Illuminate\Support\Facades\Artisan;
use Tests\MultiUnitTestCase;

class CoaManagementTest extends MultiUnitTestCase
{
    private User $finance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finance = $this->userWithRole('finance', [$this->unitA, $this->unitB]);
        $this->context()->activate($this->unitA);
    }

    private function act(): static
    {
        return $this->actingInUnit($this->finance, $this->unitA);
    }

    private function subAccountPayload(array $overrides = []): array
    {
        return array_merge([
            'code' => '6991',
            'name' => 'Beban Uji',
            'parent_id' => Account::where('code', '6100')->value('id'),
        ], $overrides);
    }

    public function test_locked_account_cannot_be_changed_or_deleted(): void
    {
        app(CoaSynchronizer::class)->sync($this->unitA);
        $locked = Account::where('code', '3110')->firstOrFail();

        $this->act()->put("/app/accounts/{$locked->id}", ['code' => '3199', 'name' => 'Ganti', 'parent_id' => $locked->parent_id])->assertSessionHas('error');
        $this->act()->delete("/app/accounts/{$locked->id}")->assertSessionHas('error');
        $this->act()->post('/app/accounts/bulk-delete', ['ids' => [$locked->id]]);

        $this->assertSame('3110', $locked->fresh()->code);
        $this->assertNotSoftDeleted($locked);
    }

    public function test_account_with_sub_accounts_cannot_be_deleted(): void
    {
        $parent = Account::where('code', '6100')->firstOrFail();

        $this->act()->delete("/app/accounts/{$parent->id}")->assertSessionHas('error');

        $this->assertNotSoftDeleted($parent);
    }

    public function test_account_with_journals_cannot_be_deleted(): void
    {
        $account = Account::where('code', '6110')->firstOrFail();
        $entry = JournalEntry::create(['entry_date' => '2026-07-01', 'description' => 'x', 'fiscal_period_id' => FiscalPeriod::value('id'), 'created_by' => $this->finance->id, 'status' => 'draft']);
        JournalEntryLine::create(['journal_entry_id' => $entry->id, 'account_id' => $account->id, 'debit' => 1, 'credit' => 0]);

        $this->act()->delete("/app/accounts/{$account->id}")->assertSessionHas('error');
        $this->assertNotSoftDeleted($account);
    }

    public function test_unused_sub_account_can_be_deleted(): void
    {
        $account = Account::where('code', '6110')->firstOrFail();

        $this->act()->delete("/app/accounts/{$account->id}")->assertSessionHas('success');

        $this->assertSoftDeleted($account);
    }

    public function test_new_account_needs_a_parent(): void
    {
        $this->act()->post('/app/accounts', $this->subAccountPayload(['parent_id' => null]))->assertSessionHasErrors('parent_id');
    }

    public function test_type_and_category_follow_the_parent(): void
    {
        $this->act()->post('/app/accounts', $this->subAccountPayload(['type' => 'Aset', 'report_category' => 'Neraca']));

        $account = Account::where('code', '6991')->firstOrFail();
        $this->assertSame('Beban', $account->type);
        $this->assertSame('Laba Rugi', $account->report_category);
        $this->assertSame('Debet', $account->normal_balance);
    }

    public function test_contra_account_may_differ_on_normal_balance(): void
    {
        $this->act()->post('/app/accounts', [
            'code' => '1299', 'name' => 'Akumulasi Uji', 'normal_balance' => 'Kredit',
            'parent_id' => Account::where('code', '1200')->value('id'),
        ]);

        $this->assertSame('Kredit', Account::where('code', '1299')->value('normal_balance'));
    }

    public function test_code_must_match_the_parent_group(): void
    {
        $this->act()->post('/app/accounts', $this->subAccountPayload(['code' => '5991']))->assertSessionHasErrors('code');

        // 2100 dan 2200 tampil di kelompok berbeda pada Neraca.
        $this->act()->post('/app/accounts', [
            'code' => '2299', 'name' => 'Utang Salah Tempat',
            'parent_id' => Account::where('code', '2100')->value('id'),
        ])->assertSessionHasErrors('code');
    }

    public function test_cash_and_bank_accounts_need_cash_codes(): void
    {
        $parent = Account::where('code', '1100')->value('id');

        $this->act()->post('/app/accounts', ['code' => '1195', 'name' => 'Bank BRI', 'parent_id' => $parent])->assertSessionHasErrors('code');
        $this->act()->post('/app/accounts', ['code' => '1125', 'name' => 'Bank BRI', 'parent_id' => $parent])->assertSessionHasNoErrors();
    }

    public function test_sub_account_change_stays_in_one_unit(): void
    {
        $this->act()->post('/app/accounts', $this->subAccountPayload());

        $this->assertTrue(Account::where('code', '6991')->exists());
        $this->assertFalse($this->context()->run($this->unitB, fn () => Account::where('code', '6991')->exists()));
    }

    public function test_sync_applies_master_and_leaves_free_sub_accounts_alone(): void
    {
        $free = Account::create(['code' => '6992', 'name' => 'Sub Bebas', 'type' => 'Beban', 'normal_balance' => 'Debet', 'report_category' => 'Laba Rugi', 'parent_id' => Account::where('code', '6100')->value('id'), 'is_active' => true]);
        Account::where('code', '6100')->update(['name' => 'Nama Salah']);

        $result = app(CoaSynchronizer::class)->sync($this->unitA);

        $this->assertNotEmpty($result['changes']);
        $this->assertSame(CoaMaster::where('code', '6100')->value('name'), Account::where('code', '6100')->value('name'));
        $this->assertTrue((bool) Account::where('code', '6100')->value('is_locked'));
        $this->assertSame('Sub Bebas', $free->fresh()->name);
        $this->assertFalse($free->fresh()->is_locked);
    }

    public function test_sync_dry_run_writes_nothing(): void
    {
        Account::where('code', '6100')->update(['name' => 'Nama Salah']);
        $before = Account::count();

        $result = app(CoaSynchronizer::class)->sync($this->unitA, dryRun: true);

        $this->assertNotEmpty($result['changes']);
        $this->assertSame('Nama Salah', Account::where('code', '6100')->value('name'));
        $this->assertSame($before, Account::count());
    }

    public function test_sync_refuses_to_turn_an_account_with_journals_into_a_header(): void
    {
        Account::where('code', '6200')->update(['parent_id' => Account::where('code', '6100')->value('id'), 'name' => 'Sub Berisi Jurnal']);
        $account = Account::where('code', '6200')->firstOrFail();
        $entry = JournalEntry::create(['entry_date' => '2026-07-01', 'description' => 'x', 'fiscal_period_id' => FiscalPeriod::value('id'), 'created_by' => $this->finance->id, 'status' => 'draft']);
        JournalEntryLine::create(['journal_entry_id' => $entry->id, 'account_id' => $account->id, 'debit' => 1, 'credit' => 0]);

        $warnings = app(CoaSynchronizer::class)->sync($this->unitA)['warnings'];

        $this->assertStringContainsString('DILEWATI 6200', implode(' ', $warnings));
        $this->assertSame('Sub Berisi Jurnal', $account->fresh()->name);
        $this->assertNotNull($account->fresh()->parent_id);
    }

    public function test_sync_is_idempotent(): void
    {
        $synchronizer = app(CoaSynchronizer::class);
        $synchronizer->sync($this->unitA);

        $this->assertSame([], $synchronizer->sync($this->unitA)['changes']);
    }

    public function test_sync_warns_about_top_level_accounts_outside_master(): void
    {
        Account::create(['code' => '6950', 'name' => 'Induk Liar', 'type' => 'Beban', 'normal_balance' => 'Debet', 'report_category' => 'Laba Rugi', 'is_active' => true]);

        $warnings = app(CoaSynchronizer::class)->sync($this->unitA, dryRun: true)['warnings'];

        $this->assertStringContainsString('6950', implode(' ', $warnings));
    }

    public function test_intercompany_accounts_follow_the_counterparty_slot(): void
    {
        app(CoaSynchronizer::class)->sync($this->unitA);

        // Unit A berlawanan dengan B (slot 2) dan C (slot 3), bukan dengan dirinya.
        $this->assertSame('Piutang Antar Unit – Unit B', Account::where('code', '1133')->value('name'));
        $this->assertSame('unit_c', Account::where('code', '2172')->value('counterparty_unit_code'));
        $this->assertTrue((bool) Account::where('code', '4220')->value('is_intercompany'));
        $this->assertTrue(Account::where('code', '6830')->exists());
        $this->assertFalse(Account::where('code', '1132')->exists());
        $this->assertFalse(Account::where('code', '4210')->exists());
    }

    public function test_intercompany_codes_stay_inside_their_report_groups(): void
    {
        $codes = app(IntercompanyAccounts::class)->forUnit($this->unitA);

        foreach ($codes as $account) {
            $prefix = match ($account['parent_code']) {
                '1100' => '11', '2100' => '21', '4200' => '42', '6800' => '68',
            };

            $this->assertStringStartsWith($prefix, $account['code']);
        }
    }

    public function test_coa_sync_command_supports_dry_run_and_unit_filter(): void
    {
        Account::where('code', '6100')->update(['name' => 'Nama Salah']);

        $this->assertSame(0, Artisan::call('finlog:coa-sync', ['--unit' => 'unit_a', '--dry-run' => true]));
        $this->assertSame('Nama Salah', Account::where('code', '6100')->value('name'));
        $this->assertSame(1, Artisan::call('finlog:coa-sync', ['--unit' => 'tidak_ada', '--force' => true]));
    }
}
