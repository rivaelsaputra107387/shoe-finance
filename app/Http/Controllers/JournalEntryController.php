<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class JournalEntryController extends Controller
{
    public function index(Request $request): Response
    {
        $activePeriod = FiscalPeriod::active();
        $defaultMonthYear = $activePeriod 
            ? date('Y-m', strtotime($activePeriod->start_date)) 
            : date('Y-m');

        $monthYear = $request->input('month_year', $defaultMonthYear);
        $day = $request->input('day', '');

        // Extract year and month
        $parts = explode('-', $monthYear);
        $year = isset($parts[0]) ? (int)$parts[0] : (int)date('Y');
        $month = isset($parts[1]) ? (int)$parts[1] : (int)date('m');

        $query = JournalEntry::with(['lines.account', 'creator', 'postedBy', 'fiscalPeriod'])
            ->where('status', '!=', 'draft')
            ->orderBy('entry_date', 'desc')
            ->orderBy('id', 'desc');

        if ($request->filled('day')) {
            $dayInt = (int)$day;
            $specificDate = Carbon::createFromDate($year, $month, $dayInt)->toDateString();
            $query->whereDate('entry_date', $specificDate);
        } else {
            $startDate = Carbon::createFromDate($year, $month, 1)->startOfDay();
            $endDate   = $startDate->copy()->endOfMonth()->endOfDay();
            $query->whereBetween('entry_date', [$startDate->toDateTimeString(), $endDate->toDateTimeString()]);
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

        $entries = $query->paginate(50)->withQueryString();

        return Inertia::render('Transactions/JournalList', [
            'entries' => $entries,
            'filters' => [
                'month_year' => $monthYear,
                'day'        => $day,
                'status'     => $request->input('status', ''),
                'search'     => $request->input('search', ''),
            ],
        ]);
    }

    public function draftJournals(Request $request): Response
    {
        $query = JournalEntry::with(['lines.account', 'creator', 'fiscalPeriod'])
            ->where('status', 'draft')
            ->orderBy('entry_date', 'desc')
            ->orderBy('id', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $entries = $query->paginate(20)->withQueryString();
        $accounts = Account::active()->whereNotNull('parent_id')->orderBy('code')->get();

        return Inertia::render('Transactions/DraftJournals', [
            'entries'  => $entries,
            'accounts' => $accounts,
            'filters'  => $request->only(['search']),
        ]);
    }

    public function create(): Response
    {
        $periods = FiscalPeriod::whereIn('status', ['open', 'reopened'])->orderByDesc('start_date')->get();
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
                        'description'      => $line['description'] ?? ($line['memo'] ?? null),
                    ]);
                }
            }
        });

        return redirect('/app/journal-entries')->with('success', 'Jurnal berhasil disimpan.');
    }

    public function show(JournalEntry $journalEntry): Response
    {
        $journalEntry->load(['lines.account', 'creator', 'postedBy', 'fiscalPeriod']);
        
        return Inertia::render('Transactions/ShowJournal', [
            'entry' => $journalEntry,
        ]);
    }

    public function edit(JournalEntry $journalEntry): Response
    {
        if ($journalEntry->status === 'posted') {
            return redirect('/app/journal-entries')->with('error', 'Jurnal yang sudah diposting tidak dapat diubah.');
        }

        $journalEntry->load('lines.account');
        $periods = FiscalPeriod::orderByDesc('start_date')->get();
        $accounts = Account::active()->whereNotNull('parent_id')->orderBy('code')->get();

        return Inertia::render('Transactions/EditJournal', [
            'entry'    => $journalEntry,
            'periods'  => $periods,
            'accounts' => $accounts,
        ]);
    }

    public function update(Request $request, JournalEntry $journalEntry)
    {
        if ($journalEntry->status === 'posted') {
            return back()->with('error', 'Jurnal yang sudah diposting tidak dapat diubah.');
        }

        $request->validate([
            'fiscal_period_id'   => ['required', 'exists:fiscal_periods,id'],
            'entry_date'         => ['required', 'date'],
            'description'        => ['required', 'string', 'max:255'],
            'reference'          => ['nullable', 'string', 'max:100'],
            'lines'              => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['required', 'exists:accounts,id'],
            'lines.*.debit'      => ['required', 'numeric', 'min:0'],
            'lines.*.credit'     => ['required', 'numeric', 'min:0'],
        ]);

        $totalDebit = array_sum(array_column($request->lines, 'debit'));
        $totalCredit = array_sum(array_column($request->lines, 'credit'));

        if (abs($totalDebit - $totalCredit) > 0.01) {
            return back()->with('error', 'Total Debit dan Total Kredit harus seimbang (Balance).');
        }

        DB::transaction(function () use ($request, $journalEntry) {
            $journalEntry->update([
                'fiscal_period_id' => $request->fiscal_period_id,
                'entry_date'       => $request->entry_date,
                'description'      => $request->description,
                'reference'        => $request->reference,
            ]);

            $journalEntry->lines()->delete();

            foreach ($request->lines as $line) {
                if (($line['debit'] > 0 || $line['credit'] > 0)) {
                    JournalEntryLine::create([
                        'journal_entry_id' => $journalEntry->id,
                        'account_id'       => $line['account_id'],
                        'debit'            => $line['debit'],
                        'credit'           => $line['credit'],
                        'description'      => $line['description'] ?? ($line['memo'] ?? null),
                    ]);
                }
            }
        });

        $redirectPath = $journalEntry->status === 'draft' ? '/app/draft-journals' : '/app/journal-entries';
        return redirect($redirectPath)->with('success', 'Jurnal berhasil diperbarui.');
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

        \App\Models\BankMutation::where('journal_entry_id', $journalEntry->id)->update(['status' => 'unapproved']);

        return back()->with('success', 'Jurnal berhasil di-submit untuk persetujuan.');
    }

    public function approve(JournalEntry $journalEntry)
    {
        if (!auth()->user()->hasAnyRole(['owner', 'finance'])) {
            return back()->with('error', 'Anda tidak memiliki hak akses untuk menyetujui jurnal.');
        }

        // Cek jika masih ada Akun Sementara (9999)
        if ($journalEntry->lines()->whereHas('account', fn($q) => $q->where('code', '9999'))->exists()) {
            return back()->with('error', 'Harap Lengkapi Akun terlebih dahulu (Ganti Akun Sementara 9999) sebelum melakukan posting.');
        }

        $journalEntry->post();
        \App\Models\BankMutation::where('journal_entry_id', $journalEntry->id)->update(['status' => 'posted', 'posted_by' => auth()->id()]);

        return back()->with('success', 'Jurnal berhasil disetujui dan diposting.');
    }

    public function reject(JournalEntry $journalEntry)
    {
        if (!auth()->user()->hasAnyRole(['owner', 'finance'])) {
            return back()->with('error', 'Anda tidak memiliki hak akses untuk menolak jurnal.');
        }

        if ($journalEntry->status !== 'unapproved') {
            return back()->with('error', 'Hanya jurnal status unapproved yang dapat ditolak.');
        }

        $journalEntry->update([
            'status'       => 'draft',
            'submitted_by' => null,
            'submitted_at' => null,
        ]);

        \App\Models\BankMutation::where('journal_entry_id', $journalEntry->id)->update(['status' => 'drafted']);

        return back()->with('success', 'Jurnal dikembalikan ke status Draft.');
    }

    public function updateLines(Request $request, JournalEntry $journalEntry)
    {
        $request->validate([
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['required', 'exists:accounts,id'],
            'lines.*.debit' => ['required', 'numeric', 'min:0'],
            'lines.*.credit' => ['required', 'numeric', 'min:0'],
        ]);

        if (in_array($journalEntry->status, ['posted'])) {
            return back()->with('error', 'Jurnal yang sudah diposting tidak dapat diubah.');
        }

        DB::transaction(function () use ($request, $journalEntry) {
            $journalEntry->lines()->delete();

            foreach ($request->lines as $line) {
                if (($line['debit'] > 0 || $line['credit'] > 0)) {
                    JournalEntryLine::create([
                        'journal_entry_id' => $journalEntry->id,
                        'account_id'       => $line['account_id'],
                        'debit'            => $line['debit'],
                        'credit'           => $line['credit'],
                        'memo'             => $line['memo'] ?? null,
                    ]);
                }
            }
        });

        return back()->with('success', 'Akun jurnal berhasil diperbarui.');
    }

    public function bulkSubmit(Request $request)
    {
        $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['exists:journal_entries,id'],
        ]);

        $count = 0;
        foreach ($request->ids as $id) {
            $entry = JournalEntry::find($id);
            if ($entry && $entry->status === 'draft') {
                $entry->update([
                    'status'       => 'unapproved',
                    'submitted_by' => auth()->id(),
                    'submitted_at' => now(),
                ]);
                \App\Models\BankMutation::where('journal_entry_id', $entry->id)->update(['status' => 'unapproved']);
                $count++;
            }
        }

        return back()->with('success', "Berhasil me-submit {$count} jurnal untuk disetujui.");
    }

    public function bulkApprove(Request $request)
    {
        $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['exists:journal_entries,id'],
        ]);

        $count = 0;
        $failedCount = 0;

        foreach ($request->ids as $id) {
            $entry = JournalEntry::find($id);
            if ($entry && in_array($entry->status, ['draft', 'unapproved'])) {
                if ($entry->lines()->whereHas('account', fn($q) => $q->where('code', '9999'))->exists()) {
                    $failedCount++;
                    continue;
                }

                $entry->post();
                \App\Models\BankMutation::where('journal_entry_id', $entry->id)->update(['status' => 'posted', 'posted_by' => auth()->id()]);
                $count++;
            }
        }

        $msg = "Berhasil menyetujui & memposting {$count} jurnal.";
        if ($failedCount > 0) {
            $msg .= " ({$failedCount} jurnal dilewati karena masih memiliki Akun Sementara 9999).";
        }

        return back()->with('success', $msg);
    }

    public function bulkReject(Request $request)
    {
        $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['exists:journal_entries,id'],
        ]);

        $count = 0;
        foreach ($request->ids as $id) {
            $entry = JournalEntry::find($id);
            if ($entry && $entry->status === 'unapproved') {
                $entry->update([
                    'status'       => 'draft',
                    'submitted_by' => null,
                    'submitted_at' => null,
                ]);
                \App\Models\BankMutation::where('journal_entry_id', $entry->id)->update(['status' => 'drafted']);
                $count++;
            }
        }

        return back()->with('success', "Berhasil menolak {$count} jurnal dan mengembalikannya ke status Draft.");
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['exists:journal_entries,id'],
        ]);

        $count = 0;
        $failedCount = 0;

        foreach ($request->ids as $id) {
            $entry = JournalEntry::find($id);
            if ($entry) {
                if ($entry->status === 'posted') {
                    $failedCount++;
                    continue;
                }

                \App\Models\BankMutation::where('journal_entry_id', $entry->id)->update(['status' => 'pending', 'journal_entry_id' => null]);
                $entry->lines()->delete();
                $entry->delete();
                $count++;
            }
        }

        $msg = "Berhasil menghapus {$count} jurnal terpilih.";
        if ($failedCount > 0) {
            $msg .= " ({$failedCount} jurnal berstatus Posted dilewati).";
        }

        return back()->with('success', $msg);
    }
}
