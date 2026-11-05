<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\MultiUnitTestCase;

class RoleAccessTest extends MultiUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->context()->activate($this->unitA);
    }

    private function actAs(string $role): static
    {
        return $this->actingInUnit($this->userWithRole($role, [$this->unitA]), $this->unitA);
    }

    private function resolveUri(string $uri): string
    {
        return str_replace(
            ['{account}', '{period}'],
            [Account::whereNotNull('parent_id')->value('id'), FiscalPeriod::value('id')],
            $uri
        );
    }

    public static function ownerFinanceOnlyRoutes(): array
    {
        return [
            ['get', '/app/general-ledger'],
            ['get', '/app/general-ledger/export'],
            ['get', '/app/trial-balance'],
            ['get', '/app/trial-balance/export'],
            ['get', '/app/income-statement'],
            ['get', '/app/income-statement/export'],
            ['get', '/app/balance-sheet'],
            ['get', '/app/balance-sheet/export'],
            ['get', '/app/equity-statement'],
            ['get', '/app/equity-statement/export'],
            ['get', '/app/cash-flow-statement'],
            ['get', '/app/cash-flow-statement/export'],
            ['post', '/app/accounts'],
            ['put', '/app/accounts/{account}'],
            ['delete', '/app/accounts/{account}'],
            ['post', '/app/accounts/bulk-delete'],
            ['get', '/app/fiscal-periods'],
            ['post', '/app/fiscal-periods'],
            ['put', '/app/fiscal-periods/{period}'],
            ['delete', '/app/fiscal-periods/{period}'],
            ['post', '/app/fiscal-periods/{period}/reopen'],
            ['post', '/app/journal-entries/bulk-approve'],
            ['post', '/app/journal-entries/bulk-reject'],
            ['get', '/app/period-closing'],
            ['get', '/app/opening-balances'],
            ['get', '/app/users'],
            ['get', '/app/audit-trail'],
        ];
    }

    #[DataProvider('ownerFinanceOnlyRoutes')]
    public function test_staff_is_forbidden(string $method, string $uri): void
    {
        $response = $this->actAs('staff')->{$method}($this->resolveUri($uri));

        $response->assertForbidden();
    }

    #[DataProvider('ownerFinanceOnlyRoutes')]
    public function test_owner_and_finance_are_not_forbidden(string $method, string $uri): void
    {
        foreach (['owner', 'finance'] as $role) {
            $response = $this->actAs($role)->{$method}($this->resolveUri($uri));

            $this->assertNotSame(403, $response->getStatusCode(), "{$role} ditolak di {$uri}");
        }
    }

    public function test_old_admin_panel_is_gone(): void
    {
        $this->actAs('owner')->get('/admin')->assertNotFound();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/app/income-statement')->assertRedirect('/login');
    }

    public static function staffPages(): array
    {
        return [
            ['/app/dashboard'],
            ['/app/bank-mutations'],
            ['/app/draft-journals'],
            ['/app/journal-entries'],
            ['/app/journal-entries/create'],
            ['/app/transaction-archive'],
            ['/app/accounts'],
            ['/app/profile'],
        ];
    }

    #[DataProvider('staffPages')]
    public function test_staff_can_still_open_their_pages(string $uri): void
    {
        $this->actAs('staff')->get($uri)->assertOk();
    }

    private function draftJournal(User $creator): JournalEntry
    {
        return JournalEntry::create([
            'entry_date' => FiscalPeriod::value('start_date'),
            'description' => 'Jurnal uji',
            'fiscal_period_id' => FiscalPeriod::value('id'),
            'created_by' => $creator->id,
            'status' => 'draft',
        ]);
    }

    public function test_staff_cannot_bulk_approve_journals(): void
    {
        $staff = $this->userWithRole('staff', [$this->unitA]);
        $entry = $this->draftJournal($staff);

        $this->actingInUnit($staff, $this->unitA)
            ->post('/app/journal-entries/bulk-approve', ['ids' => [$entry->id]])
            ->assertForbidden();

        $this->assertSame('draft', $entry->fresh()->status);
    }

    public function test_finance_can_bulk_approve_journals(): void
    {
        $finance = $this->userWithRole('finance', [$this->unitA]);
        $entry = $this->draftJournal($finance);

        $this->actingInUnit($finance, $this->unitA)->post('/app/journal-entries/bulk-approve', ['ids' => [$entry->id]]);

        $this->assertSame('posted', $entry->fresh()->status);
    }

    public function test_staff_can_still_submit_and_delete_drafts(): void
    {
        $staff = $this->userWithRole('staff', [$this->unitA]);
        $submitted = $this->draftJournal($staff);
        $deleted = $this->draftJournal($staff);

        $this->actingInUnit($staff, $this->unitA)->post('/app/journal-entries/bulk-submit', ['ids' => [$submitted->id]]);
        $this->actingInUnit($staff, $this->unitA)->post('/app/journal-entries/bulk-delete', ['ids' => [$deleted->id]]);

        $this->assertSame('unapproved', $submitted->fresh()->status);
        $this->assertSoftDeleted($deleted);
    }

    public function test_staff_can_still_view_accounts_but_not_change_them(): void
    {
        $staff = $this->userWithRole('staff', [$this->unitA]);
        $account = Account::whereNotNull('parent_id')->first();

        $this->actingInUnit($staff, $this->unitA)->get('/app/accounts')->assertOk();
        $this->actingInUnit($staff, $this->unitA)->delete("/app/accounts/{$account->id}")->assertForbidden();

        $this->assertNotSoftDeleted($account);
    }

    private function aiPromptFor(string $role): string
    {
        $this->actingAs($this->userWithRole($role, [$this->unitA]));
        $method = new ReflectionMethod(\App\Http\Controllers\AiAssistantController::class, 'getSystemPrompt');

        return $method->invoke(new \App\Http\Controllers\AiAssistantController());
    }

    public function test_ai_prompt_hides_financial_figures_from_staff(): void
    {
        $prompt = $this->aiPromptFor('staff');

        foreach (['### SALDO KAS', '### SALDO PIUTANG', '### SALDO PERSEDIAAN', '### SALDO HUTANG', '### TOP 5 BEBAN', '### LABA BERSIH', '### PERBANDINGAN LABA BERSIH'] as $section) {
            $this->assertStringNotContainsString($section, $prompt);
        }

        $this->assertStringContainsString('### MUTASI BANK', $prompt);
        $this->assertStringContainsString('tidak memiliki akses ke laporan', $prompt);
    }

    public function test_ai_prompt_keeps_financial_figures_for_finance(): void
    {
        $prompt = $this->aiPromptFor('finance');

        $this->assertStringContainsString('### SALDO KAS', $prompt);
        $this->assertStringContainsString('### LABA BERSIH', $prompt);
        $this->assertStringNotContainsString('tidak memiliki akses ke laporan', $prompt);
    }
}
