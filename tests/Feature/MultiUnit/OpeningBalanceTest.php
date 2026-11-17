<?php

namespace Tests\Feature\MultiUnit;

use App\Models\Account;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\BalanceSheetService;
use App\Services\EquityStatementService;
use Tests\MultiUnitTestCase;

class OpeningBalanceTest extends MultiUnitTestCase
{
    private User $finance;

    private FiscalPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        $this->finance = $this->userWithRole('finance', [$this->unitA]);
        $this->context()->activate($this->unitA);
        $this->period = FiscalPeriod::orderBy('start_date')->firstOrFail();
        $this->period->update(['status' => 'open']);
    }

    private function id(string $code): int
    {
        return Account::where('code', $code)->value('id');
    }

    private function save(array $amounts)
    {
        return $this->actingInUnit($this->finance, $this->unitA)->post('/app/opening-balances', [
            'fiscal_period_id' => $this->period->id,
            'entry_date' => $this->period->start_date->format('Y-m-d'),
            'balances' => collect($amounts)
                ->map(fn ($amount, $code) => ['account_id' => $this->id((string) $code), 'amount' => $amount])
                ->values()
                ->all(),
        ]);
    }

    private function openingLine(string $code)
    {
        return JournalEntry::where('is_opening', true)->firstOrFail()
            ->lines()->where('account_id', $this->id($code))->firstOrFail();
    }

    public function test_retained_earnings_deficit_is_recorded_as_debit(): void
    {
        $this->save(['1110' => 70_000_000, '3110' => 100_000_000, '3300' => -30_000_000])
            ->assertSessionHasNoErrors();

        $line = $this->openingLine('3300');
        $this->assertEquals(30_000_000, $line->debit);
        $this->assertEquals(0, $line->credit);
    }

    public function test_deficit_reduces_equity_consistently_across_reports(): void
    {
        $this->save(['1110' => 70_000_000, '3110' => 100_000_000, '3300' => -30_000_000]);

        $balanceSheet = app(BalanceSheetService::class)->generate($this->period->id);
        $equity = app(EquityStatementService::class)->generate($this->period->id);

        $this->assertEquals(70_000_000, $balanceSheet['total_assets']);
        $this->assertEquals(70_000_000, $balanceSheet['total_equity']);
        $this->assertEquals(70_000_000, $equity['ending_capital']);
    }

    public function test_page_shows_a_saved_deficit_as_negative(): void
    {
        $this->save(['1110' => 70_000_000, '3110' => 100_000_000, '3300' => -30_000_000]);

        $this->actingInUnit($this->finance, $this->unitA)->get('/app/opening-balances?fiscal_period_id=' . $this->period->id)
            ->assertInertia(fn ($page) => $page->where(
                'accounts',
                fn ($accounts) => collect($accounts)->firstWhere('code', '3300')['amount'] == -30_000_000
                    && collect($accounts)->firstWhere('code', '3300')['allows_deficit'] === true
            ));
    }

    public function test_other_accounts_cannot_be_negative(): void
    {
        $this->save(['1110' => -70_000_000, '3110' => -70_000_000])
            ->assertSessionHasErrors('balances');

        $this->assertFalse(JournalEntry::where('is_opening', true)->exists());
    }

    public function test_positive_retained_earnings_still_go_to_credit(): void
    {
        $this->save(['1110' => 130_000_000, '3110' => 100_000_000, '3300' => 30_000_000])
            ->assertSessionHasNoErrors();

        $line = $this->openingLine('3300');
        $this->assertEquals(0, $line->debit);
        $this->assertEquals(30_000_000, $line->credit);
    }
}
