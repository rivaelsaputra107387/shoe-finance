<?php

namespace Tests\Feature\MultiUnit;

use App\Models\BankMutation;
use Tests\MultiUnitTestCase;

class TabConflictTest extends MultiUnitTestCase
{
    private function payload(): array
    {
        return ['date' => '2026-10-05', 'bank_source' => 'BCA', 'mutation_type' => 'IN', 'amount' => 1000, 'description' => 'Dari tab lama'];
    }

    private function mutationCount($unit): int
    {
        return $this->context()->run($unit, fn () => BankMutation::count());
    }

    public function test_write_from_a_tab_showing_another_unit_is_rejected(): void
    {
        $user = $this->userWithRole('finance', [$this->unitA, $this->unitB]);

        // Sesi sudah pindah ke B, tetapi tab masih menampilkan A.
        $response = $this->actingAs($user)
            ->withSession(['active_unit_id' => $this->unitB->id])
            ->withHeader('X-Finlog-Unit', $this->unitA->code)
            ->post('/app/bank-mutations', $this->payload());

        $response->assertStatus(409)->assertJson(['code' => 'unit_conflict', 'active_unit' => $this->unitB->name]);

        $this->assertSame(0, $this->mutationCount($this->unitA));
        $this->assertSame(0, $this->mutationCount($this->unitB));
    }

    public function test_write_without_unit_header_is_rejected(): void
    {
        $user = $this->userWithRole('finance', [$this->unitA]);

        $this->actingAs($user)
            ->withSession(['active_unit_id' => $this->unitA->id])
            ->post('/app/bank-mutations', $this->payload())
            ->assertStatus(409);

        $this->assertSame(0, $this->mutationCount($this->unitA));
    }

    public function test_write_with_matching_unit_is_accepted(): void
    {
        $user = $this->userWithRole('finance', [$this->unitA]);

        $this->actingInUnit($user, $this->unitA)->post('/app/bank-mutations', $this->payload());

        $this->assertSame(1, $this->mutationCount($this->unitA));
    }

    public function test_export_link_from_another_unit_is_rejected(): void
    {
        $user = $this->userWithRole('finance', [$this->unitA, $this->unitB]);

        $this->actingAs($user)
            ->withSession(['active_unit_id' => $this->unitB->id])
            ->get("/app/trial-balance/export?unit={$this->unitA->code}&format=excel")
            ->assertStatus(409);
    }

    public function test_plain_navigation_without_unit_is_allowed(): void
    {
        $user = $this->userWithRole('finance', [$this->unitA]);

        $this->actingAs($user)
            ->withSession(['active_unit_id' => $this->unitA->id])
            ->get('/app/draft-journals')
            ->assertOk();
    }
}
