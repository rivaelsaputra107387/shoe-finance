<?php

namespace Tests\Feature\MultiUnit;

use App\Models\Account;
use App\Models\Central\CalendarPeriod;
use App\Models\Central\Unit;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;
use App\Services\ClosingEntryService;
use App\Services\Coa\CoaSynchronizer;
use App\Services\Consolidation\ConsolidatedReports;
use App\Services\Consolidation\ConsolidationScope;
use App\Services\Consolidation\IntercompanyReconciler;
use Illuminate\Support\Facades\DB;
use Tests\MultiUnitTestCase;

class ConsolidatedReportsTest extends MultiUnitTestCase
{
    private const START = '2026-07-01';

    private User $author;

    private CalendarPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = User::factory()->create();
        $this->period = CalendarPeriod::create(['name' => 'Juli 2026', 'start_date' => self::START, 'end_date' => '2026-07-31']);

        foreach ([$this->unitA, $this->unitB, $this->unitC] as $unit) {
            app(CoaSynchronizer::class)->sync($unit);
        }
    }

    private function reports(): ConsolidatedReports
    {
        return app(ConsolidatedReports::class);
    }

    /**
     * @param  array<string, array{0: float, 1: float}>  $lines  Kode akun => [debit, kredit]
     */
    private function journal(Unit $unit, array $lines, string $status = 'posted', string $description = 'Uji'): JournalEntry
    {
        return $this->context()->run($unit, function () use ($lines, $status, $description) {
            $period = FiscalPeriod::where('start_date', self::START)->firstOrFail();
            $entry = JournalEntry::create([
                'entry_date' => self::START, 'description' => $description, 'fiscal_period_id' => $period->id,
                'created_by' => $this->author->id, 'status' => $status,
            ]);

            foreach ($lines as $code => [$debit, $credit]) {
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => Account::where('code', $code)->firstOrFail()->id,
                    'debit' => $debit,
                    'credit' => $credit,
                ]);
            }

            return $entry;
        });
    }

    private function row(array $report, string $label): array
    {
        return collect($report['rows'])->firstWhere('label', $label) ?? $this->fail("Baris {$label} tidak ada");
    }

    private function rowByCode(array $report, string $code): array
    {
        return collect($report['rows'])->firstWhere('code', $code) ?? $this->fail("Baris {$code} tidak ada");
    }

    private function closeAll(): void
    {
        foreach ([$this->unitA, $this->unitB, $this->unitC] as $unit) {
            $this->context()->run($unit, fn () => FiscalPeriod::where('start_date', self::START)->update(['status' => 'closed']));
        }
    }

    /**
     * Unit A: modal 100jt, pendapatan luar 10jt, bayar jasa ke B 5jt.
     * Unit B: modal 50jt, menerima 5jt dari A, beban gaji 1jt.
     */
    private function seedMatchedTransactions(): void
    {
        $this->journal($this->unitA, ['1110' => [100000000, 0], '3110' => [0, 100000000]]);
        $this->journal($this->unitA, ['1110' => [10000000, 0], '4110' => [0, 10000000]]);
        $this->journal($this->unitA, ['6820' => [5000000, 0], '1110' => [0, 5000000]]);
        $this->journal($this->unitB, ['1110' => [50000000, 0], '3110' => [0, 50000000]]);
        $this->journal($this->unitB, ['1110' => [5000000, 0], '4210' => [0, 5000000]]);
        $this->journal($this->unitB, ['6110' => [1000000, 0], '1110' => [0, 1000000]]);
    }

    public function test_intercompany_revenue_and_expense_are_eliminated(): void
    {
        $this->seedMatchedTransactions();

        $report = $this->reports()->incomeStatement($this->period);
        $revenue = $this->row($report, 'Total Pendapatan');

        $this->assertEquals(10000000, $revenue['amounts']['unit_a']);
        $this->assertEquals(5000000, $revenue['amounts']['unit_b']);
        $this->assertEquals(-5000000, $revenue['elimination']);
        $this->assertEquals(10000000, $revenue['combined']);

        $expense = $this->row($report, 'Total Beban Operasional');
        $this->assertEquals(-5000000, $expense['elimination']);
        $this->assertEquals(1000000, $expense['combined']);
    }

    public function test_net_profit_equals_the_sum_of_units_when_matched(): void
    {
        $this->seedMatchedTransactions();

        $net = $this->row($this->reports()->incomeStatement($this->period), 'Laba Bersih');

        $this->assertEquals(5000000, $net['amounts']['unit_a']);
        $this->assertEquals(4000000, $net['amounts']['unit_b']);
        $this->assertEquals(0, $net['elimination']);
        $this->assertEquals(9000000, $net['combined']);
    }

    public function test_net_profit_stays_the_sum_of_units_when_mismatched(): void
    {
        $this->journal($this->unitA, ['6820' => [5000000, 0], '1110' => [0, 5000000]]);
        $this->journal($this->unitB, ['1110' => [4000000, 0], '4210' => [0, 4000000]]);

        $report = $this->reports()->incomeStatement($this->period);

        // Laba A -5jt, B +4jt.
        $this->assertEquals(-1000000, $this->row($report, 'Laba Bersih')['combined']);
        $this->assertEquals(1000000 * -1, $this->row($report, 'Selisih Antar Unit (belum cocok)')['combined']);
        $this->assertSame('SEMENTARA', $report['status']['label']);
    }

    public function test_income_statement_rows_add_up_to_net_profit(): void
    {
        $this->seedMatchedTransactions();
        $this->journal($this->unitA, ['5110' => [2000000, 0], '1110' => [0, 2000000]]);

        $report = $this->reports()->incomeStatement($this->period);

        foreach (['unit_a', 'unit_b', 'unit_c'] as $code) {
            $computed = $this->row($report, 'Laba Operasi')['amounts'][$code]
                + $this->row($report, 'Total Pendapatan Lain-lain')['amounts'][$code]
                - $this->row($report, 'Total Beban Lain-lain')['amounts'][$code]
                - $this->row($report, 'Total Beban Administrasi & Pajak')['amounts'][$code];

            $this->assertEquals($this->row($report, 'Laba Bersih')['amounts'][$code], $computed);
        }
    }

    public function test_balance_sheet_columns_balance_and_eliminate_receivable_and_payable(): void
    {
        $this->journal($this->unitA, ['1110' => [100000000, 0], '3110' => [0, 100000000]]);
        $this->journal($this->unitB, ['1110' => [50000000, 0], '3110' => [0, 50000000]]);
        // A menjual jasa ke B secara kredit.
        $this->journal($this->unitA, ['1133' => [3000000, 0], '4110' => [0, 3000000]]);
        $this->journal($this->unitB, ['6110' => [3000000, 0], '2170' => [0, 3000000]]);

        $report = $this->reports()->balanceSheet($this->period);

        $this->assertTrue($report['checks']['is_balanced'], json_encode($report['checks']));

        $assets = $this->row($report, 'Total Aset');
        $this->assertEquals(-3000000, $assets['elimination']);
        $this->assertEquals(150000000, $assets['combined']);

        $liabilities = $this->row($report, 'Total Kewajiban');
        $this->assertEquals(-3000000, $liabilities['elimination']);
        $this->assertEquals(0, $liabilities['combined']);
    }

    public function test_balance_sheet_stays_balanced_with_a_visible_difference_when_mismatched(): void
    {
        $this->journal($this->unitA, ['1133' => [3000000, 0], '4110' => [0, 3000000]]);
        $this->journal($this->unitB, ['6110' => [2000000, 0], '2170' => [0, 2000000]]);

        $report = $this->reports()->balanceSheet($this->period);

        $this->assertTrue($report['checks']['is_balanced'], json_encode($report['checks']));
        $this->assertEquals(-1000000, $this->row($report, 'Selisih Antar Unit (belum cocok)')['elimination']);
        $this->assertSame('SEMENTARA', $report['status']['label']);
    }

    public function test_equity_is_summed_with_capital_visible_per_unit(): void
    {
        $this->seedMatchedTransactions();

        $report = $this->reports()->balanceSheet($this->period);
        $capital = $this->rowByCode($report, '3110');

        $this->assertEquals(100000000, $capital['amounts']['unit_a']);
        $this->assertEquals(50000000, $capital['amounts']['unit_b']);
        $this->assertEquals(150000000, $capital['combined']);
        $this->assertEquals(0, $capital['elimination']);
    }

    public function test_equity_statement_matches_balance_sheet(): void
    {
        $this->seedMatchedTransactions();
        $this->journal($this->unitA, ['3120' => [1000000, 0], '1110' => [0, 1000000]]);

        $report = $this->reports()->equityStatement($this->period);

        $this->assertTrue($report['checks']['matches_balance_sheet'], json_encode($report['checks']));
        $this->assertEquals(158000000, $this->row($report, 'Modal Akhir Periode')['combined']);
    }

    public function test_equity_statement_matches_balance_sheet_when_mismatched(): void
    {
        $this->journal($this->unitA, ['1110' => [100000, 0], '3110' => [0, 100000]]);
        $this->journal($this->unitA, ['1133' => [3000, 0], '4110' => [0, 3000]]);
        $this->journal($this->unitB, ['6110' => [2000, 0], '2170' => [0, 2000]]);

        $this->assertTrue($this->reports()->equityStatement($this->period)['checks']['matches_balance_sheet']);
    }

    public function test_only_posted_journals_count(): void
    {
        $this->journal($this->unitA, ['1110' => [10000000, 0], '4110' => [0, 10000000]], 'unapproved');

        $report = $this->reports()->incomeStatement($this->period);

        $this->assertEquals(0, $this->row($report, 'Laba Bersih')['combined']);
        $this->assertSame(1, collect($report['status']['units'])->firstWhere('code', 'unit_a')['unposted']);
    }

    public function test_label_is_final_only_when_all_closed_and_matched(): void
    {
        $this->seedMatchedTransactions();

        $this->assertSame('SEMENTARA', $this->reports()->incomeStatement($this->period)['status']['label']);

        $this->closeAll();

        $report = $this->reports()->incomeStatement($this->period);
        $this->assertSame('FINAL', $report['status']['label'], json_encode($report['status']['reasons']));
    }

    public function test_label_stays_provisional_when_closed_but_mismatched(): void
    {
        $this->journal($this->unitA, ['6820' => [5000000, 0], '1110' => [0, 5000000]]);
        $this->closeAll();

        $this->assertSame('SEMENTARA', $this->reports()->incomeStatement($this->period)['status']['label']);
    }

    public function test_label_stays_provisional_with_unposted_journals(): void
    {
        $this->journal($this->unitA, ['1110' => [100, 0], '4110' => [0, 100]], 'draft');
        $this->closeAll();

        $this->assertSame('SEMENTARA', $this->reports()->incomeStatement($this->period)['status']['label']);
    }

    public function test_unit_in_preparation_is_not_counted(): void
    {
        $this->journal($this->unitA, ['1110' => [1000, 0], '4110' => [0, 1000]]);
        $this->journal($this->unitC, ['1110' => [5000, 0], '4110' => [0, 5000]]);
        $this->unitC->update(['status' => Unit::STATUS_PREPARATION]);

        $report = $this->reports()->incomeStatement($this->period);

        $this->assertArrayNotHasKey('unit_c', $this->row($report, 'Laba Bersih')['amounts']);
        $this->assertEquals(1000, $this->row($report, 'Laba Bersih')['combined']);
        $this->assertSame('unit_c', $report['status']['excluded'][0]['code']);
    }

    public function test_unit_starting_later_is_not_counted_and_does_not_block_final(): void
    {
        $this->unitC->update(['books_start_date' => '2026-10-01']);
        $this->closeAll();

        $report = $this->reports()->incomeStatement($this->period);

        $this->assertNotContains('unit_c', collect($report['units'])->pluck('code')->all());
        $this->assertSame('FINAL', $report['status']['label'], json_encode($report['status']['reasons']));
    }

    public function test_active_unit_missing_the_period_blocks_final(): void
    {
        $this->closeAll();
        $this->context()->run($this->unitC, fn () => FiscalPeriod::where('start_date', self::START)->delete());

        $this->assertSame('SEMENTARA', $this->reports()->incomeStatement($this->period)['status']['label']);
    }

    public function test_different_sub_accounts_land_under_the_same_header(): void
    {
        $this->context()->run($this->unitB, fn () => Account::create([
            'code' => '6197', 'name' => 'Beban Khusus B', 'type' => 'Beban', 'normal_balance' => 'Debet',
            'report_category' => 'Laba Rugi', 'is_active' => true, 'parent_id' => Account::where('code', '6100')->value('id'),
        ]));

        $this->journal($this->unitA, ['6110' => [100, 0], '1110' => [0, 100]]);
        $this->journal($this->unitB, ['6197' => [200, 0], '1110' => [0, 200]]);

        $row = $this->rowByCode($this->reports()->incomeStatement($this->period), '6100');

        $this->assertEquals(100, $row['amounts']['unit_a']);
        $this->assertEquals(200, $row['amounts']['unit_b']);
        $this->assertEquals(['6110', '6197'], collect($row['details'])->pluck('code')->all());
    }

    public function test_period_ids_may_differ_between_units(): void
    {
        $this->context()->run($this->unitB, fn () => FiscalPeriod::where('start_date', self::START)->delete());
        $this->context()->run($this->unitB, function () {
            FiscalPeriod::create(['name' => 'Isi', 'start_date' => '2026-01-01', 'end_date' => '2026-01-31', 'status' => 'open']);
            FiscalPeriod::create(['name' => 'Juli 2026', 'start_date' => self::START, 'end_date' => '2026-07-31', 'status' => 'open']);
        });

        $this->journal($this->unitB, ['1110' => [700, 0], '4110' => [0, 700]]);

        $this->assertEquals(700, $this->row($this->reports()->incomeStatement($this->period), 'Laba Bersih')['amounts']['unit_b']);
    }

    public function test_reconciler_pairs_both_directions(): void
    {
        $this->journal($this->unitA, ['1133' => [3000, 0], '4110' => [0, 3000]]);
        $this->journal($this->unitB, ['6110' => [3000, 0], '2170' => [0, 3000]]);
        $this->journal($this->unitB, ['1110' => [500, 0], '4210' => [0, 500]]);
        $this->journal($this->unitA, ['6820' => [400, 0], '1110' => [0, 400]]);

        $pairs = app(IntercompanyReconciler::class)->reconcile(ConsolidationScope::for($this->period));

        $receivable = collect($pairs)->firstWhere(fn ($p) => $p['unit_a'] === 'unit_a' && $p['type'] === 'balance_sheet');
        $this->assertTrue($receivable['matched']);

        $revenue = collect($pairs)->firstWhere(fn ($p) => $p['unit_a'] === 'unit_b' && $p['type'] === 'income_statement');
        $this->assertFalse($revenue['matched']);
        $this->assertEquals(500, $revenue['amount_a']);
        $this->assertEquals(400, $revenue['amount_b']);
        $this->assertEquals(100, $revenue['difference']);
    }

    public function test_one_sided_expense_shows_up_as_a_difference(): void
    {
        $this->journal($this->unitA, ['6820' => [400, 0], '1110' => [0, 400]]);

        $pairs = app(IntercompanyReconciler::class)->reconcile(ConsolidationScope::for($this->period));

        $this->assertCount(1, $pairs);
        $this->assertFalse($pairs[0]['matched']);
    }

    public function test_reconciliation_shows_journals_behind_a_difference_including_unposted(): void
    {
        $this->journal($this->unitB, ['1110' => [500, 0], '4210' => [0, 500]], 'posted', 'Tagihan jasa');
        $this->journal($this->unitA, ['6820' => [500, 0], '1110' => [0, 500]], 'unapproved', 'Bayar jasa');

        $pair = collect($this->reports()->reconciliation($this->period)['pairs'])->first();

        $this->assertFalse($pair['matched']);
        $this->assertSame('Tagihan jasa', $pair['lines_a'][0]['description']);
        $this->assertSame('Bayar jasa', $pair['lines_b'][0]['description']);
        $this->assertSame('unapproved', $pair['lines_b'][0]['status']);
    }

    public function test_reports_write_nothing(): void
    {
        $this->seedMatchedTransactions();

        $counts = fn () => collect([$this->unitA, $this->unitB, $this->unitC])->map(fn ($unit) => $this->context()->run($unit, fn () => [
            DB::table('journal_entries')->count(), DB::table('journal_entry_lines')->count(), DB::table('accounts')->count(), DB::table('audit_trails')->count(),
        ]))->all();

        $before = $counts();

        $this->reports()->incomeStatement($this->period);
        $this->reports()->balanceSheet($this->period);
        $this->reports()->equityStatement($this->period);
        $this->reports()->reconciliation($this->period);

        $this->assertSame($before, $counts());
    }

    public function test_context_is_untouched_after_building_a_report(): void
    {
        $this->context()->activate($this->unitB);

        $this->reports()->incomeStatement($this->period);

        $this->assertSame('unit_b', $this->context()->current()->code);
    }

    public function test_finance_with_all_units_can_open_reports(): void
    {
        $finance = $this->userWithRole('finance', [$this->unitA, $this->unitB, $this->unitC]);

        foreach (['income-statement', 'balance-sheet', 'equity-statement', 'reconciliation'] as $page) {
            $this->actingAs($finance)->get("/app/consolidated/{$page}")->assertOk();
        }
    }

    public function test_owner_can_open_reports(): void
    {
        $this->actingAs($this->userWithRole('owner'))->get('/app/consolidated/balance-sheet')->assertOk();
    }

    public function test_finance_missing_a_unit_is_forbidden(): void
    {
        $finance = $this->userWithRole('finance', [$this->unitA, $this->unitB]);

        $this->actingAs($finance)->get('/app/consolidated/income-statement')->assertForbidden();
        $this->actingAs($finance)->get('/app/consolidated/reconciliation')->assertForbidden();
        $this->actingAs($finance)->get('/app/consolidated/income-statement/export?format=pdf')->assertForbidden();
    }

    public function test_staff_is_forbidden_even_with_all_units(): void
    {
        $staff = $this->userWithRole('staff', [$this->unitA, $this->unitB, $this->unitC]);

        $this->actingAs($staff)->get('/app/consolidated/balance-sheet')->assertForbidden();
    }

    public function test_guest_is_redirected(): void
    {
        $this->get('/app/consolidated/balance-sheet')->assertRedirect('/login');
    }

    public function test_unknown_report_is_not_found(): void
    {
        $this->actingAs($this->userWithRole('owner'))->get('/app/consolidated/rahasia')->assertNotFound();
    }

    public function test_pdf_and_excel_exports_download(): void
    {
        $this->seedMatchedTransactions();
        $owner = $this->userWithRole('owner');

        $pdf = $this->actingAs($owner)->get('/app/consolidated/income-statement/export?format=pdf&period='.self::START);
        $pdf->assertOk();
        $this->assertStringContainsString('Laba_Rugi_Gabungan_Juli_2026.pdf', $pdf->headers->get('content-disposition'));

        $excel = $this->actingAs($owner)->get('/app/consolidated/balance-sheet/export?format=excel&period='.self::START);
        $excel->assertOk();
        $this->assertStringContainsString('Neraca_Gabungan_Juli_2026.xlsx', $excel->headers->get('content-disposition'));
    }

    public function test_export_prints_the_status_label(): void
    {
        $report = $this->reports()->incomeStatement($this->period);

        $html = view('reports.consolidated.report', [
            'report' => $report, 'title' => 'X', 'subtitle' => 'Y', 'heading' => 'GABUNGAN: UNIT',
        ])->render();

        $this->assertStringContainsString('SEMENTARA', $html);
        $this->assertStringContainsString('GABUNGAN: UNIT', $html);
        // Class "row" dipakai layout PDF dan membuat kolom bergeser.
        $this->assertStringNotContainsString('class="row"', $html);
    }

    private function closeWithService(Unit $unit, string $start = self::START): void
    {
        $this->context()->run($unit, fn () => app(ClosingEntryService::class)->closePeriod(FiscalPeriod::where('start_date', $start)->value('id')));
    }

    public function test_reports_stay_correct_after_real_closing_in_every_unit(): void
    {
        $this->actingAs($this->author);
        $this->seedMatchedTransactions();
        $this->journal($this->unitA, ['3120' => [1000000, 0], '1110' => [0, 1000000]]);

        $netBefore = $this->row($this->reports()->incomeStatement($this->period), 'Laba Bersih')['combined'];
        $assetsBefore = $this->row($this->reports()->balanceSheet($this->period), 'Total Aset')['combined'];

        foreach ([$this->unitA, $this->unitB, $this->unitC] as $unit) {
            $this->closeWithService($unit);
        }

        $income = $this->reports()->incomeStatement($this->period);
        $balance = $this->reports()->balanceSheet($this->period);
        $equity = $this->reports()->equityStatement($this->period);

        $this->assertEquals($netBefore, $this->row($income, 'Laba Bersih')['combined']);
        $this->assertEquals(-5000000, $this->row($income, 'Total Pendapatan')['elimination']);
        $this->assertTrue($balance['checks']['is_balanced'], json_encode($balance['checks']));
        $this->assertEquals($assetsBefore, $this->row($balance, 'Total Aset')['combined']);

        // Laba 9jt dikurangi prive 1jt pindah ke Laba Ditahan, dan Modal Disetor tetap 150jt.
        $this->assertEquals(8000000, $this->rowByCode($balance, '3300')['combined']);
        $this->assertEquals(150000000, $this->rowByCode($balance, '3110')['combined']);
        $this->assertTrue($equity['checks']['matches_balance_sheet'], json_encode($equity['checks']));
        $this->assertSame('FINAL', $income['status']['label'], json_encode($income['status']['reasons']));
    }

    public function test_second_period_only_counts_its_own_results_but_carries_balances(): void
    {
        $this->actingAs($this->author);
        $this->seedMatchedTransactions();
        $this->journal($this->unitA, ['1133' => [2000000, 0], '4110' => [0, 2000000]]);
        $this->journal($this->unitB, ['6110' => [2000000, 0], '2170' => [0, 2000000]]);

        foreach ([$this->unitA, $this->unitB, $this->unitC] as $unit) {
            $this->closeWithService($unit);
        }

        $august = CalendarPeriod::where('start_date', '2026-08-01')->firstOrFail();

        $this->context()->run($this->unitA, function () {
            $period = FiscalPeriod::where('start_date', '2026-08-01')->firstOrFail();
            $entry = JournalEntry::create(['entry_date' => '2026-08-05', 'description' => 'x', 'fiscal_period_id' => $period->id, 'created_by' => $this->author->id, 'status' => 'posted']);
            $entry->lines()->create(['account_id' => Account::where('code', '1110')->value('id'), 'debit' => 700, 'credit' => 0]);
            $entry->lines()->create(['account_id' => Account::where('code', '4110')->value('id'), 'debit' => 0, 'credit' => 700]);
        });

        $income = $this->reports()->incomeStatement($august);
        $balance = $this->reports()->balanceSheet($august);
        $equity = $this->reports()->equityStatement($august);

        $this->assertEquals(700, $this->row($income, 'Laba Bersih')['combined']);
        $this->assertEquals(0, $this->row($income, 'Total Beban Operasional')['combined']);

        // Piutang dan utang antar unit dari Juli masih tersimpan, dan tetap dieliminasi.
        $this->assertEquals(-2000000, $this->row($balance, 'Total Kewajiban')['elimination']);
        $this->assertTrue($balance['checks']['is_balanced'], json_encode($balance['checks']));
        $this->assertEquals(159000000, $this->row($equity, 'Modal Awal Periode')['combined']);
        $this->assertTrue($equity['checks']['matches_balance_sheet'], json_encode($equity['checks']));
    }

    public function test_page_without_any_calendar_period_still_opens(): void
    {
        CalendarPeriod::query()->delete();

        $this->actingAs($this->userWithRole('owner'))->get('/app/consolidated/balance-sheet')->assertOk();
    }
}
