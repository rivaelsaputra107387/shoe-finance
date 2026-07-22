<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Seed roles and permissions for the SIA system.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ───────────────────────────────────────
        // Define Permissions
        // ───────────────────────────────────────

        $permissions = [
            // COA / Accounts
            'view_account',
            'view_any_account',
            'create_account',
            'update_account',
            'delete_account',

            // Journal Entries
            'view_journal_entry',
            'view_any_journal_entry',
            'create_journal_entry',
            'update_journal_entry',
            'delete_journal_entry',

            // Reports (pages)
            'page_GeneralLedger',
            'page_TrialBalance',
            'page_IncomeStatement',
            'page_BalanceSheet',
            'page_EquityStatement',
            'page_CashFlowStatement',

            // Period Closing
            'page_PeriodClosing',

            // Audit
            'page_AuditTrail',

            // User Management
            'view_user',
            'view_any_user',
            'create_user',
            'update_user',
            'delete_user',

            // Fiscal Period Management
            'view_fiscal_period',
            'view_any_fiscal_period',
            'create_fiscal_period',
            'update_fiscal_period',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // ───────────────────────────────────────
        // Define Roles
        // ───────────────────────────────────────

        // Owner/Admin — full access
        $owner = Role::firstOrCreate(['name' => 'owner']);
        $owner->givePermissionTo(Permission::all());

        // Staff — limited access (input journal, view own journals, view COA read-only)
        $staff = Role::firstOrCreate(['name' => 'staff']);
        $staff->givePermissionTo([
            'view_account',
            'view_any_account',
            'view_journal_entry',
            'create_journal_entry',
        ]);

        // Finance (optional) — same as owner but no user management or period closing
        $finance = Role::firstOrCreate(['name' => 'finance']);
        $finance->givePermissionTo([
            'view_account',
            'view_any_account',
            'create_account',
            'update_account',
            'view_journal_entry',
            'view_any_journal_entry',
            'create_journal_entry',
            'update_journal_entry',
            'page_GeneralLedger',
            'page_TrialBalance',
            'page_IncomeStatement',
            'page_BalanceSheet',
            'page_EquityStatement',
            'page_CashFlowStatement',
            'view_fiscal_period',
            'view_any_fiscal_period',
        ]);
    }
}
