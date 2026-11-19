<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\AuditTrail;
use App\Models\BankMutation;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\PostedJournalCorrectionService;
use Database\Seeders\AccountSeeder;
use Database\Seeders\FiscalPeriodSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PostedJournalCorrectionTest extends TestCase
{
    use RefreshDatabase;

    private const REASON = 'Transfer BCA ke Kas tercatat dua kali';

    private User $finance;

    private FiscalPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed([RolePermissionSeeder::class, FiscalPeriodSeeder::class, AccountSeeder::class]);

        config(['journal.allow_posted_correction' => true]);

        Account::firstOrCreate(['code' => '1180'], [
            'name' => 'Ayat Silang',
            'type' => 'Aset',
            'normal_balance' => 'Debet',
            'report_category' => 'Neraca',
            'parent_id' => $this->id('1100'),
        ]);

        $this->finance = User::factory()->create()->assignRole('finance');
        $this->period = FiscalPeriod::orderBy('start_date')->firstOrFail();
        $this->period->update(['status' => 'open']);
    }

    private function id(string $code): int
    {
        return Account::where('code', $code)->value('id');
    }

    private function line(string $code, float $debit, float $credit, ?string $description = null): array
    {
        return ['account_id' => $this->id($code), 'debit' => $debit, 'credit' => $credit, 'description' => $description];
    }

    private function journal(array $lines, array $attributes = []): JournalEntry
    {
        $journal = JournalEntry::create($attributes + [
            'entry_date' => $this->period->start_date,
            'reference' => 'MUT-' . fake()->unique()->numberBetween(10000, 99999),
            'description' => 'Transfer ke Kas',
            'fiscal_period_id' => $this->period->id,
            'created_by' => $this->finance->id,
            'status' => 'posted',
            'posted_by' => $this->finance->id,
            'posted_at' => now(),
        ]);

        foreach ($lines as $line) {
            $journal->lines()->create($line);
        }

        return $journal;
    }

    private function bankOutJournal(float $amount = 4500000): JournalEntry
    {
        $journal = $this->journal([$this->line('1110', $amount, 0), $this->line('1120', 0, $amount)]);

        BankMutation::create([
            'date' => $this->period->start_date,
            'description' => 'TRSF E-BANKING DB',
            'amount' => $amount,
            'bank_source' => 'BCA',
            'mutation_type' => 'OUT',
            'status' => 'posted',
            'journal_entry_id' => $journal->id,
        ]);

        return $journal;
    }

    private function submit(JournalEntry $journal, array $lines, string $reason = self::REASON, ?User $user = null)
    {
        return $this->actingAs($user ?? $this->finance)
            ->from("/app/journal-entries/{$journal->id}/correction")
            ->put("/app/journal-entries/{$journal->id}/correction", ['reason' => $reason, 'lines' => $lines]);
    }

    private function codes(JournalEntry $journal): array
    {
        return $journal->fresh()->lines()->with('account')->get()
            ->map(fn ($line) => $line->account->code . ':' . (float) $line->debit . ':' . (float) $line->credit)
            ->sort()->values()->all();
    }

    public function test_feature_is_off_by_default(): void
    {
        config(['journal.allow_posted_correction' => false]);
        $journal = $this->bankOutJournal();
        $before = $this->codes($journal);

        $this->actingAs($this->finance)->get("/app/journal-entries/{$journal->id}/correction")
            ->assertRedirect("/app/journal-entries/{$journal->id}");

        $this->submit($journal, [$this->line('1180', 4500000, 0), $this->line('1120', 0, 4500000)])
            ->assertSessionHasErrors('lines');

        $this->assertSame($before, $this->codes($journal));
    }

    public function test_staff_cannot_open_or_submit_a_correction(): void
    {
        $staff = User::factory()->create()->assignRole('staff');
        $journal = $this->bankOutJournal();

        $this->actingAs($staff)->get("/app/journal-entries/{$journal->id}/correction")->assertForbidden();
        $this->submit($journal, [$this->line('1180', 4500000, 0), $this->line('1120', 0, 4500000)], self::REASON, $staff)->assertForbidden();
    }

    public function test_correction_replaces_lines_and_keeps_journal_posted(): void
    {
        $journal = $this->bankOutJournal();

        $this->submit($journal, [$this->line('1180', 4500000, 0), $this->line('1120', 0, 4500000)])
            ->assertRedirect("/app/journal-entries/{$journal->id}")
            ->assertSessionHasNoErrors();

        $this->assertSame(['1120:0:4500000', '1180:4500000:0'], $this->codes($journal));
        $this->assertSame('posted', $journal->fresh()->status);
    }

    public function test_log_keeps_user_reason_and_lines_before_and_after(): void
    {
        $journal = $this->bankOutJournal();

        $this->submit($journal, [$this->line('1180', 4500000, 0, 'uang di jalan'), $this->line('1120', 0, 4500000)]);

        $audit = AuditTrail::where('action', PostedJournalCorrectionService::AUDIT_ACTION)->firstOrFail();

        $this->assertSame($this->finance->id, $audit->user_id);
        $this->assertSame($journal->id, $audit->record_id);
        $this->assertSame(self::REASON, $audit->new_data['reason']);
        $this->assertSame('1110', $audit->old_data['lines'][0]['code']);
        $this->assertEquals(4500000, $audit->old_data['lines'][0]['debit']);
        $this->assertSame('1180', $audit->new_data['lines'][0]['code']);
        $this->assertSame('uang di jalan', $audit->new_data['lines'][0]['description']);
    }

    public function test_every_correction_stays_in_the_history(): void
    {
        $journal = $this->bankOutJournal();

        $this->submit($journal, [$this->line('1180', 4500000, 0), $this->line('1120', 0, 4500000)]);
        $this->submit($journal, [$this->line('1130', 4500000, 0), $this->line('1120', 0, 4500000)], 'Akun lawan ternyata piutang');

        $this->actingAs($this->finance)->get("/app/journal-entries/{$journal->id}")
            ->assertInertia(fn ($page) => $page
                ->where('canCorrect', true)
                ->has('corrections', 2)
                ->where('corrections.0.reason', 'Akun lawan ternyata piutang')
                ->where('corrections.0.before.0.code', '1180')
                ->where('corrections.1.before.0.code', '1110'));
    }

    public function test_staff_does_not_see_correction_history(): void
    {
        $staff = User::factory()->create()->assignRole('staff');
        $journal = $this->bankOutJournal();
        $this->submit($journal, [$this->line('1180', 4500000, 0), $this->line('1120', 0, 4500000)]);

        $this->actingAs($staff)->get("/app/journal-entries/{$journal->id}")
            ->assertInertia(fn ($page) => $page->where('canCorrect', false)->where('corrections', []));
    }

    public function test_audit_page_shows_lines_before_and_after(): void
    {
        $journal = $this->bankOutJournal();
        $this->submit($journal, [$this->line('1180', 4500000, 0), $this->line('1120', 0, 4500000)]);

        $this->actingAs($this->finance)->get('/app/audit-trail?action=correct_posted')
            ->assertInertia(fn ($page) => $page
                ->where('auditTrails.data.0.action_label', 'Koreksi Jurnal Posted')
                ->where('auditTrails.data.0.formatted_diff.0.new_value', self::REASON)
                ->where('auditTrails.data.0.formatted_diff.1.old_value', fn ($value) => str_starts_with($value, '1110 Kas | Debit'))
                ->where('auditTrails.data.0.formatted_diff.1.new_value', fn ($value) => str_starts_with($value, '1180 Ayat Silang | Debit')));
    }

    public function test_reason_is_required(): void
    {
        $journal = $this->bankOutJournal();
        $before = $this->codes($journal);

        $this->submit($journal, [$this->line('1180', 4500000, 0), $this->line('1120', 0, 4500000)], 'salah')
            ->assertSessionHasErrors('reason');

        $this->assertSame($before, $this->codes($journal));
    }

    public function test_unbalanced_lines_are_rejected(): void
    {
        $journal = $this->bankOutJournal();
        $before = $this->codes($journal);

        $this->submit($journal, [$this->line('1180', 4000000, 0), $this->line('1120', 0, 4500000)])
            ->assertSessionHasErrors('lines');

        $this->assertSame($before, $this->codes($journal));
    }

    public function test_line_cannot_hold_debit_and_credit(): void
    {
        $journal = $this->bankOutJournal();

        $this->submit($journal, [$this->line('1180', 4500000, 100), $this->line('1120', 100, 4500000)])
            ->assertSessionHasErrors('lines');
    }

    public function test_unchanged_lines_are_rejected(): void
    {
        $journal = $this->bankOutJournal();

        $this->submit($journal, [$this->line('1110', 4500000, 0), $this->line('1120', 0, 4500000)])
            ->assertSessionHasErrors('lines');

        $this->assertFalse(AuditTrail::where('action', PostedJournalCorrectionService::AUDIT_ACTION)->exists());
    }

    public function test_bank_amount_must_stay_equal_to_the_mutation(): void
    {
        $journal = $this->bankOutJournal();

        $this->submit($journal, [$this->line('1180', 4000000, 0), $this->line('1120', 0, 4000000)])
            ->assertSessionHasErrors('lines');
    }

    public function test_bank_side_must_follow_the_mutation_direction(): void
    {
        $journal = $this->bankOutJournal();

        $this->submit($journal, [$this->line('1120', 4500000, 0), $this->line('1180', 0, 4500000)])
            ->assertSessionHasErrors('lines');
    }

    public function test_money_in_journal_with_reversed_bank_side_can_be_fixed(): void
    {
        $journal = $this->journal([$this->line('6110', 122800, 0), $this->line('1120', 0, 122800)]);
        BankMutation::create([
            'date' => $this->period->start_date,
            'description' => 'kembalian',
            'amount' => 122800,
            'bank_source' => 'BCA',
            'mutation_type' => 'IN',
            'status' => 'posted',
            'journal_entry_id' => $journal->id,
        ]);

        $this->submit($journal, [$this->line('1120', 122800, 0), $this->line('6110', 0, 122800)], 'Kembalian uang masuk tercatat terbalik')
            ->assertSessionHasNoErrors();

        $this->assertSame(['1120:122800:0', '6110:0:122800'], $this->codes($journal));
    }

    public function test_cents_are_preserved(): void
    {
        $journal = $this->bankOutJournal(1250.75);

        $this->submit($journal, [$this->line('1180', 1250.75, 0), $this->line('1120', 0, 1250.75)])
            ->assertSessionHasNoErrors();

        $this->assertSame(['1120:0:1250.75', '1180:1250.75:0'], $this->codes($journal));
    }

    public function test_header_and_suspense_accounts_cannot_be_used(): void
    {
        $journal = $this->bankOutJournal();

        $this->submit($journal, [$this->line('1100', 4500000, 0), $this->line('1120', 0, 4500000)])->assertSessionHasErrors('lines');
        $this->submit($journal, [$this->line('9999', 4500000, 0), $this->line('1120', 0, 4500000)])->assertSessionHasErrors('lines');
    }

    public function test_closed_period_is_blocked(): void
    {
        $journal = $this->bankOutJournal();
        $this->period->update(['status' => 'closed']);

        $this->submit($journal, [$this->line('1180', 4500000, 0), $this->line('1120', 0, 4500000)])
            ->assertSessionHasErrors('lines');
    }

    public function test_opening_closing_and_unposted_journals_are_blocked(): void
    {
        $lines = [$this->line('1110', 1000, 0), $this->line('1120', 0, 1000)];
        $changed = [$this->line('1130', 1000, 0), $this->line('1120', 0, 1000)];

        foreach ([['is_opening' => true], ['is_closing' => true], ['status' => 'draft']] as $attributes) {
            $journal = $this->journal($lines, $attributes);

            $this->submit($journal, $changed)->assertSessionHasErrors('lines');
            $this->assertSame(['1110:1000:0', '1120:0:1000'], $this->codes($journal));
        }
    }

    public function test_dashboard_cache_is_refreshed(): void
    {
        Cache::put('inertia_cash_balance_stats_' . $this->period->id, 'lama', 600);
        Cache::put('inertia_dashboard_charts_' . $this->period->id, 'lama', 600);
        Cache::put('inertia_financial_summary_' . $this->period->id, 'lama', 600);
        $journal = $this->bankOutJournal();

        $this->submit($journal, [$this->line('1180', 4500000, 0), $this->line('1120', 0, 4500000)]);

        $this->assertFalse(Cache::has('inertia_cash_balance_stats_' . $this->period->id));
        $this->assertFalse(Cache::has('inertia_dashboard_charts_' . $this->period->id));
        $this->assertFalse(Cache::has('inertia_financial_summary_' . $this->period->id));
    }
}
