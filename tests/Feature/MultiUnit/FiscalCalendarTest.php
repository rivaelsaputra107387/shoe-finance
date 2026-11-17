<?php

namespace Tests\Feature\MultiUnit;

use App\Models\Account;
use App\Models\Central\CalendarPeriod;
use App\Models\Central\Unit;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\ClosingEntryService;
use App\Services\FiscalCalendar;
use Illuminate\Support\Facades\Artisan;
use Tests\MultiUnitTestCase;

class FiscalCalendarTest extends MultiUnitTestCase
{
    private User $finance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finance = $this->userWithRole('finance', [$this->unitA, $this->unitB, $this->unitC]);
    }

    private function periodIn(Unit $unit, string $startDate): ?FiscalPeriod
    {
        return $this->context()->run($unit, fn () => FiscalPeriod::where('start_date', $startDate)->first());
    }

    private function addOctober(): void
    {
        $this->actingInUnit($this->finance, $this->unitA)->post('/app/fiscal-periods', [
            'name' => 'Oktober 2026', 'start_date' => '2026-10-01', 'end_date' => '2026-10-31',
        ])->assertSessionHas('success');
    }

    public function test_new_period_reaches_all_units(): void
    {
        $this->addOctober();

        foreach ([$this->unitA, $this->unitB, $this->unitC] as $unit) {
            $this->assertSame('open', $this->periodIn($unit, '2026-10-01')?->status);
        }

        $this->assertTrue(CalendarPeriod::where('start_date', '2026-10-01')->exists());
    }

    public function test_duplicate_start_date_is_rejected(): void
    {
        $this->addOctober();

        $this->actingInUnit($this->finance, $this->unitB)->post('/app/fiscal-periods', [
            'name' => 'Dobel', 'start_date' => '2026-10-01', 'end_date' => '2026-10-31',
        ])->assertSessionHasErrors('start_date');
    }

    public function test_period_dates_cannot_be_changed_per_unit(): void
    {
        $this->addOctober();
        $period = $this->periodIn($this->unitA, '2026-10-01');

        $this->actingInUnit($this->finance, $this->unitA)->put("/app/fiscal-periods/{$period->id}", [
            'name' => 'Ganti', 'start_date' => '2026-10-05', 'end_date' => '2026-10-20', 'status' => 'reopened',
        ]);

        $fresh = $this->periodIn($this->unitA, '2026-10-01');
        $this->assertSame('2026-10-31', $fresh->end_date->toDateString());
        $this->assertSame('Oktober 2026', $fresh->name);
        $this->assertSame('reopened', $fresh->status);
        $this->assertSame('open', $this->periodIn($this->unitB, '2026-10-01')->status);
    }

    public function test_period_with_journals_in_another_unit_cannot_be_deleted(): void
    {
        $this->addOctober();
        $this->context()->run($this->unitB, fn () => JournalEntry::create([
            'entry_date' => '2026-10-01', 'description' => 'x', 'created_by' => $this->finance->id, 'status' => 'draft',
            'fiscal_period_id' => FiscalPeriod::where('start_date', '2026-10-01')->value('id'),
        ]));

        $period = $this->periodIn($this->unitA, '2026-10-01');

        $this->actingInUnit($this->finance, $this->unitA)->delete("/app/fiscal-periods/{$period->id}")->assertSessionHas('error');

        $this->assertNotNull($this->periodIn($this->unitA, '2026-10-01'));
    }

    public function test_empty_period_is_deleted_from_all_units(): void
    {
        $this->addOctober();
        $period = $this->periodIn($this->unitA, '2026-10-01');

        $this->actingInUnit($this->finance, $this->unitA)->delete("/app/fiscal-periods/{$period->id}")->assertSessionHas('success');

        $this->assertNull($this->periodIn($this->unitB, '2026-10-01'));
        $this->assertFalse(CalendarPeriod::where('start_date', '2026-10-01')->exists());
    }

    public function test_sync_is_idempotent_and_respects_books_start_date(): void
    {
        $calendar = app(FiscalCalendar::class);
        CalendarPeriod::create(['name' => 'Juni 2026', 'start_date' => '2026-06-01', 'end_date' => '2026-06-30']);
        CalendarPeriod::create(['name' => 'Oktober 2026', 'start_date' => '2026-10-01', 'end_date' => '2026-10-31']);

        $this->assertSame(1, $calendar->syncUnit($this->unitA));
        $this->assertSame(0, $calendar->syncUnit($this->unitA));
        $this->assertNull($this->periodIn($this->unitA, '2026-06-01'));
    }

    public function test_import_seeds_the_calendar_from_a_unit(): void
    {
        $this->assertSame(0, Artisan::call('finlog:calendar-sync', ['--import-from' => 'unit_a']));

        $this->assertTrue(CalendarPeriod::where('start_date', '2026-07-01')->exists());
        $this->assertNotNull($this->periodIn($this->unitB, '2026-07-01'));
    }

    public function test_inactive_unit_is_skipped_by_sync(): void
    {
        $this->unitC->update(['status' => Unit::STATUS_INACTIVE]);
        $this->addOctober();

        $this->assertNull($this->periodIn($this->unitC, '2026-10-01'));
    }

    public function test_closing_one_unit_leaves_other_units_open_and_shares_next_period(): void
    {
        $this->actingAs($this->finance);

        $period = $this->context()->run($this->unitA, function () {
            $period = FiscalPeriod::orderBy('start_date')->firstOrFail();
            app(ClosingEntryService::class)->closePeriod($period->id);

            return $period->fresh();
        });

        $this->assertSame('closed', $period->status);
        $this->assertSame('open', $this->periodIn($this->unitB, '2026-07-01')->status);

        // Periode berikutnya masuk kalender dan semua unit, dengan status terbuka.
        $this->assertTrue(CalendarPeriod::where('start_date', '2026-08-01')->exists());
        $this->assertSame('open', $this->periodIn($this->unitA, '2026-08-01')->status);
        $this->assertSame('open', $this->periodIn($this->unitB, '2026-08-01')->status);
    }

    public function test_closing_entries_go_to_retained_earnings_only_in_that_unit(): void
    {
        $this->actingAs($this->finance);

        $make = function () {
            $period = FiscalPeriod::orderBy('start_date')->firstOrFail();
            $entry = JournalEntry::create(['entry_date' => $period->start_date, 'description' => 'x', 'fiscal_period_id' => $period->id, 'created_by' => $this->finance->id, 'status' => 'posted']);
            $entry->lines()->create(['account_id' => Account::where('code', 'like', '111%')->whereNotNull('parent_id')->value('id'), 'debit' => 500, 'credit' => 0]);
            $entry->lines()->create(['account_id' => Account::where('code', 'like', '41%')->whereNotNull('parent_id')->value('id'), 'debit' => 0, 'credit' => 500]);

            return $period;
        };

        $retained = fn () => (float) Account::where('code', '3300')->firstOrFail()->journalEntryLines()->sum('credit');

        $this->context()->run($this->unitA, function () use ($make) {
            app(ClosingEntryService::class)->closePeriod($make()->id);
        });
        $this->context()->run($this->unitB, $make);

        $this->assertEquals(500, $this->context()->run($this->unitA, $retained));
        $this->assertEquals(0, $this->context()->run($this->unitB, $retained));
    }
}
