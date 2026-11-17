<?php

namespace Tests\Feature\MultiUnit;

use App\Models\Central\CentralAuditTrail;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Models\User;
use Tests\MultiUnitTestCase;

class UserAccessManagementTest extends MultiUnitTestCase
{
    private function newUserPayload(array $units, array $overrides = []): array
    {
        return array_merge([
            'name' => 'Staf Baru',
            'email' => 'baru@contoh.test',
            'password' => 'Rahasia123!',
            'password_confirmation' => 'Rahasia123!',
            'role' => 'staff',
            'unit_ids' => array_map(fn ($unit) => $unit->id, $units),
        ], $overrides);
    }

    public function test_finance_can_give_staff_units_they_own(): void
    {
        $finance = $this->userWithRole('finance', [$this->unitA, $this->unitB]);

        $this->actingInUnit($finance, $this->unitA)->post('/app/users', $this->newUserPayload([$this->unitB]));

        $created = User::where('email', 'baru@contoh.test')->firstOrFail();
        $this->assertSame(['unit_b'], $created->accessibleUnits()->pluck('code')->all());
        $this->assertSame($finance->id, (int) $created->units()->first()->pivot->granted_by);
    }

    public function test_finance_cannot_give_a_unit_they_do_not_own(): void
    {
        $finance = $this->userWithRole('finance', [$this->unitA]);

        $this->actingInUnit($finance, $this->unitA)->post('/app/users', $this->newUserPayload([$this->unitC]))->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'baru@contoh.test'], 'central');
    }

    public function test_finance_cannot_edit_another_finance_or_create_finance(): void
    {
        $finance = $this->userWithRole('finance', [$this->unitA]);
        $other = $this->userWithRole('finance', [$this->unitA]);

        $this->actingInUnit($finance, $this->unitA)->post('/app/users', $this->newUserPayload([$this->unitA], ['role' => 'finance']));
        $this->assertDatabaseMissing('users', ['email' => 'baru@contoh.test'], 'central');

        $this->actingInUnit($finance, $this->unitA)->put("/app/users/{$other->id}", [
            'name' => 'Diubah', 'email' => $other->email, 'role' => 'staff', 'unit_ids' => [$this->unitA->id],
        ]);
        $this->assertNotSame('Diubah', $other->fresh()->name);
    }

    public function test_nobody_can_change_their_own_access_through_user_management(): void
    {
        $finance = $this->userWithRole('finance', [$this->unitA]);

        $this->actingInUnit($finance, $this->unitA)->put("/app/users/{$finance->id}", [
            'name' => $finance->name, 'email' => $finance->email, 'role' => 'finance', 'unit_ids' => [$this->unitA->id, $this->unitB->id],
        ]);

        $this->assertSame(['unit_a'], $finance->fresh()->accessibleUnits()->pluck('code')->all());
    }

    public function test_finance_edit_keeps_units_outside_their_reach(): void
    {
        $finance = $this->userWithRole('finance', [$this->unitA]);
        $staff = $this->userWithRole('staff', [$this->unitA, $this->unitC]);

        $this->actingInUnit($finance, $this->unitA)->put("/app/users/{$staff->id}", [
            'name' => $staff->name, 'email' => $staff->email, 'role' => 'staff', 'unit_ids' => [$this->unitA->id],
        ]);

        $this->assertEqualsCanonicalizing(['unit_a', 'unit_c'], $staff->fresh()->accessibleUnits()->pluck('code')->all());
    }

    public function test_at_least_one_unit_is_required(): void
    {
        $owner = $this->userWithRole('owner');

        $this->actingInUnit($owner, $this->unitA)->post('/app/users', $this->newUserPayload([]))->assertSessionHasErrors('unit_ids');
    }

    public function test_access_changes_are_audited_centrally(): void
    {
        $owner = $this->userWithRole('owner');
        $staff = $this->userWithRole('staff', [$this->unitA]);

        $this->actingInUnit($owner, $this->unitA)->put("/app/users/{$staff->id}", [
            'name' => $staff->name, 'email' => $staff->email, 'role' => 'staff', 'unit_ids' => [$this->unitB->id],
        ]);

        $audit = CentralAuditTrail::where('action', 'user.updated')->where('subject_id', $staff->id)->firstOrFail();
        $this->assertSame($owner->id, (int) $audit->user_id);
        $this->assertSame([$this->unitA->id], $audit->old_data['unit_ids']);
        $this->assertSame([$this->unitB->id], $audit->new_data['unit_ids']);
    }

    public function test_deleting_a_user_deactivates_instead(): void
    {
        $owner = $this->userWithRole('owner');
        $staff = $this->userWithRole('staff', [$this->unitA]);

        $this->actingInUnit($owner, $this->unitA)->delete("/app/users/{$staff->id}");

        $this->assertTrue($staff->fresh()->isDeactivated());
        $this->assertSame([], $staff->fresh()->accessibleUnits()->pluck('code')->all());
    }

    public function test_audit_trail_search_by_user_name_works_across_databases(): void
    {
        $owner = $this->userWithRole('owner');

        $this->actingInUnit($owner, $this->unitA)->get('/app/audit-trail?search=Budi')->assertOk();
    }

    public function test_journal_creator_name_loads_from_central(): void
    {
        $owner = User::factory()->create(['name' => 'Pembuat Jurnal'])->assignRole('owner');

        $entry = $this->context()->run($this->unitA, function () use ($owner) {
            $period = FiscalPeriod::orderBy('start_date')->firstOrFail();

            return JournalEntry::create([
                'entry_date' => $period->start_date, 'description' => 'x',
                'fiscal_period_id' => $period->id, 'created_by' => $owner->id, 'status' => 'draft',
            ])->load('creator');
        });

        $this->assertSame('Pembuat Jurnal', $entry->creator->name);
    }
}
