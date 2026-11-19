<?php

namespace Tests\Feature;

use App\Models\AuditTrail;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuditTrailTimezoneTest extends TestCase
{
    use RefreshDatabase;

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
        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
        $owner = User::factory()->create()->assignRole('owner');

        // 19 Nov 01.30 WIB = 18 Nov 18.30 UTC. Harus muncul di filter tanggal 19 Nov.
        Carbon::setTestNow(Carbon::parse('2026-11-18 18:30:00', 'UTC'));
        AuditTrail::create(['table_name' => 'journal_entries', 'record_id' => 7, 'action' => 'update']);

        $this->actingAs($owner)
            ->get('/app/audit-trail?start_date=2026-11-19&end_date=2026-11-19')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('auditTrails.total', 1));

        $this->actingAs($owner)
            ->get('/app/audit-trail?start_date=2026-11-18&end_date=2026-11-18')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('auditTrails.total', 0));
    }
}
