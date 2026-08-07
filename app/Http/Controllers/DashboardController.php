<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\BankMutation;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Services\AccountBalanceService;
use App\Services\BalanceSheetService;
use App\Services\CashFlowReportService;
use App\Services\EquityStatementService;
use App\Services\IncomeStatementService;
use App\Services\TrialBalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $isStaff = $user->hasRole('staff');
        $isOwnerOrFinance = $user->hasAnyRole(['owner', 'finance']);

        // Ambil periode dari parameter atau gunakan periode aktif
        $periodId = $request->get('period_id');
        if ($periodId) {
            $period = FiscalPeriod::find($periodId);
        } else {
            $period = FiscalPeriod::active();
        }

        // Daftar semua periode untuk filter dropdown
        $allPeriods = FiscalPeriod::orderByDesc('start_date')->get()->map(function ($p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'status' => $p->status,
            ];
        });

        // 1. Active Period Stats
        $journalQuery = $period ? JournalEntry::forPeriod($period->id)->posted() : JournalEntry::query()->whereRaw('1=0');
        if ($isStaff) {
            $journalQuery->where('created_by', $user->id);
        }
        $journalCount = $journalQuery->count();

        $activePeriodStats = [
            'period_name'   => $period?->name ?? 'Tidak Ada Periode Aktif',
            'start_date'    => $period?->start_date?->format('d M Y') ?? '-',
            'end_date'      => $period?->end_date?->format('d M Y') ?? '-',
            'journal_count' => $journalCount,
            'is_open'       => $period?->status === 'open',
        ];

        // 2. Cash & Balance Stats (Owner & Finance)
        $cashBalanceStats = null;
        if ($isOwnerOrFinance && $period) {
            $cashBalanceStats = Cache::remember('inertia_cash_balance_stats_' . $period->id, 600, function () use ($period) {
                // FIX: Gunakan kumulatif (getCumulativeTotalsUpTo) bukan period-only.
                // Saldo Kas/Bank/Piutang/Hutang adalah saldo akumulasi, bukan mutasi satu periode.
                $balanceSvc   = new AccountBalanceService();
                $bulkTotals   = $balanceSvc->getCumulativeTotalsUpTo($period->id);

                // Kas & Bank (111x & 112x) — normal balance Debet
                $kasBankAccounts = Account::active()
                    ->where(function ($q) {
                        $q->where('code', 'like', '111%')->orWhere('code', 'like', '112%');
                    })
                    ->whereNotNull('parent_id')
                    ->get();
                $totalKasBank = $kasBankAccounts->sum(
                    fn ($acc) => $balanceSvc->getBalance($bulkTotals, $acc->id, 'Debet')
                );

                // Piutang Usaha (113x) — normal balance Debet
                $piutangAccounts = Account::active()
                    ->where('code', 'like', '113%')
                    ->whereNotNull('parent_id')
                    ->get();
                $totalPiutang = $piutangAccounts->sum(
                    fn ($acc) => $balanceSvc->getBalance($bulkTotals, $acc->id, 'Debet')
                );

                // Hutang (2xxx) — normal balance Kredit
                $hutangAccounts = Account::active()
                    ->where('code', 'like', '2%')
                    ->whereNotNull('parent_id')
                    ->get();
                $totalHutang = $hutangAccounts->sum(
                    fn ($acc) => $balanceSvc->getBalance($bulkTotals, $acc->id, 'Kredit')
                );

                return [
                    'total_kas_bank' => round($totalKasBank, 2),
                    'total_piutang'  => round($totalPiutang, 2),
                    'total_hutang'   => round($totalHutang, 2),
                ];
            });
        }

        // 3. Analytics Charts Data (Owner & Finance)
        $charts = null;
        if ($isOwnerOrFinance) {
            $cacheKey = 'inertia_dashboard_charts_' . ($period ? $period->id : 'null');
            $charts = Cache::remember($cacheKey, 600, function () use ($period) {
                // Last 6 fiscal periods up to selected period
                $query = FiscalPeriod::orderBy('start_date', 'desc')->limit(6);
                if ($period) {
                    $query->where('start_date', '<=', $period->start_date);
                }
                $periods = $query->get()->reverse();

                $labels = [];
                $revenueData = [];
                $expenseData = [];
                $netProfitData = [];

                foreach ($periods as $p) {
                    $labels[] = $p->name;

                    // Revenue (4xxx, Credit - Debit)
                    $revenue = JournalEntryLine::whereHas('account', fn ($q) => $q->where('code', 'like', '4%')->whereNotNull('parent_id'))
                        ->whereHas('journalEntry', fn ($q) => $q->where('fiscal_period_id', $p->id)->where('status', 'posted')->where('is_closing', false)->whereNull('deleted_at'))
                        ->selectRaw('COALESCE(SUM(credit) - SUM(debit), 0) as balance')
                        ->value('balance');
                    $revVal = abs((float) $revenue);
                    $revenueData[] = round($revVal, 2);

                    // Expenses (5xxx, 6xxx, 72xx, 8xxx, Debit - Credit)
                    // FIX: Tambah whereNotNull('parent_id') agar konsisten dengan IncomeStatementService.
                    // Sebelumnya menggunakan whereNotNull('account_id') yang tidak memfilter akun induk.
                    $expenses = JournalEntryLine::whereHas('account', function ($q) {
                        $q->where(function ($inner) {
                            $inner->where('code', 'like', '5%')
                                  ->orWhere('code', 'like', '6%')
                                  ->orWhere('code', 'like', '72%')
                                  ->orWhere('code', 'like', '8%');
                        })->whereNotNull('parent_id');
                    })
                    ->whereHas('journalEntry', fn ($q) => $q->where('fiscal_period_id', $p->id)->where('status', 'posted')->where('is_closing', false)->whereNull('deleted_at'))
                    ->selectRaw('COALESCE(SUM(debit) - SUM(credit), 0) as balance')
                    ->value('balance');
                    $expVal = abs((float) $expenses);
                    $expenseData[] = round($expVal, 2);

                    $netProfitData[] = round($revVal - $expVal, 2);
                }

                // Donut: Expense Composition for Active Period
                $expenseDonut = ['labels' => [], 'data' => []];
                $revenuePie   = ['labels' => [], 'data' => []];

                if ($period) {
                    $expComposition = JournalEntryLine::join('accounts', 'journal_entry_lines.account_id', '=', 'accounts.id')
                        ->where(function ($q) {
                            $q->where('accounts.code', 'like', '5%')
                              ->orWhere('accounts.code', 'like', '6%')
                              ->orWhere('accounts.code', 'like', '72%')
                              ->orWhere('accounts.code', 'like', '8%');
                        })
                        ->whereHas('journalEntry', fn ($q) => $q->where('fiscal_period_id', $period->id)->where('status', 'posted')->where('is_closing', false)->whereNull('deleted_at'))
                        ->select('accounts.name', DB::raw('SUM(journal_entry_lines.debit) - SUM(journal_entry_lines.credit) as balance'))
                        ->groupBy('accounts.id', 'accounts.name')
                        ->havingRaw('SUM(journal_entry_lines.debit) - SUM(journal_entry_lines.credit) > 0')
                        ->orderByRaw('balance DESC')
                        ->get();

                    $expenseDonut = [
                        'labels' => $expComposition->pluck('name')->toArray(),
                        'data'   => $expComposition->pluck('balance')->map(fn ($v) => round((float)$v, 2))->toArray(),
                    ];

                    $revComposition = JournalEntryLine::join('accounts', 'journal_entry_lines.account_id', '=', 'accounts.id')
                        ->where('accounts.code', 'like', '4%')
                        ->whereHas('journalEntry', fn ($q) => $q->where('fiscal_period_id', $period->id)->where('status', 'posted')->where('is_closing', false)->whereNull('deleted_at'))
                        ->select('accounts.name', DB::raw('SUM(journal_entry_lines.credit) - SUM(journal_entry_lines.debit) as balance'))
                        ->groupBy('accounts.id', 'accounts.name')
                        ->havingRaw('SUM(journal_entry_lines.credit) - SUM(journal_entry_lines.debit) > 0')
                        ->orderByRaw('balance DESC')
                        ->get();

                    $revenuePie = [
                        'labels' => $revComposition->pluck('name')->toArray(),
                        'data'   => $revComposition->pluck('balance')->map(fn ($v) => round((float)$v, 2))->toArray(),
                    ];
                }

                return [
                    'labels'          => $labels,
                    'revenue'         => $revenueData,
                    'expense'         => $expenseData,
                    'net_profit'      => $netProfitData,
                    'expense_donut'   => $expenseDonut,
                    'revenue_pie'     => $revenuePie,
                ];
            });
        }

        // 3b. Financial Report Summary (Owner & Finance only)
        $financialSummary = null;
        if ($isOwnerOrFinance && $period) {
            $cacheKey = 'inertia_financial_summary_' . $period->id;
            $financialSummary = Cache::remember($cacheKey, 600, function () use ($period) {
                try {
                    // Laba Rugi
                    $is = (new IncomeStatementService())->generate($period->id);
                    $incomeStatement = [
                        'total_revenue'  => $is['total_revenue'],
                        'total_expense'  => $is['total_hpp'] + $is['total_operational_expenses'] + $is['total_other_expenses'] + $is['total_admin_expenses'],
                        'gross_profit'   => $is['gross_profit'],
                        'net_profit'     => $is['net_profit'],
                    ];

                    // Neraca
                    $bs = (new BalanceSheetService())->generate($period->id);
                    $balanceSheet = [
                        'total_assets'       => $bs['total_assets'],
                        'total_liabilities'  => $bs['total_liabilities'],
                        'total_equity'       => $bs['total_equity'],
                        'is_balanced'        => abs($bs['total_assets'] - ($bs['total_liabilities'] + $bs['total_equity'])) < 1,
                    ];

                    // Arus Kas
                    $cf = (new CashFlowReportService())->generate($period->id);
                    $cashFlow = [
                        'total_operating'  => $cf['total_operating'],
                        'total_investing'  => $cf['total_investing'],
                        'total_financing'  => $cf['total_financing'],
                        'net_increase'     => $cf['net_increase'],
                        'ending_cash'      => $cf['ending_cash'],
                    ];

                    // Perubahan Ekuitas
                    $eq = (new EquityStatementService())->generate($period->id);
                    $equity = [
                        'beginning_capital'  => $eq['beginning_capital'],
                        'net_profit'         => $eq['net_profit'],
                        'prive'              => $eq['prive'],
                        'additional_capital' => $eq['additional_capital'],
                        'ending_capital'     => $eq['ending_capital'],
                    ];

                    // Neraca Lajur
                    $tb = (new TrialBalanceService())->generate($period->id);
                    $trialBalance = [
                        'total_debit'  => $tb['total_debit'],
                        'total_credit' => $tb['total_credit'],
                        'is_balanced'  => $tb['is_balanced'],
                    ];

                    return compact('incomeStatement', 'balanceSheet', 'cashFlow', 'equity', 'trialBalance');
                } catch (\Throwable $e) {
                    return null;
                }
            });
        }

        // 4. Recent Journals (Posted)
        $recentJournals = JournalEntry::posted()
            ->with(['lines'])
            ->latest('entry_date')
            ->limit(5)
            ->get()
            ->map(function ($je) {
                return [
                    'id'          => $je->id,
                    'entry_date'  => $je->entry_date->format('d M Y'),
                    'reference'   => $je->reference,
                    'description' => $je->description,
                    'total_debit' => (float) $je->lines->sum('debit'),
                    'is_closing'  => (bool) $je->is_closing,
                ];
            });

        // 5. Staff Widgets Data
        $staffWidgets = null;
        if ($isStaff) {
            $pendingMutationsCount = BankMutation::whereIn('status', ['pending', 'matched'])->count();
            $draftJournalsCount    = JournalEntry::where('status', 'draft')->where('created_by', $user->id)->count();
            $unapprovedCount       = JournalEntry::where('status', 'unapproved')->where('created_by', $user->id)->count();

            $staffDraftJournals = JournalEntry::where('status', 'draft')
                ->where('created_by', $user->id)
                ->with('lines')
                ->latest('entry_date')
                ->limit(5)
                ->get()
                ->map(fn ($je) => [
                    'id'          => $je->id,
                    'entry_date'  => $je->entry_date->format('d M Y'),
                    'reference'   => $je->reference,
                    'description' => $je->description,
                    'total_debit' => (float) $je->lines->sum('debit'),
                ]);

            $staffWidgets = [
                'pending_mutations'   => $pendingMutationsCount,
                'draft_journals'      => $draftJournalsCount,
                'unapproved_journals' => $unapprovedCount,
                'drafts_list'         => $staffDraftJournals,
            ];
        }

        return Inertia::render('Dashboard', [
            'activePeriod'      => $activePeriodStats,
            'cashBalance'       => $cashBalanceStats,
            'charts'            => $charts,
            'financialSummary'  => $financialSummary,
            'recentJournals'    => $recentJournals,
            'staffWidgets'      => $staffWidgets,
            'userRole'          => $user->roles->first()?->name ?? 'staff',
            'periods'           => $allPeriods,
            'selectedPeriodId'  => $period ? $period->id : null,
        ]);
    }
}
