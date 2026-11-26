<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\BankMutation;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Models\User;
use Database\Seeders\AccountSeeder;
use Database\Seeders\FiscalPeriodSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionArchiveTest extends TestCase
{
    use RefreshDatabase;

    private User $finance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed([RolePermissionSeeder::class, FiscalPeriodSeeder::class, AccountSeeder::class]);

        $this->finance = User::factory()->create()->assignRole('finance');

        $this->archived('MUT-03061', 'perjalanan dinas cikini', 'posted', '6110');
        $this->archived('MUT-02535', 'TRSF E-BANKING DB YAPINDO TRANSPORTA', 'posted', '1110');
    }

    private function archived(string $reference, string $description, string $status = 'posted', ?string $accountCode = null): BankMutation
    {
        $journal = JournalEntry::create([
            'entry_date' => '2026-10-31',
            'reference' => $reference,
            'description' => $description,
            'fiscal_period_id' => FiscalPeriod::value('id'),
            'created_by' => $this->finance->id,
            'status' => 'posted',
        ]);

        if ($accountCode) {
            $account = Account::where('code', $accountCode)->first();
            if ($account) {
                $journal->lines()->create([
                    'account_id' => $account->id,
                    'debit' => 1000,
                    'credit' => 0,
                ]);
            }
        }

        return BankMutation::create([
            'date' => '2026-10-31',
            'description' => $description,
            'amount' => 1000,
            'bank_source' => 'BCA',
            'mutation_type' => 'OUT',
            'status' => $status,
            'journal_entry_id' => $journal->id,
        ]);
    }

    private function assertSearchFinds(string $search, array $descriptions): void
    {
        $this->actingAs($this->finance)
            ->get('/app/transaction-archive?search=' . urlencode($search))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where(
                'mutations.data',
                fn ($rows) => collect($rows)->pluck('description')->sort()->values()->all() === $descriptions
            ));
    }

    public function test_finds_a_transaction_by_journal_number(): void
    {
        $this->assertSearchFinds('MUT-03061', ['perjalanan dinas cikini']);
    }

    public function test_finds_a_transaction_by_description(): void
    {
        $this->assertSearchFinds('yapindo', ['TRSF E-BANKING DB YAPINDO TRANSPORTA']);
    }

    public function test_finds_a_transaction_by_account_code(): void
    {
        $this->assertSearchFinds('6110', ['perjalanan dinas cikini']);
    }

    public function test_finds_a_transaction_by_account_name(): void
    {
        $this->assertSearchFinds('Gaji Karyawan', ['perjalanan dinas cikini']);
    }

    public function test_search_ignores_surrounding_spaces(): void
    {
        $this->assertSearchFinds('  MUT-02535 ', ['TRSF E-BANKING DB YAPINDO TRANSPORTA']);
    }

    public function test_unknown_search_finds_nothing(): void
    {
        $this->assertSearchFinds('MUT-99999', []);
    }

    public function test_journal_number_search_keeps_unposted_mutations_out(): void
    {
        $this->archived('MUT-04000', 'belum diposting', 'drafted');

        $this->assertSearchFinds('MUT-04000', []);
    }
}
