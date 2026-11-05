<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;
use App\Services\ReportSnapshotService;
use Illuminate\Support\Facades\DB;
use Tests\MultiUnitTestCase;

class ReportSnapshotTest extends MultiUnitTestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->context()->activate($this->unitA);

        $this->directory = sys_get_temp_dir().'/finlog-snapshot-test-'.uniqid();
        mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->directory.'/*') ?: []);
        rmdir($this->directory);

        parent::tearDown();
    }

    private function account(string $prefix): Account
    {
        return Account::active()->where('code', 'like', $prefix.'%')->whereNotNull('parent_id')->orderBy('code')->firstOrFail();
    }

    private function postJournal(float $amount): JournalEntry
    {
        $period = FiscalPeriod::orderBy('start_date')->firstOrFail();

        $entry = JournalEntry::create([
            'entry_date' => $period->start_date,
            'description' => 'Jurnal uji',
            'fiscal_period_id' => $period->id,
            'created_by' => User::factory()->create()->id,
            'status' => 'posted',
        ]);

        JournalEntryLine::create(['journal_entry_id' => $entry->id, 'account_id' => $this->account('111')->id, 'debit' => $amount, 'credit' => 0]);
        JournalEntryLine::create(['journal_entry_id' => $entry->id, 'account_id' => $this->account('41')->id, 'debit' => 0, 'credit' => $amount]);

        return $entry;
    }

    private function snapshotToFile(string $name): string
    {
        $path = "{$this->directory}/{$name}.json";

        $this->artisan('finlog:report-snapshot', ['--unit' => 'unit_a', '--output' => $path])->assertSuccessful();

        return $path;
    }

    public function test_snapshot_covers_all_reports_for_periods_with_journals(): void
    {
        $this->postJournal(1000000);

        $snapshot = app(ReportSnapshotService::class)->build();

        $this->assertCount(1, $snapshot['reports']);

        $report = reset($snapshot['reports']);
        $this->assertEqualsCanonicalizing(
            ['trial_balance', 'income_statement', 'balance_sheet', 'equity_statement', 'cash_flow', 'general_ledger'],
            array_keys($report)
        );
        $this->assertEquals(1000000, $report['income_statement']['net_profit']);
        $this->assertEquals(1000000, $snapshot['data']['journal_debit']);
    }

    public function test_empty_periods_are_skipped(): void
    {
        $this->assertSame([], app(ReportSnapshotService::class)->build()['reports']);
    }

    public function test_requested_period_must_exist(): void
    {
        $this->artisan('finlog:report-snapshot', [
            '--unit' => 'unit_a',
            '--output' => "{$this->directory}/x.json",
            '--period' => ['1999-01-01'],
        ])->assertFailed();

        $this->assertFileDoesNotExist("{$this->directory}/x.json");
    }

    public function test_snapshot_writes_nothing_to_the_database(): void
    {
        $this->postJournal(1000000);
        $tables = ['accounts', 'fiscal_periods', 'journal_entries', 'journal_entry_lines', 'audit_trails'];
        $before = array_map(fn ($table) => DB::table($table)->count(), $tables);

        app(ReportSnapshotService::class)->build();

        $this->assertSame($before, array_map(fn ($table) => DB::table($table)->count(), $tables));
    }

    public function test_identical_data_compares_as_identical(): void
    {
        $this->postJournal(1000000);

        $this->artisan('finlog:report-compare', [
            'before' => $this->snapshotToFile('a'),
            'after' => $this->snapshotToFile('b'),
        ])->assertSuccessful()->expectsOutputToContain('IDENTIK');
    }

    public function test_changed_amount_fails_the_comparison(): void
    {
        $entry = $this->postJournal(1000000);
        $before = $this->snapshotToFile('before');

        $entry->lines()->where('debit', '>', 0)->update(['debit' => 1000000.01]);
        $entry->lines()->where('credit', '>', 0)->update(['credit' => 1000000.01]);

        $this->artisan('finlog:report-compare', [
            'before' => $before,
            'after' => $this->snapshotToFile('after'),
        ])->assertFailed()->expectsOutputToContain('BERBEDA');
    }

    public function test_deleted_journal_fails_the_comparison(): void
    {
        $this->postJournal(500000);
        $extra = $this->postJournal(250000);
        $before = $this->snapshotToFile('before');

        $extra->delete();

        $this->artisan('finlog:report-compare', [
            'before' => $before,
            'after' => $this->snapshotToFile('after'),
        ])->assertFailed();
    }

    public function test_missing_period_fails_the_comparison(): void
    {
        $service = app(ReportSnapshotService::class);
        $before = ['format_version' => 1, 'data' => [], 'reports' => ['2026-10-01' => ['x' => 1]]];
        $after = ['format_version' => 1, 'data' => [], 'reports' => []];

        $this->assertNotSame([], $service->compare($before, $after));
    }

    public function test_metadata_is_ignored_and_small_rounding_is_tolerated(): void
    {
        $service = app(ReportSnapshotService::class);
        $before = ['format_version' => 1, 'meta' => ['database' => 'a'], 'data' => ['total' => '100.00'], 'reports' => []];
        $after = ['format_version' => 1, 'meta' => ['database' => 'b'], 'data' => ['total' => 100.001], 'reports' => []];

        $this->assertSame([], $service->compare($before, $after));
    }

    private function cashFlowSnapshot(array $rows): array
    {
        return ['format_version' => 1, 'data' => [], 'reports' => ['2026-10-01' => ['cash_flow' => ['operating' => $rows]]]];
    }

    public function test_cash_flow_row_order_and_text_are_ignored(): void
    {
        $before = $this->cashFlowSnapshot([
            ['account_code' => '4170', 'amount' => 100, 'description' => 'a, b'],
            ['account_code' => '4180', 'amount' => 200, 'description' => 'c'],
        ]);
        $after = $this->cashFlowSnapshot([
            ['account_code' => '4180', 'amount' => 200, 'description' => 'c'],
            ['account_code' => '4170', 'amount' => 100, 'description' => 'b, a'],
        ]);

        $this->assertSame([], app(ReportSnapshotService::class)->compare($before, $after));
    }

    public function test_cash_flow_amount_change_is_still_detected_when_rows_are_reordered(): void
    {
        $before = $this->cashFlowSnapshot([
            ['account_code' => '4170', 'amount' => 100, 'description' => 'a'],
            ['account_code' => '4180', 'amount' => 200, 'description' => 'c'],
        ]);
        $after = $this->cashFlowSnapshot([
            ['account_code' => '4180', 'amount' => 200, 'description' => 'c'],
            ['account_code' => '4170', 'amount' => 101, 'description' => 'a'],
        ]);

        $this->assertNotSame([], app(ReportSnapshotService::class)->compare($before, $after));
    }

    public function test_output_inside_the_repo_is_refused_outside_storage(): void
    {
        $this->artisan('finlog:report-snapshot', ['--unit' => 'unit_a', '--output' => base_path('snapshot-test.json')])->assertFailed();

        $this->assertFileDoesNotExist(base_path('snapshot-test.json'));
    }

    public function test_existing_file_is_not_overwritten_without_force(): void
    {
        $path = $this->snapshotToFile('a');

        $this->artisan('finlog:report-snapshot', ['--unit' => 'unit_a', '--output' => $path])->assertFailed();
        $this->artisan('finlog:report-snapshot', ['--unit' => 'unit_a', '--output' => $path, '--force' => true])->assertSuccessful();
    }

    public function test_snapshot_file_is_private(): void
    {
        $path = $this->snapshotToFile('a');

        $this->assertSame('600', substr(sprintf('%o', fileperms($path)), -3));
    }
}
