<?php

namespace App\Http\Controllers;

use App\Rules\AccountCodeMatchesParent;
use App\Services\FiscalCalendar;
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
        $query = Account::with('parent')->withCount(['children', 'journalEntryLines'])->orderBy('code');

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
        $parent = $this->validatedParent($request);

        $request->validate([
            'code'               => ['required', 'string', 'max:10', 'unique:accounts,code', new AccountCodeMatchesParent($parent?->id, $request->input('name'))],
            'name'               => ['required', 'string', 'max:255'],
            'normal_balance'     => ['nullable', 'string', 'in:Debet,Kredit'],
            'cash_flow_category' => ['nullable', 'string'],
        ]);

        Account::create($this->accountAttributes($request, $parent) + ['code' => $request->code]);

        return back()->with('success', 'Akun COA berhasil dibuat.');
    }

    public function updateAccount(Request $request, Account $account)
    {
        if ($account->is_locked) {
            return back()->with('error', 'Akun ini terkunci dan sama di semua unit. Perubahan diajukan ke Tim IT.');
        }

        $parent = $this->validatedParent($request);

        $request->validate([
            'code'               => ['required', 'string', 'max:10', 'unique:accounts,code,' . $account->id, new AccountCodeMatchesParent($parent?->id, $request->input('name'))],
            'name'               => ['required', 'string', 'max:255'],
            'normal_balance'     => ['nullable', 'string', 'in:Debet,Kredit'],
            'cash_flow_category' => ['nullable', 'string'],
        ]);

        $account->update($this->accountAttributes($request, $parent) + ['code' => $request->code]);

        return back()->with('success', 'Akun COA berhasil diperbarui.');
    }

    public function deleteAccount(Account $account)
    {
        $blocker = $this->deletionBlocker($account);

        if ($blocker) {
            return back()->with('error', "Akun ini tidak dapat dihapus karena {$blocker}.");
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
                if ($this->deletionBlocker($account)) {
                    $failedCount++;
                    continue;
                }
                $account->delete();
                $count++;
            }
        }

        $msg = "Berhasil menghapus {$count} akun COA.";
        if ($failedCount > 0) {
            $msg .= " ({$failedCount} akun dilewati karena terkunci, punya sub-akun, atau sudah memiliki transaksi).";
        }

        return back()->with('success', $msg);
    }

    /**
     * Akun baru selalu sub-akun, karena akun induk dikelola terpusat.
     */
    private function validatedParent(Request $request): Account
    {
        $request->validate([
            'parent_id' => ['required', 'integer', 'exists:accounts,id'],
        ], [
            'parent_id.required' => 'Pilih akun induk.',
        ]);

        return Account::findOrFail($request->integer('parent_id'));
    }

    /**
     * Tipe dan kategori mengikuti induk. Saldo normal boleh berbeda untuk akun kontra, seperti Akumulasi Penyusutan.
     */
    private function accountAttributes(Request $request, Account $parent): array
    {
        return [
            'name'               => $request->name,
            'type'               => $parent->type,
            'normal_balance'     => $request->input('normal_balance') ?: $parent->normal_balance,
            'report_category'    => $parent->report_category,
            'cash_flow_category' => $request->input('cash_flow_category') ?: null,
            'parent_id'          => $parent->id,
        ];
    }

    private function deletionBlocker(Account $account): ?string
    {
        if ($account->is_locked) {
            return 'terkunci dan sama di semua unit';
        }

        if ($account->children()->exists()) {
            return 'masih memiliki sub-akun';
        }

        if ($account->journalEntryLines()->exists()) {
            return 'sudah memiliki transaksi jurnal';
        }

        return null;
    }

    public function fiscalPeriods(): Response
    {
        $periods = FiscalPeriod::orderByDesc('start_date')->get();

        return Inertia::render('Master/FiscalPeriods', [
            'periods' => $periods,
        ]);
    }

    public function storeFiscalPeriod(Request $request, FiscalCalendar $calendar)
    {
        $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date', 'unique:central.calendar_periods,start_date'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $failed = $calendar->addPeriod($request->name, $request->start_date, $request->end_date);

        if ($failed !== []) {
            return back()->with('warning', 'Periode dibuat, tetapi belum masuk ke unit: ' . implode(', ', $failed) . '. Hubungi Tim IT.');
        }

        return back()->with('success', 'Periode Akuntansi berhasil dibuat di semua unit.');
    }

    /**
     * Tanggal periode sama di semua unit, jadi yang bisa diubah per unit hanya statusnya.
     */
    public function updateFiscalPeriod(Request $request, FiscalPeriod $fiscalPeriod)
    {
        $request->validate([
            'status' => ['required', 'in:open,reopened'],
        ]);

        if ($fiscalPeriod->status === 'closed') {
            return back()->with('error', 'Periode yang sudah ditutup hanya bisa dibuka lewat Buka Ulang.');
        }

        $fiscalPeriod->update(['status' => $request->status]);

        return back()->with('success', 'Status periode berhasil diperbarui.');
    }

    public function deleteFiscalPeriod(FiscalPeriod $fiscalPeriod, FiscalCalendar $calendar)
    {
        $startDate = $fiscalPeriod->start_date->toDateString();
        $blocker = $calendar->removalBlocker($startDate);

        if ($blocker) {
            return back()->with('error', "Gagal menghapus: {$blocker}.");
        }

        $calendar->removePeriod($startDate);

        return back()->with('success', "Periode '{$fiscalPeriod->name}' berhasil dihapus dari semua unit.");
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
