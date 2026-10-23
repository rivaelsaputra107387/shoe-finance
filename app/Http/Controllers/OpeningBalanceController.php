<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AuditTrail;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OpeningBalanceController extends Controller
{
    /**
     * Display the Opening Balance (Saldo Awal) setup table.
     */
    public function index(Request $request): Response
    {
        $user = auth()->user();
        if (!$user->hasAnyRole(['owner', 'finance'])) {
            abort(403, 'Akses ditolak: Hanya Owner & Finance yang dapat mengakses Saldo Awal.');
        }

        $periods = FiscalPeriod::orderByDesc('start_date')->get();

        // Target period: from request, or earliest active, or latest
        $selectedPeriodId = $request->input('fiscal_period_id');
        if (!$selectedPeriodId) {
            $activePeriod = FiscalPeriod::whereIn('status', ['open', 'reopened'])
                ->orderBy('start_date', 'asc')
                ->first();
            $selectedPeriodId = $activePeriod?->id ?? $periods->first()?->id;
        }

        $selectedPeriod = FiscalPeriod::find($selectedPeriodId);

        // Fetch existing opening balance journal for this period
        $openingJournal = null;
        $existingBalances = [];

        if ($selectedPeriod) {
            $openingJournal = JournalEntry::with('lines')
                ->where('fiscal_period_id', $selectedPeriod->id)
                ->where('is_opening', true)
                ->whereNull('deleted_at')
                ->first();

            if ($openingJournal) {
                foreach ($openingJournal->lines as $line) {
                    $existingBalances[$line->account_id] = [
                        'debit'  => (float) $line->debit,
                        'credit' => (float) $line->credit,
                    ];
                }
            }
        }

        // All Balance Sheet leaf accounts (Aset, Kewajiban, Ekuitas)
        // Excludes suspense (9999), income summary (3200), and prive (3120)
        $accounts = Account::active()
            ->where(function ($q) {
                $q->whereNotNull('parent_id')
                  ->orWhere('code', '3300');
            })
            ->whereIn('type', ['Aset', 'Kewajiban', 'Ekuitas'])
            ->whereNotIn('code', ['9999', '3200', '3120'])
            ->orderBy('code')
            ->get()
            ->map(function (Account $account) use ($existingBalances) {
                $line = $existingBalances[$account->id] ?? null;
                $currentAmount = 0.0;

                if ($line) {
                    $currentAmount = $account->normal_balance === 'Debet'
                        ? ($line['debit'] - $line['credit'])
                        : ($line['credit'] - $line['debit']);
                }

                return [
                    'id'             => $account->id,
                    'code'           => $account->code,
                    'name'           => $account->name,
                    'type'           => $account->type,
                    'normal_balance' => $account->normal_balance,
                    'amount'         => max(0, round($currentAmount, 2)),
                ];
            });

        return Inertia::render('Settings/OpeningBalances', [
            'periods'          => $periods,
            'selectedPeriod'   => $selectedPeriod,
            'accounts'         => $accounts,
            'openingJournal'   => $openingJournal ? [
                'id'         => $openingJournal->id,
                'reference'  => $openingJournal->reference,
                'entry_date' => $openingJournal->entry_date->format('Y-m-d'),
                'status'     => $openingJournal->status,
                'updated_at' => $openingJournal->updated_at?->format('d M Y H:i'),
            ] : null,
            'canEdit'          => $selectedPeriod && $selectedPeriod->status !== 'closed',
        ]);
    }

    /**
     * Store or update opening balance journal entry.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasAnyRole(['owner', 'finance'])) {
            abort(403, 'Akses ditolak: Hanya Owner & Finance yang dapat mengatur Saldo Awal.');
        }

        $request->validate([
            'fiscal_period_id'       => ['required', 'exists:fiscal_periods,id'],
            'entry_date'             => ['required', 'date'],
            'balances'               => ['required', 'array', 'min:1'],
            'balances.*.account_id'  => ['required', 'exists:accounts,id'],
            'balances.*.amount'      => ['required', 'numeric', 'min:0'],
        ]);

        $period = FiscalPeriod::findOrFail($request->fiscal_period_id);

        if ($period->status === 'closed') {
            throw ValidationException::withMessages([
                'fiscal_period_id' => 'Tidak dapat mengubah Saldo Awal pada periode yang sudah ditutup.',
            ]);
        }

        if ($request->entry_date > $period->end_date->format('Y-m-d')) {
            throw ValidationException::withMessages([
                'entry_date' => 'Tanggal cut-off jurnal saldo awal tidak boleh melebihi tanggal akhir periode (' . $period->end_date->format('d/m/Y') . ').',
            ]);
        }

        $accountIds = collect($request->balances)->pluck('account_id')->unique();
        $accountMap = Account::whereIn('id', $accountIds)->get()->keyBy('id');

        $totalDebit  = 0;
        $totalCredit = 0;
        $linesToInsert = [];

        foreach ($request->balances as $item) {
            $amount = (float) ($item['amount'] ?? 0);
            if ($amount <= 0) continue;

            $account = $accountMap->get($item['account_id']);
            if (!$account) continue;

            if ($account->normal_balance === 'Debet') {
                $totalDebit += $amount;
                $linesToInsert[] = [
                    'account_id'  => $account->id,
                    'debit'       => $amount,
                    'credit'      => 0,
                    'description' => "Saldo Awal - {$account->code} {$account->name}",
                ];
            } else {
                $totalCredit += $amount;
                $linesToInsert[] = [
                    'account_id'  => $account->id,
                    'debit'       => 0,
                    'credit'      => $amount,
                    'description' => "Saldo Awal - {$account->code} {$account->name}",
                ];
            }
        }

        if (empty($linesToInsert)) {
            throw ValidationException::withMessages([
                'balances' => 'Harap isi minimal satu akun dengan nominal saldo awal lebih dari 0.',
            ]);
        }

        // Validate Double Entry Balance
        $difference = abs($totalDebit - $totalCredit);
        if ($difference > 0.01) {
            $formattedDiff = number_format($difference, 2, ',', '.');
            $formattedDeb = number_format($totalDebit, 2, ',', '.');
            $formattedCre = number_format($totalCredit, 2, ',', '.');
            throw ValidationException::withMessages([
                'balances' => "Saldo Awal belum seimbang! Total Debit: Rp {$formattedDeb}, Total Kredit: Rp {$formattedCre}. Selisih: Rp {$formattedDiff}.",
            ]);
        }

        return DB::transaction(function () use ($period, $request, $linesToInsert, $totalDebit, $totalCredit) {
            // Find existing opening balance journal for this period
            $journal = JournalEntry::where('fiscal_period_id', $period->id)
                ->where('is_opening', true)
                ->whereNull('deleted_at')
                ->first();

            $isNew = false;
            $oldData = null;

            if ($journal) {
                $oldData = [
                    'reference'   => $journal->reference,
                    'total_debit' => $journal->total_debit,
                ];

                // Delete existing lines
                $journal->lines()->delete();

                // Update journal header
                $journal->update([
                    'entry_date' => $request->entry_date,
                    'status'     => 'posted',
                    'posted_by'  => auth()->id(),
                    'posted_at'  => now(),
                ]);
            } else {
                $isNew = true;
                $refNumber = 'SA-' . $period->start_date->format('Ym');

                // Ensure unique reference
                $suffix = 1;
                while (JournalEntry::where('reference', $refNumber)->exists()) {
                    $refNumber = 'SA-' . $period->start_date->format('Ym') . '-' . $suffix++;
                }

                $journal = JournalEntry::create([
                    'entry_date'       => $request->entry_date,
                    'reference'        => $refNumber,
                    'description'      => "Saldo Awal Neraca - Cut Off Migrasi Sistem ({$period->name})",
                    'fiscal_period_id' => $period->id,
                    'created_by'       => auth()->id(),
                    'is_closing'       => false,
                    'is_opening'       => true,
                    'status'           => 'posted',
                    'posted_by'        => auth()->id(),
                    'posted_at'        => now(),
                ]);
            }

            // Insert new lines
            foreach ($linesToInsert as $line) {
                $journal->lines()->create($line);
            }

            return back()->with('success', "Saldo Awal periode '{$period->name}' berhasil disimpan dan seimbang (Rp " . number_format($totalDebit, 2, ',', '.') . ")!");
        });
    }
}
