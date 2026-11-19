<?php

namespace Tests\Feature\MultiUnit;

use App\Models\AuditTrail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\MultiUnitTestCase;

class AuditTrailTimezoneTest extends MultiUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->context()->activate($this->unitA);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function rawCreatedAt(AuditTrail $audit): string
    {
        return (string) DB::table('audit_trails')->where('id', $audit->id)->value('created_at');
    }

    public function test_existing_wib_value_is_read_as_wib(): void
    {
        // Seperti baris lama di production: diisi MySQL dengan jam WIB.
        $id = DB::table('audit_trails')->insertGetId([
            'table_name' => 'journal_entries',
            'record_id' => 1,
            'action' => 'correct_posted',
            'created_at' => '2026-11-19 16:44:19',
        ]);

        $audit = AuditTrail::findOrFail($id);

        $this->assertSame('2026-11-19T09:44:19+00:00', $audit->created_at->toIso8601String());
        $this->assertSame('2026-11-19 16:44', $audit->created_at->setTimezone('Asia/Jakarta')->format('Y-m-d H:i'));
        $this->assertStringStartsWith('2026-11-19T09:44:19', $audit->toArray()['created_at']);
    }

    public function test_new_rows_are_stored_as_wib_regardless_of_database_timezone(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-19 09:44:19', 'UTC'));

        $audit = AuditTrail::create([
            'table_name' => 'journal_entries',
            'record_id' => 1,
            'action' => 'update',
        ]);

        $this->assertSame('2026-11-19 16:44:19', $this->rawCreatedAt($audit));
        $this->assertSame('2026-11-19T09:44:19+00:00', $audit->fresh()->created_at->toIso8601String());
    }

    public function test_today_filter_uses_wib_date(): void
    {
        $owner = $this->userWithRole('owner', [$this->unitA]);

        // 1 Jan 2030 01.30 WIB = 31 Des 2029 18.30 UTC. Harus muncul di filter tanggal 1 Jan.
        Carbon::setTestNow(Carbon::parse('2029-12-31 18:30:00', 'UTC'));
        AuditTrail::create(['table_name' => 'journal_entries', 'record_id' => 7, 'action' => 'update']);

        $this->actingInUnit($owner, $this->unitA)
            ->get('/app/audit-trail?start_date=2030-01-01&end_date=2030-01-01')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('auditTrails.total', 1));

        $this->actingInUnit($owner, $this->unitA)
            ->get('/app/audit-trail?start_date=2029-12-31&end_date=2029-12-31')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('auditTrails.total', 0));
    }
}
