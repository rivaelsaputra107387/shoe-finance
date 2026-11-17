<?php

namespace App\Filament\Pages;

use App\Models\Account;
use App\Models\FiscalPeriod;
use App\Models\JournalEntryLine;
use Filament\Pages\Page;
use Livewire\WithPagination;

class GeneralLedger extends Page
{
    use WithPagination;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationLabel = 'Buku Besar';
    protected static ?string $navigationGroup = 'Laporan Keuangan';
    protected static ?string $title = 'Buku Besar';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.pages.general-ledger';

    public ?int $account_id = null;
    public ?string $start_date = null;
    public ?string $end_date = null;

    public float $beginningBalance = 0.0;
    public float $pageStartBalance = 0.0;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole(['owner', 'finance']) ?? false;
    }

    public function mount(): void
    {
        if (!auth()->user()?->hasAnyRole(['owner', 'finance'])) {
            abort(403, 'Unauthorized access.');
        }

        $activePeriod = FiscalPeriod::active();
        if ($activePeriod) {
            $this->start_date = $activePeriod->start_date->format('Y-m-d');
            $this->end_date = $activePeriod->end_date->format('Y-m-d');
        }
    }

    public function updatedAccountId()
    {
        $this->resetPage();
    }

    public function updatedStartDate()
    {
        $this->resetPage();
    }

    public function updatedEndDate()
    {
        $this->resetPage();
    }

    public function getAccountsProperty()
    {
        return Account::active()
            ->whereNotNull('parent_id')
            ->orderBy('code')
            ->get();
    }

    public function calculateBeginningBalance(): float
    {
        if (!$this->account_id || !$this->start_date) {
            return 0.0;
        }

        $account = Account::find($this->account_id);
        if (!$account) return 0.0;

        $linesBefore = JournalEntryLine::query()
            ->where('account_id', $this->account_id)
            ->whereHas('journalEntry', function ($query) {
                $query->where('entry_date', '<', $this->start_date)
                      ->where('status', 'posted')
                      ->whereNull('deleted_at');
            })
            ->get();

        $debitSum = $linesBefore->sum('debit');
        $creditSum = $linesBefore->sum('credit');

        return $account->normal_balance === 'Debet' 
            ? ($debitSum - $creditSum) 
            : ($creditSum - $debitSum);
    }

    public function getLedgerEntriesProperty()
    {
        if (!$this->account_id || !$this->start_date || !$this->end_date) {
            return null;
        }

        $this->beginningBalance = $this->calculateBeginningBalance();
        $account = Account::find($this->account_id);
        $isDebitNormal = $account ? ($account->normal_balance === 'Debet') : true;

        $query = JournalEntryLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->whereNull('journal_entries.deleted_at')
            ->where('journal_entries.status', 'posted')
            ->where('journal_entry_lines.account_id', $this->account_id)
            ->whereBetween('journal_entries.entry_date', [$this->start_date, $this->end_date])
            ->select('journal_entry_lines.*', 'journal_entries.entry_date', 'journal_entries.reference', 'journal_entries.description')
            ->orderBy('journal_entries.entry_date', 'asc')
            ->orderBy('journal_entry_lines.id', 'asc');

        $entries = $query->paginate(50);

        // Calculate pageStartBalance based on first item of this page
        if ($entries->count() > 0) {
            $firstItem = $entries->first();
            $recordDate = $firstItem->entry_date;
            $recordId = $firstItem->id;

            $totals = JournalEntryLine::query()
                ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
                ->whereNull('journal_entries.deleted_at')
                ->where('journal_entries.status', 'posted')
                ->where('journal_entry_lines.account_id', $this->account_id)
                ->whereBetween('journal_entries.entry_date', [$this->start_date, $this->end_date])
                ->where(function ($q) use ($recordDate, $recordId) {
                    $q->where('journal_entries.entry_date', '<', $recordDate)
                      ->orWhere(function ($q2) use ($recordDate, $recordId) {
                          $q2->where('journal_entries.entry_date', '=', $recordDate)
                             ->where('journal_entry_lines.id', '<', $recordId); // Strictly less than first item
                      });
                })
                ->selectRaw('COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(credit), 0) as total_credit')
                ->first();

            $debitSum = (float) ($totals->total_debit ?? 0);
            $creditSum = (float) ($totals->total_credit ?? 0);

            $pageMutations = $isDebitNormal ? ($debitSum - $creditSum) : ($creditSum - $debitSum);
            $this->pageStartBalance = $this->beginningBalance + $pageMutations;
        } else {
            $this->pageStartBalance = $this->beginningBalance;
        }

        return $entries;
    }
}
