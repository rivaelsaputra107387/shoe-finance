<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\FiscalPeriod;
use App\Services\BalanceSheetService;
use App\Services\CashFlowReportService;
use App\Services\EquityStatementService;
use App\Services\IncomeStatementService;
use App\Services\LedgerService;
use App\Services\TrialBalanceService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function generalLedger(Request $request): Response
    {
        $accounts = Account::active()->whereNotNull('parent_id')->orderBy('code')->get();
        $periods  = FiscalPeriod::orderByDesc('start_date')->get();

        $accountId      = $request->input('account_id', $accounts->first()?->id);
        $fiscalPeriodId = $request->input('fiscal_period_id', FiscalPeriod::active()?->id);

        $ledgerData = null;
        if ($accountId && $fiscalPeriodId) {
            $service = new LedgerService();
            $ledgerData = $service->generateForAccount($accountId, $fiscalPeriodId);
        }

        return Inertia::render('Reports/GeneralLedger', [
            'accounts'         => $accounts,
            'periods'          => $periods,
            'selectedAccountId'=> (int) $accountId,
            'selectedPeriodId' => (int) $fiscalPeriodId,
            'ledgerData'       => $ledgerData,
        ]);
    }

    public function trialBalance(Request $request): Response
    {
        $periods = FiscalPeriod::orderByDesc('start_date')->get();
        $fiscalPeriodId = $request->input('fiscal_period_id', FiscalPeriod::active()?->id);

        $reportData = null;
        if ($fiscalPeriodId) {
            $service = new TrialBalanceService();
            $reportData = $service->generate($fiscalPeriodId);
        }

        return Inertia::render('Reports/TrialBalance', [
            'periods'          => $periods,
            'selectedPeriodId' => (int) $fiscalPeriodId,
            'reportData'       => $reportData,
        ]);
    }

    public function incomeStatement(Request $request): Response
    {
        $periods = FiscalPeriod::orderByDesc('start_date')->get();
        $fiscalPeriodId = $request->input('fiscal_period_id', FiscalPeriod::active()?->id);

        $reportData = null;
        if ($fiscalPeriodId) {
            $service = new IncomeStatementService();
            $reportData = $service->generate($fiscalPeriodId);
        }

        return Inertia::render('Reports/IncomeStatement', [
            'periods'          => $periods,
            'selectedPeriodId' => (int) $fiscalPeriodId,
            'reportData'       => $reportData,
        ]);
    }

    public function balanceSheet(Request $request): Response
    {
        $periods = FiscalPeriod::orderByDesc('start_date')->get();
        $fiscalPeriodId = $request->input('fiscal_period_id', FiscalPeriod::active()?->id);

        $reportData = null;
        if ($fiscalPeriodId) {
            $service = new BalanceSheetService();
            $reportData = $service->generate($fiscalPeriodId);
        }

        return Inertia::render('Reports/BalanceSheet', [
            'periods'          => $periods,
            'selectedPeriodId' => (int) $fiscalPeriodId,
            'reportData'       => $reportData,
        ]);
    }

    public function equityStatement(Request $request): Response
    {
        $periods = FiscalPeriod::orderByDesc('start_date')->get();
        $fiscalPeriodId = $request->input('fiscal_period_id', FiscalPeriod::active()?->id);

        $reportData = null;
        if ($fiscalPeriodId) {
            $service = new EquityStatementService();
            $reportData = $service->generate($fiscalPeriodId);
        }

        return Inertia::render('Reports/EquityStatement', [
            'periods'          => $periods,
            'selectedPeriodId' => (int) $fiscalPeriodId,
            'reportData'       => $reportData,
        ]);
    }

    public function cashFlow(Request $request): Response
    {
        $periods = FiscalPeriod::orderByDesc('start_date')->get();
        $fiscalPeriodId = $request->input('fiscal_period_id', FiscalPeriod::active()?->id);

        $reportData = null;
        if ($fiscalPeriodId) {
            $service = new CashFlowReportService();
            $reportData = $service->generate($fiscalPeriodId);
        }

        return Inertia::render('Reports/CashFlow', [
            'periods'          => $periods,
            'selectedPeriodId' => (int) $fiscalPeriodId,
            'reportData'       => $reportData,
        ]);
    }
}
