<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\AuditTrail;
use App\Models\BankMutation;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Models\User;
use Database\Seeders\AccountSeeder;
use Database\Seeders\FiscalPeriodSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkAccountCorrectionTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private FiscalPeriod $period;

    private string $csv;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RolePermissionSeeder::class, FiscalPeriodSeeder::class, AccountSeeder::class]);
        config(['journal.allow_posted_correction' => true]);

        Account::firstOrCreate(['code' => '6900'], [
            'name' => 'Beban Operasional Shoepolice',
            'type' => 'Beban',
            'normal_balance' => 'Debet',
            'report_category' => 'Laba Rugi',
            'parent_id' => Account::where('code', '6100')->value('id'),
        ]);

        $this->owner = User::factory()->create(['email' => 'owner@test.local'])->assignRole('owner');
        $this->period = FiscalPeriod::orderBy('start_date')->firstOrFail();
        $this->period->update(['status' => 'open']);
        $this->csv = tempnam(sys_get_temp_dir(), 'koreksi');
    }

    protected function tearDown(): void
    {
        @unlink($this->csv);

        parent::tearDown();
    }

    private function id(string $code): int
    {
        return Account::where('code', $code)->value('id');
    }

    private function journal(string $reference, string $debitCode, float $amount): JournalEntry
    {
        $journal = JournalEntry::create([
            'entry_date' => $this->period->start_date,
            'reference' => $reference,
            'description' => 'biaya studio',
            'fiscal_period_id' => $this->period->id,
            'created_by' => $this->owner->id,
            'status' => 'posted',
        ]);
        $journal->lines()->create(['account_id' => $this->id($debitCode), 'debit' => $amount, 'credit' => 0, 'description' => 'listrik studio']);
        $journal->lines()->create(['account_id' => $this->id('1120'), 'debit' => 0, 'credit' => $amount]);

        BankMutation::create([
            'date' => $this->period->start_date,
            'description' => 'TRSF',
            'amount' => $amount,
            'bank_source' => 'BCA',
            'mutation_type' => 'OUT',
            'status' => 'posted',
            'journal_entry_id' => $journal->id,
        ]);

        return $journal;
    }

    private function writeCsv(array $rows): void
    {
        $lines = ['reference,from,to,amount,reason'];
        foreach ($rows as $row) {
            $lines[] = implode(',', $row);
        }
        file_put_contents($this->csv, implode("\n", $lines)."\n");
    }

    private function codes(JournalEntry $journal): array
    {
        return $journal->fresh()->lines()->with('account')->get()->pluck('account.code')->sort()->values()->all();
    }

    public function test_dry_run_changes_nothing(): void
    {
        $journal = $this->journal('MUT-1', '1130', 505000);
        $this->writeCsv([['MUT-1', '1130', '6900', '505000', 'Tahap 4 biaya Shoepolice']]);

        $this->artisan('finlog:koreksi-massal', ['file' => $this->csv, '--user' => 'owner@test.local'])->assertSuccessful();

        $this->assertSame(['1120', '1130'], $this->codes($journal));
        $this->assertSame(0, AuditTrail::where('action', 'correct_posted')->count());
    }

    public function test_apply_logs_each_journal_under_the_running_user(): void
    {
        $a = $this->journal('MUT-1', '1130', 505000);
        $b = $this->journal('MUT-2', '6170', 224500);
        $this->writeCsv([
            ['MUT-1', '1130', '6900', '505000', 'Tahap 4 biaya Shoepolice'],
            ['MUT-2', '6170', '6900', '224500', 'Tahap 4 biaya Shoepolice'],
        ]);

        $this->artisan('finlog:koreksi-massal', ['file' => $this->csv, '--user' => 'owner@test.local', '--apply' => true])->assertSuccessful();

        $this->assertSame(['1120', '6900'], $this->codes($a));
        $this->assertSame(['1120', '6900'], $this->codes($b));

        $audits = AuditTrail::where('action', 'correct_posted')->get();
        $this->assertCount(2, $audits);
        $this->assertTrue($audits->every(fn ($audit) => $audit->user_id === $this->owner->id));
        $this->assertSame('Tahap 4 biaya Shoepolice', $audits->first()->new_data['reason']);
        $this->assertSame('1130', $audits->firstWhere('record_id', $a->id)->old_data['lines'][0]['code']);
    }

    public function test_rerun_skips_journals_already_corrected(): void
    {
        $journal = $this->journal('MUT-1', '1130', 505000);
        $this->writeCsv([['MUT-1', '1130', '6900', '505000', 'Tahap 4 biaya Shoepolice']]);
        $args = ['file' => $this->csv, '--user' => 'owner@test.local', '--apply' => true];

        $this->artisan('finlog:koreksi-massal', $args)->assertSuccessful();
        $this->artisan('finlog:koreksi-massal', $args)->assertSuccessful();

        $this->assertSame(['1120', '6900'], $this->codes($journal));
        $this->assertSame(1, AuditTrail::where('action', 'correct_posted')->count());
    }

    public function test_one_bad_row_stops_everything(): void
    {
        $good = $this->journal('MUT-1', '1130', 505000);
        $this->journal('MUT-2', '1130', 100000);
        $this->writeCsv([
            ['MUT-1', '1130', '6900', '505000', 'Tahap 4 biaya Shoepolice'],
            ['MUT-2', '1130', '6900', '999999', 'Tahap 4 biaya Shoepolice'],
        ]);

        $this->artisan('finlog:koreksi-massal', ['file' => $this->csv, '--user' => 'owner@test.local', '--apply' => true])->assertFailed();

        $this->assertSame(['1120', '1130'], $this->codes($good));
        $this->assertSame(0, AuditTrail::where('action', 'correct_posted')->count());
    }

    public function test_requires_an_owner_or_finance_user(): void
    {
        User::factory()->create(['email' => 'staff@test.local'])->assignRole('staff');
        $journal = $this->journal('MUT-1', '1130', 505000);
        $this->writeCsv([['MUT-1', '1130', '6900', '505000', 'Tahap 4 biaya Shoepolice']]);

        $this->artisan('finlog:koreksi-massal', ['file' => $this->csv, '--user' => 'staff@test.local', '--apply' => true])->assertFailed();

        $this->assertSame(['1120', '1130'], $this->codes($journal));
    }
}
