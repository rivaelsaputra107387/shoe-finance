<?php

use App\Http\Controllers\AuditTrailController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BankMutationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\JournalEntryController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\PeriodClosingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Redirect Root to Application Dashboard or Login
Route::get('/', function () {
    return auth()->check() ? redirect('/app/dashboard') : redirect('/login');
});

// Guest Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Authenticated Application Routes
Route::middleware(['auth'])->prefix('app')->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profil (All Auth Users)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Transaksi
    Route::get('/journal-entries', [JournalEntryController::class, 'index'])->name('journal-entries.index');
    Route::get('/journal-entries/create', [JournalEntryController::class, 'create'])->name('journal-entries.create');
    Route::get('/journal-entries/{journalEntry}', [JournalEntryController::class, 'show'])->name('journal-entries.show');
    Route::post('/journal-entries', [JournalEntryController::class, 'store'])->name('journal-entries.store');
    Route::get('/journal-entries/{journalEntry}/edit', [JournalEntryController::class, 'edit'])->name('journal-entries.edit');
    Route::put('/journal-entries/{journalEntry}', [JournalEntryController::class, 'update'])->name('journal-entries.update');
    Route::post('/journal-entries/{journalEntry}/submit', [JournalEntryController::class, 'submit'])->name('journal-entries.submit');
    Route::post('/journal-entries/{journalEntry}/approve', [JournalEntryController::class, 'approve'])->name('journal-entries.approve');
    Route::post('/journal-entries/{journalEntry}/reject', [JournalEntryController::class, 'reject'])->name('journal-entries.reject');
    Route::post('/journal-entries/{journalEntry}/update-lines', [JournalEntryController::class, 'updateLines'])->name('journal-entries.update-lines');

    Route::post('/journal-entries/bulk-submit', [JournalEntryController::class, 'bulkSubmit'])->name('journal-entries.bulk-submit');
    Route::post('/journal-entries/bulk-approve', [JournalEntryController::class, 'bulkApprove'])->name('journal-entries.bulk-approve');
    Route::post('/journal-entries/bulk-reject', [JournalEntryController::class, 'bulkReject'])->name('journal-entries.bulk-reject');
    Route::post('/journal-entries/bulk-delete', [JournalEntryController::class, 'bulkDelete'])->name('journal-entries.bulk-delete');

    Route::get('/bank-mutations', [BankMutationController::class, 'index'])->name('bank-mutations.index');
    Route::post('/bank-mutations', [BankMutationController::class, 'store'])->name('bank-mutations.store');
    Route::put('/bank-mutations/{bankMutation}', [BankMutationController::class, 'update'])->name('bank-mutations.update');
    Route::post('/bank-mutations/import', [BankMutationController::class, 'import'])->name('bank-mutations.import');
    Route::post('/bank-mutations/{bankMutation}/generate-draft', [BankMutationController::class, 'generateDraft'])->name('bank-mutations.generate-draft');
    Route::post('/bank-mutations/{bankMutation}/match-api', [BankMutationController::class, 'matchApi'])->name('bank-mutations.match-api');
    Route::post('/bank-mutations/bulk-generate-draft', [BankMutationController::class, 'bulkGenerateDraft'])->name('bank-mutations.bulk-generate-draft');
    Route::post('/bank-mutations/bulk-match-api', [BankMutationController::class, 'bulkMatchApi'])->name('bank-mutations.bulk-match-api');
    Route::post('/bank-mutations/bulk-delete', [BankMutationController::class, 'bulkDelete'])->name('bank-mutations.bulk-delete');

    Route::get('/draft-journals', [JournalEntryController::class, 'draftJournals'])->name('draft-journals.index');

    // Laporan Keuangan
    Route::get('/general-ledger', [ReportController::class, 'generalLedger'])->name('reports.general-ledger');
    Route::get('/general-ledger/export', [ReportController::class, 'exportGeneralLedger'])->name('reports.general-ledger.export');

    Route::get('/trial-balance', [ReportController::class, 'trialBalance'])->name('reports.trial-balance');
    Route::get('/trial-balance/export', [ReportController::class, 'exportTrialBalance'])->name('reports.trial-balance.export');

    Route::get('/income-statement', [ReportController::class, 'incomeStatement'])->name('reports.income-statement');
    Route::get('/income-statement/export', [ReportController::class, 'exportIncomeStatement'])->name('reports.income-statement.export');

    Route::get('/balance-sheet', [ReportController::class, 'balanceSheet'])->name('reports.balance-sheet');
    Route::get('/balance-sheet/export', [ReportController::class, 'exportBalanceSheet'])->name('reports.balance-sheet.export');

    Route::get('/equity-statement', [ReportController::class, 'equityStatement'])->name('reports.equity-statement');
    Route::get('/equity-statement/export', [ReportController::class, 'exportEquityStatement'])->name('reports.equity-statement.export');

    Route::get('/cash-flow-statement', [ReportController::class, 'cashFlow'])->name('reports.cash-flow');
    Route::get('/cash-flow-statement/export', [ReportController::class, 'exportCashFlow'])->name('reports.cash-flow.export');

    // Master Data & Pengaturan
    Route::get('/accounts', [MasterController::class, 'accounts'])->name('master.accounts');
    Route::post('/accounts', [MasterController::class, 'storeAccount'])->name('master.accounts.store');
    Route::put('/accounts/{account}', [MasterController::class, 'updateAccount'])->name('master.accounts.update');
    Route::delete('/accounts/{account}', [MasterController::class, 'deleteAccount'])->name('master.accounts.delete');
    Route::post('/accounts/bulk-delete', [MasterController::class, 'bulkDeleteAccounts'])->name('master.accounts.bulk-delete');

    Route::get('/fiscal-periods', [MasterController::class, 'fiscalPeriods'])->name('master.fiscal-periods');
    Route::post('/fiscal-periods', [MasterController::class, 'storeFiscalPeriod'])->name('master.fiscal-periods.store');
    Route::put('/fiscal-periods/{fiscalPeriod}', [MasterController::class, 'updateFiscalPeriod'])->name('master.fiscal-periods.update');

    // Penutupan Periode (Owner Only - enforced at route level)
    Route::middleware(['role:owner'])->group(function () {
        Route::get('/period-closing', [PeriodClosingController::class, 'index'])->name('period-closing.index');
        Route::post('/period-closing/execute', [PeriodClosingController::class, 'execute'])->name('period-closing.execute');
    });

    Route::get('/audit-trail', [AuditTrailController::class, 'index'])->name('audit-trail.index');

    // Manajemen Akun (Owner Only)
    Route::middleware(['role:owner'])->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });
});
