<?php

namespace App\Filament\Pages;

use App\Models\Account;
use App\Models\FiscalPeriod;
use App\Services\TrialBalanceService;
use App\Services\AccountBalanceService;
use Filament\Pages\Page;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Collection;

class TrialBalance extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-table-cells';
    protected static ?string $navigationLabel = 'Neraca Lajur';
    protected static ?string $navigationGroup = 'Laporan Keuangan';
    protected static ?string $title = 'Neraca Lajur (Trial Balance)';
    protected static ?int $navigationSort = 2;
    protected static string $view = 'filament.pages.trial-balance';

    public ?int $fiscal_period_id = null;
    public ?array $data = [];
    public float $totalDebit  = 0.0;
    public float $totalCredit = 0.0;
    public bool  $isBalanced  = true;

    /** Pre-fetched cumulative totals keyed by account_id (serialisable as array) */
    public array $cachedTotals = [];

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
        $this->fiscal_period_id = $activePeriod?->id;

        if ($this->fiscal_period_id) {
            $this->generateReport();
        }
    }

    public function updatedFiscalPeriodId(): void
    {
        $this->generateReport();
    }

    public function getAvailablePeriodsProperty()
    {
        return FiscalPeriod::orderByDesc('start_date')->get();
    }

    /**
     * Run ONE bulk query and cache the result.
     * The Filament Table will then read from cachedTotals — ZERO additional queries per row.
     */
    public function generateReport(): void
    {
        if (!$this->fiscal_period_id) return;

        $balanceSvc = new AccountBalanceService();
        $totalsCollection = $balanceSvc->getCumulativeTotalsUpTo($this->fiscal_period_id);

        // Convert to plain array for Livewire serialisation
        $this->cachedTotals = $totalsCollection->map(fn ($row) => [
            'debit'  => (float) $row->total_debit,
            'credit' => (float) $row->total_credit,
        ])->toArray();

        // Compute totals for header display
        $totalDebit  = 0.0;
        $totalCredit = 0.0;
        foreach ($this->cachedTotals as $row) {
            $net = $row['debit'] - $row['credit'];
            if ($net > 0) $totalDebit  += $net;
            else          $totalCredit += abs($net);
        }
        $this->totalDebit  = round($totalDebit, 2);
        $this->totalCredit = round($totalCredit, 2);
        $this->isBalanced  = abs($totalDebit - $totalCredit) < 0.01;

        $this->resetTable();
    }

    /**
     * Filament Native Table — uses cachedTotals, ZERO per-row DB queries.
     */
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Account::active()
                    ->whereNotNull('parent_id') // Leaf accounts only
                    ->orderBy('code')
            )
            ->columns([
                TextColumn::make('code')
                    ->label('Kode Akun')
                    ->sortable()
                    ->searchable()
                    ->fontFamily('mono')
                    ->weight('bold'),

                TextColumn::make('name')
                    ->label('Nama Akun')
                    ->sortable()
                    ->searchable()
                    ->weight('medium'),

                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->colors([
                        'primary' => 'Aset',
                        'amber'   => 'Kewajiban',
                        'emerald' => 'Ekuitas',
                        'indigo'  => 'Pendapatan',
                        'rose'    => 'Beban',
                    ]),

                TextColumn::make('debit')
                    ->label('Saldo Debet')
                    ->getStateUsing(function ($record) {
                        $row = $this->cachedTotals[$record->id] ?? null;
                        if (!$row) return 0.0;
                        $net = $row['debit'] - $row['credit'];
                        return $net > 0 ? $net : 0.0;
                    })
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 2, ',', '.'))
                    ->alignEnd()
                    ->color('primary')
                    ->weight('semibold'),

                TextColumn::make('credit')
                    ->label('Saldo Kredit')
                    ->getStateUsing(function ($record) {
                        $row = $this->cachedTotals[$record->id] ?? null;
                        if (!$row) return 0.0;
                        $net = $row['debit'] - $row['credit'];
                        return $net < 0 ? abs($net) : 0.0;
                    })
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 2, ',', '.'))
                    ->alignEnd()
                    ->color('success')
                    ->weight('semibold'),
            ])
            ->defaultSort('code', 'asc')
            ->paginated(false);
    }
}
