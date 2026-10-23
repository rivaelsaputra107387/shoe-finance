<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class MasterController extends Controller
{
    public function accounts(Request $request): Response
    {
        $query = Account::with('parent')->orderBy('code');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $accounts = $query->paginate(25)->withQueryString();
        $parents  = Account::whereNull('parent_id')->orderBy('code')->get();

        return Inertia::render('Master/Accounts', [
            'accounts' => $accounts,
            'parents'  => $parents,
            'filters'  => $request->only(['type', 'search']),
        ]);
    }

    public function storeAccount(Request $request)
    {
        $request->validate([
            'code'               => ['required', 'string', 'unique:accounts,code'],
            'name'               => ['required', 'string', 'max:255'],
            'type'               => ['required', 'string'],
            'normal_balance'     => ['required', 'string', 'in:Debet,Kredit'],
            'report_category'    => ['required', 'string', 'in:Neraca,Laba Rugi'],
            'cash_flow_category' => ['nullable', 'string'],
            'parent_id'          => ['nullable', 'exists:accounts,id'],
        ]);

        Account::create($request->all());

        return back()->with('success', 'Akun COA berhasil dibuat.');
    }

    public function updateAccount(Request $request, Account $account)
    {
        $request->validate([
            'code'               => ['required', 'string', 'unique:accounts,code,' . $account->id],
            'name'               => ['required', 'string', 'max:255'],
            'type'               => ['required', 'string'],
            'normal_balance'     => ['required', 'string', 'in:Debet,Kredit'],
            'report_category'    => ['required', 'string', 'in:Neraca,Laba Rugi'],
            'cash_flow_category' => ['nullable', 'string'],
            'parent_id'          => ['nullable', 'exists:accounts,id'],
        ]);

        $account->update($request->all());

        return back()->with('success', 'Akun COA berhasil diperbarui.');
    }

    public function deleteAccount(Account $account)
    {
        if ($account->journalLines()->count() > 0) {
            return back()->with('error', 'Akun ini tidak dapat dihapus karena sudah memiliki transaksi jurnal.');
        }

        $account->delete();

        return back()->with('success', 'Akun COA berhasil dihapus.');
    }

    public function bulkDeleteAccounts(Request $request)
    {
        $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['exists:accounts,id'],
        ]);

        $count = 0;
        $failedCount = 0;

        foreach ($request->ids as $id) {
            $account = Account::find($id);
            if ($account) {
                if ($account->journalLines()->count() > 0) {
                    $failedCount++;
                    continue;
                }
                $account->delete();
                $count++;
            }
        }

        $msg = "Berhasil menghapus {$count} akun COA.";
        if ($failedCount > 0) {
            $msg .= " ({$failedCount} akun dilewati karena sudah memiliki transaksi).";
        }

        return back()->with('success', $msg);
    }

    public function fiscalPeriods(): Response
    {
        $periods = FiscalPeriod::orderByDesc('start_date')->get();

        return Inertia::render('Master/FiscalPeriods', [
            'periods' => $periods,
        ]);
    }

    public function storeFiscalPeriod(Request $request)
    {
        $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        FiscalPeriod::create([
            'name'       => $request->name,
            'start_date' => $request->start_date,
            'end_date'   => $request->end_date,
            'status'     => 'open',
        ]);

        return back()->with('success', 'Periode Akuntansi berhasil dibuat.');
    }

    public function updateFiscalPeriod(Request $request, FiscalPeriod $fiscalPeriod)
    {
        $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
            'status'     => ['nullable', 'in:open,reopened'],
        ]);

        $data = [
            'name'       => $request->name,
            'start_date' => $request->start_date,
            'end_date'   => $request->end_date,
        ];

        if ($request->has('status') && $fiscalPeriod->status !== 'closed') {
            $data['status'] = $request->status;
        }

        $fiscalPeriod->update($data);

        return back()->with('success', 'Periode Akuntansi berhasil diperbarui.');
    }

    public function deleteFiscalPeriod(FiscalPeriod $fiscalPeriod)
    {
        if ($fiscalPeriod->status === 'closed') {
            return back()->with('error', 'Gagal menghapus: Periode yang sudah ditutup tidak bisa dihapus.');
        }

        $journalCount = \App\Models\JournalEntry::where('fiscal_period_id', $fiscalPeriod->id)->count();
        if ($journalCount > 0) {
            return back()->with('error', "Gagal menghapus: Periode ini sudah memiliki {$journalCount} entri jurnal.");
        }

        $fiscalPeriod->delete();

        return back()->with('success', "Periode '{$fiscalPeriod->name}' berhasil dihapus.");
    }

    public function reopenFiscalPeriod(FiscalPeriod $fiscalPeriod)
    {
        if ($fiscalPeriod->status !== 'closed') {
            return back()->with('error', 'Hanya periode yang sudah ditutup yang bisa dibuka ulang.');
        }

        // Enforce sequential reopening: cannot reopen if any later period is still closed
        $hasLaterClosedPeriod = FiscalPeriod::where('start_date', '>', $fiscalPeriod->start_date)
            ->where('status', 'closed')
            ->exists();

        if ($hasLaterClosedPeriod) {
            return back()->with('error', "Gagal membuka ulang: Terdapat periode setelah '{$fiscalPeriod->name}' yang masih berstatus ditutup. Harap buka ulang periode terbaru terlebih dahulu secara berurutan.");
        }

        DB::transaction(function () use ($fiscalPeriod) {
            // Delete previous closing entries so nominal accounts return to their unclosed balances
            $closingEntries = JournalEntry::where('fiscal_period_id', $fiscalPeriod->id)
                ->where('is_closing', true)
                ->get();

            foreach ($closingEntries as $entry) {
                $entry->lines()->delete();
                $entry->forceDelete();
            }

            $fiscalPeriod->update([
                'status'    => 'reopened',
                'closed_by' => null,
            ]);
        });

        return back()->with('success', "Periode '{$fiscalPeriod->name}' berhasil dibuka ulang (Reopened). Jurnal penutup periode ini telah dibatalkan sehingga saldo akun kembali aktif.");
    }
}
