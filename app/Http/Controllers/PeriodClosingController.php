<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Services\ClosingEntryService;
use App\Services\IncomeStatementService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PeriodClosingController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();
        if (!$user->hasRole('owner')) {
            abort(403, 'Akses ditolak: Hanya Owner yang dapat mengakses Penutupan Periode.');
        }

        $activePeriod = FiscalPeriod::active();
        $periodData = null;

        if ($activePeriod) {
            $journalCount = JournalEntry::forPeriod($activePeriod->id)->posted()->count();
            $unpostedCount = JournalEntry::where('fiscal_period_id', $activePeriod->id)
                ->whereIn('status', ['draft', 'unapproved'])
                ->count();

            $suspenseAccount = Account::where('code', '9999')->first();
            $suspenseBalance = $suspenseAccount ? $suspenseAccount->getBalanceForPeriod($activePeriod->id) : 0;

            // Estimated Net Profit
            $incomeSvc = new IncomeStatementService();
            $incomeReport = $incomeSvc->generate($activePeriod->id);
            $estimatedNetProfit = $incomeReport['net_profit'] ?? 0;

            $periodData = [
                'id'                   => $activePeriod->id,
                'name'                 => $activePeriod->name,
                'start_date'           => $activePeriod->start_date->format('d M Y'),
                'end_date'             => $activePeriod->end_date->format('d M Y'),
                'journal_count'        => $journalCount,
                'unposted_count'       => $unpostedCount,
                'suspense_balance'     => round((float) $suspenseBalance, 2),
                'estimated_net_profit' => round((float) $estimatedNetProfit, 2),
                'can_close'            => ($unpostedCount === 0 && abs($suspenseBalance) < 0.01),
            ];
        }

        // Closed Periods list
        $closedPeriods = FiscalPeriod::where('status', 'closed')
            ->orderByDesc('end_date')
            ->get();

        return Inertia::render('Settings/PeriodClosing', [
            'activePeriod'  => $periodData,
            'closedPeriods' => $closedPeriods,
        ]);
    }

    public function execute(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasRole('owner')) {
            abort(403, 'Akses ditolak: Hanya Owner yang dapat menutup periode.');
        }

        $request->validate([
            'fiscal_period_id' => 'required|exists:fiscal_periods,id',
        ]);

        try {
            $service = new ClosingEntryService();
            $result = $service->closePeriod((int) $request->fiscal_period_id);

            return back()->with('success', "Periode '{$result['closed_period']->name}' berhasil ditutup! Jurnal penutup otomatis dibuat dan periode baru '{$result['new_period']->name}' telah dibuka.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
