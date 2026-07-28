<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class JournalEntryController extends Controller
{
    public function index(Request $request): Response
    {
        $query = JournalEntry::with(['lines.account', 'creator', 'postedBy', 'fiscalPeriod'])
            ->orderBy('entry_date', 'desc')
            ->orderBy('id', 'desc');

        if ($request->filled('fiscal_period_id')) {
            $query->where('fiscal_period_id', $request->fiscal_period_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $entries = $query->paginate(20)->withQueryString();
        $periods = FiscalPeriod::orderByDesc('start_date')->get();

        return Inertia::render('Transactions/JournalList', [
            'entries' => $entries,
            'periods' => $periods,
            'filters' => $request->only(['fiscal_period_id', 'status', 'search']),
        ]);
    }

    public function create(): Response
    {
        $periods = FiscalPeriod::where('status', 'open')->orderByDesc('start_date')->get();
        $accounts = Account::active()->whereNotNull('parent_id')->orderBy('code')->get();

        return Inertia::render('Transactions/CreateJournal', [
            'periods' => $periods,
            'accounts' => $accounts,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'fiscal_period_id' => ['required', 'exists:fiscal_periods,id'],
            'entry_date'       => ['required', 'date'],
            'description'      => ['required', 'string', 'max:255'],
            'reference'        => ['nullable', 'string', 'max:100'],
            'lines'            => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['required', 'exists:accounts,id'],
            'lines.*.debit'    => ['required', 'numeric', 'min:0'],
            'lines.*.credit'   => ['required', 'numeric', 'min:0'],
        ]);

        // Check balance
        $totalDebit = array_sum(array_column($request->lines, 'debit'));
        $totalCredit = array_sum(array_column($request->lines, 'credit'));

        if (abs($totalDebit - $totalCredit) > 0.01) {
            return back()->with('error', 'Total Debit dan Total Kredit harus seimbang (Balance).');
        }

        DB::transaction(function () use ($request) {
            $user = auth()->user();
            $isOwnerOrFinance = $user->hasAnyRole(['owner', 'finance']);

            $entry = JournalEntry::create([
                'fiscal_period_id' => $request->fiscal_period_id,
                'entry_date'       => $request->entry_date,
                'description'      => $request->description,
                'reference'        => $request->reference ?: ('JU-' . date('YmdHis')),
                'created_by'       => $user->id,
                'status'           => $isOwnerOrFinance ? 'posted' : 'draft',
                'posted_by'        => $isOwnerOrFinance ? $user->id : null,
                'posted_at'        => $isOwnerOrFinance ? now() : null,
            ]);

            foreach ($request->lines as $line) {
                if (($line['debit'] > 0 || $line['credit'] > 0)) {
                    JournalEntryLine::create([
                        'journal_entry_id' => $entry->id,
                        'account_id'       => $line['account_id'],
                        'debit'            => $line['debit'],
                        'credit'           => $line['credit'],
                        'memo'             => $line['memo'] ?? null,
                    ]);
                }
            }
        });

        return redirect('/app/journal-entries')->with('success', 'Jurnal berhasil disimpan.');
    }

    public function submit(JournalEntry $journalEntry)
    {
        if ($journalEntry->status !== 'draft') {
            return back()->with('error', 'Hanya jurnal status draft yang dapat di-submit.');
        }

        $journalEntry->update([
            'status'       => 'unapproved',
            'submitted_by' => auth()->id(),
            'submitted_at' => now(),
        ]);

        return back()->with('success', 'Jurnal berhasil di-submit untuk persetujuan.');
    }

    public function approve(JournalEntry $journalEntry)
    {
        if (!auth()->user()->hasAnyRole(['owner', 'finance'])) {
            return back()->with('error', 'Anda tidak memiliki hak akses untuk menyetujui jurnal.');
        }

        $journalEntry->post();

        return back()->with('success', 'Jurnal berhasil disetujui dan diposting.');
    }
}
