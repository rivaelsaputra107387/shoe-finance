<?php

namespace Tests\Feature\MultiUnit;

use App\Models\BankMutation;
use App\Models\Central\Unit;
use Tests\MultiUnitTestCase;

class UnitAccessTest extends MultiUnitTestCase
{
    private function mutationPayload(): array
    {
        return ['date' => '2026-10-05', 'bank_source' => 'BCA', 'mutation_type' => 'IN', 'amount' => 1000, 'description' => 'Uji'];
    }

    public function test_user_sees_only_allowed_units(): void
    {
        $user = $this->userWithRole('finance', [$this->unitA, $this->unitB]);

        $this->assertEqualsCanonicalizing(['unit_a', 'unit_b'], $user->accessibleUnits()->pluck('code')->all());
    }

    public function test_owner_automatically_accesses_all_units_including_new_ones(): void
    {
        $owner = $this->userWithRole('owner');

        Unit::create(['code' => 'baru', 'name' => 'Baru', 'database' => 'finlog_test_baru', 'status' => 'persiapan', 'books_start_date' => '2026-10-01']);

        $this->assertContains('baru', $owner->fresh()->accessibleUnits()->pluck('code')->all());
    }

    public function test_staff_does_not_see_units_in_preparation(): void
    {
        $this->unitB->update(['status' => Unit::STATUS_PREPARATION]);

        $staff = $this->userWithRole('staff', [$this->unitA, $this->unitB]);
        $finance = $this->userWithRole('finance', [$this->unitA, $this->unitB]);

        $this->assertSame(['unit_a'], $staff->accessibleUnits()->pluck('code')->all());
        $this->assertContains('unit_b', $finance->accessibleUnits()->pluck('code')->all());
    }

    public function test_selecting_a_forbidden_unit_is_rejected(): void
    {
        $user = $this->userWithRole('staff', [$this->unitA]);

        $this->actingAs($user)->post('/app/units/select', ['unit_id' => $this->unitB->id])->assertForbidden();
        $this->assertNull(session('active_unit_id'));
    }

    public function test_forbidden_unit_in_session_is_rejected_on_read(): void
    {
        $user = $this->userWithRole('staff', [$this->unitA]);

        $this->actingInUnit($user, $this->unitB)->get('/app/draft-journals')->assertRedirect('/app/units');
        $this->assertNull(session('active_unit_id'));
    }

    public function test_forbidden_unit_in_session_cannot_write(): void
    {
        $user = $this->userWithRole('staff', [$this->unitA]);

        $this->actingInUnit($user, $this->unitB)->post('/app/bank-mutations', $this->mutationPayload())->assertForbidden();

        $this->assertSame(0, $this->context()->run($this->unitB, fn () => BankMutation::count()));
    }

    public function test_revoked_access_applies_on_the_next_request(): void
    {
        $user = $this->userWithRole('staff', [$this->unitA]);
        $this->actingInUnit($user, $this->unitA)->get('/app/draft-journals')->assertOk();

        $user->units()->detach();

        $this->actingInUnit($user->fresh(), $this->unitA)->get('/app/draft-journals')->assertRedirect('/app/units');
    }

    public function test_user_without_units_cannot_open_bookkeeping(): void
    {
        $user = $this->userWithRole('staff');

        $this->actingAs($user)->get('/app/dashboard')->assertRedirect('/app/units');
        $this->actingAs($user)->get('/app/units')->assertOk();
    }

    public function test_single_unit_user_enters_directly(): void
    {
        $user = $this->userWithRole('staff', [$this->unitA]);

        $this->actingAs($user)->get('/app/units')->assertRedirect('/app/dashboard');
        $this->assertSame($this->unitA->id, session('active_unit_id'));
    }

    public function test_multi_unit_user_picks_a_unit_then_enters_it(): void
    {
        $user = $this->userWithRole('finance', [$this->unitA, $this->unitB]);

        $this->actingAs($user)->get('/app/units')->assertOk();
        $this->actingAs($user)->post('/app/units/select', ['unit_id' => $this->unitB->id])->assertRedirect('/app/dashboard');
        $this->assertSame($this->unitB->id, session('active_unit_id'));
    }

    public function test_inactive_unit_is_read_only(): void
    {
        $this->unitA->update(['status' => Unit::STATUS_INACTIVE]);
        $user = $this->userWithRole('finance', [$this->unitA]);

        $this->actingInUnit($user, $this->unitA)->get('/app/draft-journals')->assertOk();
        $this->actingInUnit($user, $this->unitA)->post('/app/bank-mutations', $this->mutationPayload())->assertForbidden();
    }

    public function test_deactivated_user_is_logged_out(): void
    {
        $user = $this->userWithRole('finance', [$this->unitA]);
        $user->forceFill(['deactivated_at' => now()])->save();

        $this->actingInUnit($user, $this->unitA)->get('/app/draft-journals')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_deactivated_user_cannot_log_in(): void
    {
        $user = $this->userWithRole('finance', [$this->unitA]);
        $user->forceFill(['deactivated_at' => now()])->save();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_goes_to_unit_selection(): void
    {
        $user = $this->userWithRole('finance', [$this->unitA, $this->unitB]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/app/units');
    }

    public function test_consolidated_access_requires_all_active_units(): void
    {
        $this->assertFalse($this->userWithRole('finance', [$this->unitA, $this->unitB])->canViewConsolidated());
        $this->assertTrue($this->userWithRole('finance', [$this->unitA, $this->unitB, $this->unitC])->canViewConsolidated());
        $this->assertTrue($this->userWithRole('owner')->canViewConsolidated());
        $this->assertFalse($this->userWithRole('staff', [$this->unitA, $this->unitB, $this->unitC])->canViewConsolidated());
    }
}
