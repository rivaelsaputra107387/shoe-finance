<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BankMutationController;
use App\Http\Controllers\JournalEntryController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

// Redirect Root to Login or Application
Route::get('/', function () {
    return redirect('/login');
});

// Guest Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Authenticated Application Routes
Route::middleware(['auth'])->prefix('app')->group(function () {

    // Transaksi
    Route::get('/journal-entries', [JournalEntryController::class, 'index'])->name('journal-entries.index');
    Route::get('/journal-entries/create', [JournalEntryController::class, 'create'])->name('journal-entries.create');
    Route::post('/journal-entries', [JournalEntryController::class, 'store'])->name('journal-entries.store');
    Route::post('/journal-entries/{journalEntry}/submit', [JournalEntryController::class, 'submit'])->name('journal-entries.submit');
    Route::post('/journal-entries/{journalEntry}/approve', [JournalEntryController::class, 'approve'])->name('journal-entries.approve');

    Route::get('/bank-mutations', [BankMutationController::class, 'index'])->name('bank-mutations.index');
    Route::post('/bank-mutations/import', [BankMutationController::class, 'import'])->name('bank-mutations.import');
    Route::post('/bank-mutations/{bankMutation}/generate-draft', [BankMutationController::class, 'generateDraft'])->name('bank-mutations.generate-draft');

    Route::get('/draft-journals', [JournalEntryController::class, 'index'])->name('draft-journals.index');

    // Laporan Keuangan
    Route::get('/general-ledger', [ReportController::class, 'generalLedger'])->name('reports.general-ledger');
    Route::get('/trial-balance', [ReportController::class, 'trialBalance'])->name('reports.trial-balance');
    Route::get('/income-statement', [ReportController::class, 'incomeStatement'])->name('reports.income-statement');
    Route::get('/balance-sheet', [ReportController::class, 'balanceSheet'])->name('reports.balance-sheet');
    Route::get('/equity-statement', [ReportController::class, 'equityStatement'])->name('reports.equity-statement');
    Route::get('/cash-flow-statement', [ReportController::class, 'cashFlow'])->name('reports.cash-flow');

    // Master Data
    Route::get('/accounts', [MasterController::class, 'accounts'])->name('master.accounts');
    Route::post('/accounts', [MasterController::class, 'storeAccount'])->name('master.accounts.store');
    Route::get('/fiscal-periods', [MasterController::class, 'fiscalPeriods'])->name('master.fiscal-periods');
    Route::post('/fiscal-periods', [MasterController::class, 'storeFiscalPeriod'])->name('master.fiscal-periods.store');
});
