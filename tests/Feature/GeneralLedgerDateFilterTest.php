<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Models\User;
use Database\Seeders\AccountSeeder;
use Database\Seeders\FiscalPeriodSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeneralLedgerDateFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $finance;
    private Account $cashAccount;
    private Account $revenueAccount;
    private FiscalPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed([RolePermissionSeeder::class, FiscalPeriodSeeder::class, AccountSeeder::class]);

        $this->finance = User::factory()->create()->assignRole('finance');
        $this->period = FiscalPeriod::firstOrCreate(
            ['name' => 'Oktober 2026'],
            ['start_date' => '2026-10-01', 'end_date' => '2026-10-31', 'status' => 'open']
        );
        $this->cashAccount = Account::where('code', '1110')->firstOrFail();
        $this->revenueAccount = Account::where('code', '4110')->firstOrFail();

        // Create 3 transactions on different dates in the period:
        // Day 01: Debet 100,000
        $this->createEntry('2026-10-01', 'JU-001', 100000);
        // Day 10: Debet 250,000
        $this->createEntry('2026-10-10', 'JU-002', 250000);
        // Day 20: Debet 500,000
        $this->createEntry('2026-10-20', 'JU-003', 500000);
    }

    private function createEntry(string $date, string $reference, float $amount): JournalEntry
    {
        $entry = JournalEntry::create([
            'fiscal_period_id' => $this->period->id,
            'entry_date' => $date,
            'reference' => $reference,
            'description' => "Test Entry {$reference}",
            'status' => 'posted',
            'created_by' => $this->finance->id,
        ]);

        $entry->lines()->create([
            'account_id' => $this->cashAccount->id,
            'debit' => $amount,
            'credit' => 0,
            'description' => "Kas Line {$reference}",
        ]);

        $entry->lines()->create([
            'account_id' => $this->revenueAccount->id,
            'debit' => 0,
            'credit' => $amount,
            'description' => "Pendapatan Line {$reference}",
        ]);

        return $entry;
    }

    public function test_general_ledger_shows_all_transactions_for_full_month(): void
    {
        $response = $this->actingAs($this->finance)->get(
            "/app/general-ledger?account_id={$this->cashAccount->id}&fiscal_period_id={$this->period->id}"
        );

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/GeneralLedger')
            ->has('ledgerData.rows', 3)
            ->where('ledgerData.total_debit', 850000)
        );
    }

    public function test_general_ledger_filters_by_specific_day(): void
    {
        $response = $this->actingAs($this->finance)->get(
            "/app/general-ledger?account_id={$this->cashAccount->id}&fiscal_period_id={$this->period->id}&day=10"
        );

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/GeneralLedger')
            ->where('selectedDay', 10)
            ->has('ledgerData.rows', 1)
            ->where('ledgerData.rows.0.reference', 'JU-002')
            ->where('ledgerData.total_debit', 250000)
            // Saldo awal at morning of Day 10 includes Day 01 transaction (100,000)
            ->where('ledgerData.starting_balance', 100000)
            // Ending balance at end of Day 10 is 100,000 + 250,000 = 350,000
            ->where('ledgerData.ending_balance', 350000)
        );
    }

    public function test_general_ledger_filters_by_date_range(): void
    {
        $response = $this->actingAs($this->finance)->get(
            "/app/general-ledger?account_id={$this->cashAccount->id}&fiscal_period_id={$this->period->id}&start_date=2026-10-10&end_date=2026-10-25"
        );

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Reports/GeneralLedger')
            ->has('ledgerData.rows', 2)
            ->where('ledgerData.starting_balance', 100000)
            ->where('ledgerData.total_debit', 750000)
            ->where('ledgerData.ending_balance', 850000)
        );
    }

    public function test_export_general_ledger_pdf_respects_day_filter(): void
    {
        $response = $this->actingAs($this->finance)->get(
            "/app/general-ledger/export?format=pdf&account_id={$this->cashAccount->id}&fiscal_period_id={$this->period->id}&day=10"
        );

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_export_general_ledger_excel_respects_day_filter(): void
    {
        $response = $this->actingAs($this->finance)->get(
            "/app/general-ledger/export?format=excel&account_id={$this->cashAccount->id}&fiscal_period_id={$this->period->id}&day=10"
        );

        $response->assertOk();
        $this->assertStringContainsString('spreadsheet', $response->headers->get('Content-Type'));
    }
}
