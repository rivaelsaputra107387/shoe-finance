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
use Carbon\Carbon;
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
        $day            = $request->input('day');
        $startDate      = $request->input('start_date');
        $endDate        = $request->input('end_date');

        if ($fiscalPeriodId && $day) {
            $period = $periods->firstWhere('id', (int) $fiscalPeriodId) ?? FiscalPeriod::find($fiscalPeriodId);
            if ($period) {
                $yearMonth = Carbon::parse($period->start_date)->format('Y-m');
                $specificDate = sprintf('%s-%02d', $yearMonth, (int) $day);
                $startDate = $specificDate;
                $endDate = $specificDate;
            }
        }

        $ledgerData = null;
        if ($accountId && $fiscalPeriodId) {
            $service = new LedgerService();
            $ledgerData = $service->generateForAccount(
                (int) $accountId,
                (int) $fiscalPeriodId,
                $startDate ?: null,
                $endDate ?: null
            );
        }

        return Inertia::render('Reports/GeneralLedger', [
            'accounts'          => $accounts,
            'periods'           => $periods,
            'selectedAccountId' => (int) $accountId,
            'selectedPeriodId'  => (int) $fiscalPeriodId,
            'selectedDay'       => $day ? (int) $day : null,
            'selectedStartDate' => $startDate,
            'selectedEndDate'   => $endDate,
            'ledgerData'        => $ledgerData,
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

    private function generateFilename(string $reportTitle, string $periodName, string $ext, ?string $extra = null): string
    {
        $cleanTitle  = str_replace([' ', '/', '\\', ':'], '_', trim($reportTitle));
        $cleanPeriod = str_replace([' ', '/', '\\', ':'], '_', trim($periodName));
        $cleanExtra  = $extra ? '_' . str_replace([' ', '/', '\\', ':'], '_', trim($extra)) : '';

        return "{$cleanTitle}{$cleanExtra}_{$cleanPeriod}.{$ext}";
    }

    public function exportGeneralLedger(Request $request)
    {
        $accountId      = $request->input('account_id');
        $fiscalPeriodId = $request->input('fiscal_period_id');
        $day            = $request->input('day');
        $startDate      = $request->input('start_date');
        $endDate        = $request->input('end_date');
        $format         = $request->input('format', 'pdf');

        if (! $accountId || ! $fiscalPeriodId) {
            return back()->with('error', 'Parameter tidak lengkap untuk export.');
        }

        $period = FiscalPeriod::find($fiscalPeriodId);
        $periodName = $period?->name ?? 'Semua Periode';

        if ($period && $day) {
            $yearMonth = Carbon::parse($period->start_date)->format('Y-m');
            $specificDate = sprintf('%s-%02d', $yearMonth, (int) $day);
            $startDate = $specificDate;
            $endDate = $specificDate;
            $periodName .= " (Tanggal {$day})";
        } elseif ($startDate && $endDate) {
            $formattedStart = Carbon::parse($startDate)->format('d/m/Y');
            $formattedEnd = Carbon::parse($endDate)->format('d/m/Y');
            if ($startDate === $endDate) {
                $periodName .= " (Tanggal {$formattedStart})";
            } else {
                $periodName .= " ({$formattedStart} - {$formattedEnd})";
            }
        } elseif ($startDate) {
            $periodName .= " (Mulai " . Carbon::parse($startDate)->format('d/m/Y') . ")";
        } elseif ($endDate) {
            $periodName .= " (Sampai " . Carbon::parse($endDate)->format('d/m/Y') . ")";
        }

        $service = new LedgerService();
        $ledgerData = $service->generateForAccount(
            (int) $accountId,
            (int) $fiscalPeriodId,
            $startDate ?: null,
            $endDate ?: null
        );
        $ledgerData['period_name'] = $periodName;

        $account = Account::find($accountId);
        $accountInfo = $account ? ($account->code . '_' . $account->name) : '';
        $filename = $this->generateFilename('Laporan_Buku_Besar', $periodName, $format === 'excel' ? 'xlsx' : 'pdf', $accountInfo);

        if ($format === 'excel') {
            return Excel::download(new GeneralLedgerExport($ledgerData, $periodName), $filename);
        }

        $pdf = Pdf::loadView('reports.general-ledger-pdf', ['data' => $ledgerData, 'title' => 'LAPORAN BUKU BESAR'])->setPaper('a4', 'landscape');
        return $pdf->download($filename);
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

        $filename = $this->generateFilename('Laporan_Neraca_Lajur', $periodName, $format === 'excel' ? 'xlsx' : 'pdf');

        if ($format === 'excel') {
            return Excel::download(new TrialBalanceExport($reportData, $periodName), $filename);
        }

        $pdf = Pdf::loadView('reports.trial-balance-pdf', ['data' => $reportData, 'title' => 'NERACA LAJUR'])->setPaper('a4', 'portrait');
        return $pdf->download($filename);
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

        $filename = $this->generateFilename('Laporan_Laba_Rugi', $periodName, $format === 'excel' ? 'xlsx' : 'pdf');

        if ($format === 'excel') {
            return Excel::download(new IncomeStatementExport($reportData, $periodName), $filename);
        }

        $pdf = Pdf::loadView('reports.income-statement-pdf', ['data' => $reportData, 'title' => 'LAPORAN LABA RUGI'])->setPaper('a4', 'portrait');
        return $pdf->download($filename);
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

        $filename = $this->generateFilename('Laporan_Neraca', $periodName, $format === 'excel' ? 'xlsx' : 'pdf');

        if ($format === 'excel') {
            return Excel::download(new BalanceSheetExport($reportData, $periodName), $filename);
        }

        $pdf = Pdf::loadView('reports.balance-sheet-pdf', ['data' => $reportData, 'title' => 'LAPORAN NERACA'])->setPaper('a4', 'landscape');
        return $pdf->download($filename);
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

        $filename = $this->generateFilename('Laporan_Perubahan_Ekuitas', $periodName, $format === 'excel' ? 'xlsx' : 'pdf');

        if ($format === 'excel') {
            return Excel::download(new EquityStatementExport($reportData, $periodName), $filename);
        }

        $pdf = Pdf::loadView('reports.equity-statement-pdf', ['data' => $reportData, 'title' => 'LAPORAN PERUBAHAN EKUITAS'])->setPaper('a4', 'portrait');
        return $pdf->download($filename);
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

        $filename = $this->generateFilename('Laporan_Arus_Kas', $periodName, $format === 'excel' ? 'xlsx' : 'pdf');

        if ($format === 'excel') {
            return Excel::download(new CashFlowExport($reportData, $periodName), $filename);
        }

        $pdf = Pdf::loadView('reports.cash-flow-statement-pdf', ['data' => $reportData, 'title' => 'LAPORAN ARUS KAS'])->setPaper('a4', 'portrait');
        return $pdf->download($filename);
    }
}
