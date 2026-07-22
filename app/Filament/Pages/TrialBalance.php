<?php

namespace App\Filament\Pages;

use App\Models\Account;
use App\Models\FiscalPeriod;
use App\Services\TrialBalanceService;
use Filament\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class TrialBalance extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-table-cells';
    protected static ?string $navigationLabel = 'Neraca Lajur';
    protected static ?string $navigationGroup = 'Laporan Keuangan';
    protected static ?string $title = 'Neraca Lajur (Trial Balance)';
    protected static ?int $navigationSort = 2;
    protected static string $view = 'filament.pages.trial-balance';

    public ?int $fiscal_period_id = null;
    
    public float $totalDebit = 0.0;
    public float $totalCredit = 0.0;
    public bool $isBalanced = true;

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

    public function form(Form $form): Form
    {
        return $form->schema([
            Select::make('fiscal_period_id')
                ->label('Periode')
                ->options(FiscalPeriod::orderByDesc('start_date')->pluck('name', 'id'))
                ->required()
                ->reactive()
                ->afterStateUpdated(fn () => $this->generateReport()),
        ])->columns(1);
    }

    public function generateReport(): void
    {
        if (!$this->fiscal_period_id) return;

        $service = new TrialBalanceService();
        $result = $service->generate($this->fiscal_period_id);
        
        $this->totalDebit = (float)$result['total_debit'];
        $this->totalCredit = (float)$result['total_credit'];
        $this->isBalanced = (bool)$result['is_balanced'];
        
        $this->resetTable();
    }

    /**
     * Filament Native Table implementation for Trial Balance
     */
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Account::active()
                    ->whereNotNull('parent_id') // Leaf accounts only
                    // Only show accounts that have transaction lines in the selected period
                    ->whereHas('journalEntryLines.journalEntry', function ($query) {
                        $query->where('fiscal_period_id', $this->fiscal_period_id)
                              ->where('status', 'posted')
                              ->whereNull('deleted_at');
                    })
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
                        'amber' => 'Kewajiban',
                        'emerald' => 'Ekuitas',
                        'indigo' => 'Pendapatan',
                        'rose' => 'Beban',
                    ]),

                TextColumn::make('debit')
                    ->label('Saldo Debet')
                    ->getStateUsing(fn ($record) => (float)$record->getTrialBalanceForPeriod($this->fiscal_period_id)['debit'])
                    ->formatStateUsing(fn ($state) => $state < 0 ? '(Rp ' . number_format(abs($state), 2, ',', '.') . ')' : 'Rp ' . number_format($state, 2, ',', '.'))
                    ->alignEnd()
                    ->color('primary')
                    ->weight('semibold'),

                TextColumn::make('credit')
                    ->label('Saldo Kredit')
                    ->getStateUsing(fn ($record) => (float)$record->getTrialBalanceForPeriod($this->fiscal_period_id)['credit'])
                    ->formatStateUsing(fn ($state) => $state < 0 ? '(Rp ' . number_format(abs($state), 2, ',', '.') . ')' : 'Rp ' . number_format($state, 2, ',', '.'))
                    ->alignEnd()
                    ->color('success')
                    ->weight('semibold'),
            ])
            ->defaultSort('code', 'asc')
            ->paginated(false); // Show all active accounts in one view
    }
}
