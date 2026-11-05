<?php

namespace Tests\Feature\MultiUnit;

use App\Models\Account;
use App\Models\BankMutation;
use App\Models\Central\Unit;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;
use App\Services\IncomeStatementService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Route;
use Tests\MultiUnitTestCase;

class UnitIsolationTest extends MultiUnitTestCase
{
    private function journalIn(Unit $unit, string $description, float $amount, string $status = 'posted'): JournalEntry
    {
        return $this->context()->run($unit, function () use ($description, $amount, $status) {
            $period = FiscalPeriod::orderBy('start_date')->firstOrFail();
            $entry = JournalEntry::create([
                'entry_date' => $period->start_date,
                'description' => $description,
                'fiscal_period_id' => $period->id,
                'created_by' => User::factory()->create()->id,
                'status' => $status,
            ]);

            $cash = Account::where('code', 'like', '111%')->whereNotNull('parent_id')->firstOrFail();
            $revenue = Account::where('code', 'like', '41%')->whereNotNull('parent_id')->firstOrFail();
            JournalEntryLine::create(['journal_entry_id' => $entry->id, 'account_id' => $cash->id, 'debit' => $amount, 'credit' => 0]);
            JournalEntryLine::create(['journal_entry_id' => $entry->id, 'account_id' => $revenue->id, 'debit' => 0, 'credit' => $amount]);

            return $entry;
        });
    }

    public function test_journal_list_only_shows_the_active_unit(): void
    {
        $this->journalIn($this->unitA, 'Jurnal milik A', 100);
        $this->journalIn($this->unitB, 'Jurnal milik B', 200);
        $owner = $this->userWithRole('owner');

        $month = $this->context()->run($this->unitA, fn () => FiscalPeriod::orderBy('start_date')->first()->start_date->format('Y-m'));

        $response = $this->actingInUnit($owner, $this->unitA)->get("/app/journal-entries?month_year={$month}");

        $response->assertOk();
        $response->assertSee('Jurnal milik A');
        $response->assertDontSee('Jurnal milik B');
    }

    public function test_route_binding_reads_the_active_unit(): void
    {
        $inA = $this->journalIn($this->unitA, 'Jurnal milik A', 100);
        $this->context()->run($this->unitB, fn () => JournalEntry::forceCreate([
            'id' => $inA->id,
            'entry_date' => FiscalPeriod::value('start_date'),
            'description' => 'Jurnal milik B',
            'fiscal_period_id' => FiscalPeriod::value('id'),
            'created_by' => 1,
            'status' => 'posted',
        ]));
        $owner = $this->userWithRole('owner');

        // ID sama di dua unit menghasilkan jurnal unit yang aktif.
        $this->actingInUnit($owner, $this->unitB)->get("/app/journal-entries/{$inA->id}")
            ->assertOk()->assertSee('Jurnal milik B')->assertDontSee('Jurnal milik A');
    }

    public function test_record_that_only_exists_in_another_unit_is_not_found(): void
    {
        $inA = $this->journalIn($this->unitA, 'Jurnal A', 100);
        $owner = $this->userWithRole('owner');

        $this->actingInUnit($owner, $this->unitC)->get("/app/journal-entries/{$inA->id}")->assertNotFound();
    }

    public function test_income_statement_only_counts_the_active_unit(): void
    {
        $this->journalIn($this->unitA, 'Pendapatan A', 1000);
        $this->journalIn($this->unitB, 'Pendapatan B', 5000);

        $netProfit = fn (Unit $unit) => $this->context()->run($unit, fn () => (new IncomeStatementService)->generate(FiscalPeriod::orderBy('start_date')->value('id'))['net_profit']);

        $this->assertEquals(1000, $netProfit($this->unitA));
        $this->assertEquals(5000, $netProfit($this->unitB));
        $this->assertEquals(0, $netProfit($this->unitC));
    }

    public function test_writes_only_reach_the_active_unit(): void
    {
        $owner = $this->userWithRole('owner');
        $this->actingInUnit($owner, $this->unitB)->post('/app/bank-mutations', [
            'date' => '2026-10-05',
            'bank_source' => 'BCA',
            'mutation_type' => 'IN',
            'amount' => 1000,
            'description' => 'Mutasi B',
        ]);

        $count = fn (Unit $unit) => $this->context()->run($unit, fn () => BankMutation::count());

        $this->assertSame(0, $count($this->unitA));
        $this->assertSame(1, $count($this->unitB));
    }

    public function test_dashboard_cache_is_separated_per_unit(): void
    {
        $this->journalIn($this->unitA, 'Pendapatan A', 1000);
        $this->journalIn($this->unitB, 'Pendapatan B', 5000);
        $owner = $this->userWithRole('owner');

        $summary = fn (Unit $unit) => $this->actingInUnit($owner, $unit)->get('/app/dashboard')->assertOk()->viewData('page')['props'];

        $this->assertNotEquals($summary($this->unitA), $summary($this->unitB));
    }

    public function test_cache_keys_include_the_unit(): void
    {
        $key = fn (Unit $unit) => $this->context()->run($unit, fn () => $this->context()->cacheKey('x'));

        $this->assertNotSame($key($this->unitA), $key($this->unitB));
    }

    public function test_export_file_name_and_heading_use_the_active_unit(): void
    {
        $this->journalIn($this->unitB, 'Pendapatan B', 5000);
        $owner = $this->userWithRole('owner');
        $periodId = $this->context()->run($this->unitB, fn () => FiscalPeriod::orderBy('start_date')->value('id'));

        $response = $this->actingInUnit($owner, $this->unitB)
            ->get("/app/income-statement/export?unit=unit_b&format=pdf&fiscal_period_id={$periodId}");

        $response->assertOk();
        $this->assertStringContainsString('unit_b_', $response->headers->get('content-disposition'));

        $html = view('reports.layout-pdf')->render();
        $this->assertStringContainsString(strtoupper($this->unitB->name), $html);
    }

    public function test_context_is_restored_after_an_error(): void
    {
        $this->context()->activate($this->unitA);

        try {
            $this->context()->run($this->unitB, fn () => throw new \RuntimeException('gagal'));
        } catch (\RuntimeException) {
            // diharapkan
        }

        $this->assertSame('unit_a', $this->context()->current()->code);
    }

    public function test_query_without_unit_context_fails_loudly(): void
    {
        $this->context()->forget();

        $this->expectException(QueryException::class);

        JournalEntry::count();
    }

    public function test_every_bookkeeping_route_requires_the_unit_middleware(): void
    {
        $exempt = ['app/units', 'app/units/select'];
        $checked = 0;

        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();

            if (! str_starts_with($uri, 'app/') || in_array($uri, $exempt, true)) {
                continue;
            }

            $this->assertContains('unit', $route->gatherMiddleware(), "Rute {$uri} tidak memakai middleware unit");
            $checked++;
        }

        $this->assertGreaterThan(50, $checked);
    }
}
