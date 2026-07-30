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
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\GeneralLedgerExport;
use App\Exports\TrialBalanceExport;
use App\Exports\IncomeStatementExport;
use App\Exports\BalanceSheetExport;
use App\Exports\EquityStatementExport;
use App\Exports\CashFlowExport;

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

    public function exportGeneralLedger(Request $request)
    {
        $accountId = $request->input('account_id');
        $fiscalPeriodId = $request->input('fiscal_period_id');
        $format = $request->input('format', 'pdf');

        if (!$accountId || !$fiscalPeriodId) {
            return back()->with('error', 'Parameter tidak lengkap untuk export.');
        }

        $service = new LedgerService();
        $ledgerData = $service->generateForAccount($accountId, $fiscalPeriodId);
        $periodName = FiscalPeriod::find($fiscalPeriodId)?->name ?? 'Semua Periode';
        $ledgerData['period_name'] = $periodName;

        if ($format === 'excel') {
            return Excel::download(new GeneralLedgerExport($ledgerData, $periodName), 'Buku_Besar_' . time() . '.xlsx');
        }

        $pdf = Pdf::loadView('reports.general-ledger-pdf', ['data' => $ledgerData])->setPaper('a4', 'landscape');
        return $pdf->download('Buku_Besar_' . time() . '.pdf');
    }

    public function exportTrialBalance(Request $request)
    {
        $fiscalPeriodId = $request->input('fiscal_period_id');
        $format = $request->input('format', 'pdf');

        if (!$fiscalPeriodId) return back()->with('error', 'Pilih periode terlebih dahulu.');

        $service = new TrialBalanceService();
        $reportData = $service->generate($fiscalPeriodId);
        $periodName = FiscalPeriod::find($fiscalPeriodId)?->name ?? 'Semua Periode';
        $reportData['period_name'] = $periodName;

        if ($format === 'excel') {
            return Excel::download(new TrialBalanceExport($reportData, $periodName), 'Neraca_Saldo_' . time() . '.xlsx');
        }

        $pdf = Pdf::loadView('reports.trial-balance-pdf', ['data' => $reportData])->setPaper('a4', 'portrait');
        return $pdf->download('Neraca_Saldo_' . time() . '.pdf');
    }

    public function exportIncomeStatement(Request $request)
    {
        $fiscalPeriodId = $request->input('fiscal_period_id');
        $format = $request->input('format', 'pdf');

        if (!$fiscalPeriodId) return back()->with('error', 'Pilih periode terlebih dahulu.');

        $service = new IncomeStatementService();
        $reportData = $service->generate($fiscalPeriodId);
        $periodName = FiscalPeriod::find($fiscalPeriodId)?->name ?? 'Semua Periode';
        $reportData['period_name'] = $periodName;

        if ($format === 'excel') {
            return Excel::download(new IncomeStatementExport($reportData, $periodName), 'Laba_Rugi_' . time() . '.xlsx');
        }

        $pdf = Pdf::loadView('reports.income-statement-pdf', ['data' => $reportData])->setPaper('a4', 'portrait');
        return $pdf->download('Laba_Rugi_' . time() . '.pdf');
    }

    public function exportBalanceSheet(Request $request)
    {
        $fiscalPeriodId = $request->input('fiscal_period_id');
        $format = $request->input('format', 'pdf');

        if (!$fiscalPeriodId) return back()->with('error', 'Pilih periode terlebih dahulu.');

        $service = new BalanceSheetService();
        $reportData = $service->generate($fiscalPeriodId);
        $periodName = FiscalPeriod::find($fiscalPeriodId)?->name ?? 'Semua Periode';
        $reportData['period_name'] = $periodName;

        if ($format === 'excel') {
            return Excel::download(new BalanceSheetExport($reportData, $periodName), 'Neraca_' . time() . '.xlsx');
        }

        $pdf = Pdf::loadView('reports.balance-sheet-pdf', ['data' => $reportData])->setPaper('a4', 'landscape');
        return $pdf->download('Neraca_' . time() . '.pdf');
    }

    public function exportEquityStatement(Request $request)
    {
        $fiscalPeriodId = $request->input('fiscal_period_id');
        $format = $request->input('format', 'pdf');

        if (!$fiscalPeriodId) return back()->with('error', 'Pilih periode terlebih dahulu.');

        $service = new EquityStatementService();
        $reportData = $service->generate($fiscalPeriodId);
        $periodName = FiscalPeriod::find($fiscalPeriodId)?->name ?? 'Semua Periode';
        $reportData['period_name'] = $periodName;

        if ($format === 'excel') {
            return Excel::download(new EquityStatementExport($reportData, $periodName), 'Perubahan_Modal_' . time() . '.xlsx');
        }

        $pdf = Pdf::loadView('reports.equity-statement-pdf', ['data' => $reportData])->setPaper('a4', 'portrait');
        return $pdf->download('Perubahan_Modal_' . time() . '.pdf');
    }

    public function exportCashFlow(Request $request)
    {
        $fiscalPeriodId = $request->input('fiscal_period_id');
        $format = $request->input('format', 'pdf');

        if (!$fiscalPeriodId) return back()->with('error', 'Pilih periode terlebih dahulu.');

        $service = new CashFlowReportService();
        $reportData = $service->generate($fiscalPeriodId);
        $periodName = FiscalPeriod::find($fiscalPeriodId)?->name ?? 'Semua Periode';
        $reportData['period_name'] = $periodName;

        if ($format === 'excel') {
            return Excel::download(new CashFlowExport($reportData, $periodName), 'Arus_Kas_' . time() . '.xlsx');
        }

        $pdf = Pdf::loadView('reports.cash-flow-statement-pdf', ['data' => $reportData])->setPaper('a4', 'portrait');
        return $pdf->download('Arus_Kas_' . time() . '.pdf');
    }
}
