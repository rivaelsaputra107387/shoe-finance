<?php

namespace App\Filament\Pages;

use App\Models\FiscalPeriod;
use App\Services\BalanceSheetService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Card;

class BalanceSheet extends Page implements HasForms, HasInfolists
{
    use InteractsWithForms, InteractsWithInfolists;

    protected static ?string $navigationIcon = 'heroicon-o-scale';
    protected static ?string $navigationLabel = 'Neraca';
    protected static ?string $navigationGroup = 'Laporan Keuangan';
    protected static ?string $title = 'Neraca (Balance Sheet)';
    protected static ?int $navigationSort = 4;
    protected static string $view = 'filament.pages.balance-sheet';

    public ?int $fiscal_period_id = null;
    public array $reportData = [];

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
                ->reactive(),
        ])->columns(1);
    }

    public function generateReport(): void
    {
        if (!$this->fiscal_period_id) return;

        $service = new BalanceSheetService();
        $result = $service->generate($this->fiscal_period_id);

        $this->reportData = [
            'period_name' => $result['period']->name,
            'current_assets' => $result['current_assets']->toArray(),
            'total_current_assets' => (float)$result['total_current_assets'],
            'fixed_assets' => $result['fixed_assets']->toArray(),
            'total_fixed_assets' => (float)$result['total_fixed_assets'],
            'total_assets' => (float)$result['total_assets'],
            'current_liabilities' => $result['current_liabilities']->toArray(),
            'total_current_liabilities' => (float)$result['total_current_liabilities'],
            'long_term_liabilities' => $result['long_term_liabilities'] ? $result['long_term_liabilities']->toArray() : [],
            'total_long_term_liabilities' => (float)($result['total_long_term_liabilities'] ?? 0),
            'total_liabilities' => (float)$result['total_liabilities'],
            'equity' => $result['equity']->toArray(),
            'total_equity' => (float)$result['total_equity'],
            'total_liabilities_and_equity' => (float)$result['total_liabilities_and_equity'],
            'is_balanced' => (bool)$result['is_balanced'],
        ];
    }

    /**
     * Filament Native Infolist for the Balance Sheet
     */
    public function reportInfolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->state($this->reportData)
            ->schema([
                // 1. Balance Integrity Notification Banner
                Card::make([
                    TextEntry::make('is_balanced')
                        ->label('')
                        ->hiddenLabel()
                        ->formatStateUsing(fn ($state) => $state 
                            ? '✓ Neraca Seimbang — Total Aset = Total Kewajiban + Ekuitas' 
                            : '⚠ PERINGATAN: Neraca Tidak Seimbang!'
                        )
                        ->color(fn ($state) => $state ? 'success' : 'danger')
                        ->weight('bold')
                        ->size('md'),
                ]),

                // 2. Main 2-column layout (Left: Assets, Right: Liabilities & Equity)
                Grid::make(2)
                    ->schema([
                        // ── LEFT COLUMN: ASET ──
                        Grid::make(1)
                            ->columnSpan(1)
                            ->schema([
                                Section::make('ASET')
                                    ->schema([
                                        // Aset Lancar
                                        Section::make('Aset Lancar')
                                            ->collapsible()
                                            ->schema([
                                                RepeatableEntry::make('current_assets')
                                                    ->label('')
                                                    ->hiddenLabel()
                                                    ->schema([
                                                        Grid::make(12)->schema([
                                                            TextEntry::make('name')->label('')->hiddenLabel()->columnSpan(9),
                                                            TextEntry::make('balance')
                                                                ->label('')
                                                                ->hiddenLabel()
                                                                ->columnSpan(3)
                                                                ->formatStateUsing(fn ($state) => $state < 0 ? '(Rp ' . number_format(abs($state), 2, ',', '.') . ')' : 'Rp ' . number_format($state, 2, ',', '.'))
                                                                ->alignEnd(),
                                                        ]),
                                                    ]),
                                                
                                                Grid::make(12)->schema([
                                                    TextEntry::make('total_current_assets')
                                                        ->label('Total Aset Lancar')
                                                        ->columnSpan(12)
                                                        ->formatStateUsing(fn ($state) => $state < 0 ? '(Rp ' . number_format(abs($state), 2, ',', '.') . ')' : 'Rp ' . number_format($state, 2, ',', '.'))
                                                        ->alignEnd()
                                                        ->weight('bold')
                                                        ->color('info'),
                                                ]),
                                            ]),

                                        // Aset Tetap
                                        Section::make('Aset Tetap')
                                            ->collapsible()
                                            ->schema([
                                                RepeatableEntry::make('fixed_assets')
                                                    ->label('')
                                                    ->hiddenLabel()
                                                    ->schema([
                                                        Grid::make(12)->schema([
                                                            TextEntry::make('name')->label('')->hiddenLabel()->columnSpan(9),
                                                            TextEntry::make('balance')
                                                                ->label('')
                                                                ->hiddenLabel()
                                                                ->columnSpan(3)
                                                                ->formatStateUsing(fn ($state) => $state < 0 ? '(Rp ' . number_format(abs($state), 2, ',', '.') . ')' : 'Rp ' . number_format($state, 2, ',', '.'))
                                                                ->alignEnd(),
                                                        ]),
                                                    ]),
                                                
                                                Grid::make(12)->schema([
                                                    TextEntry::make('total_fixed_assets')
                                                        ->label('Total Aset Tetap')
                                                        ->columnSpan(12)
                                                        ->formatStateUsing(fn ($state) => $state < 0 ? '(Rp ' . number_format(abs($state), 2, ',', '.') . ')' : 'Rp ' . number_format($state, 2, ',', '.'))
                                                        ->alignEnd()
                                                        ->weight('bold')
                                                        ->color('info'),
                                                ]),
                                            ]),

                                        // TOTAL ASET CARD
                                        Card::make([
                                            Grid::make(12)->schema([
                                                TextEntry::make('total_assets')
                                                    ->label('TOTAL ASET')
                                                    ->columnSpan(12)
                                                    ->formatStateUsing(fn ($state) => $state < 0 ? '(Rp ' . number_format(abs($state), 2, ',', '.') . ')' : 'Rp ' . number_format($state, 2, ',', '.'))
                                                    ->alignEnd()
                                                    ->weight('black')
                                                    ->color('primary')
                                                    ->size('lg'),
                                            ]),
                                        ]),
                                    ]),
                            ]),

                        // ── RIGHT COLUMN: KEWAJIBAN & EKUITAS ──
                        Grid::make(1)
                            ->columnSpan(1)
                            ->schema([
                                Section::make('KEWAJIBAN & EKUITAS')
                                    ->schema([
                                        // Kewajiban Lancar
                                        Section::make('Kewajiban Lancar')
                                            ->collapsible()
                                            ->schema([
                                                RepeatableEntry::make('current_liabilities')
                                                    ->label('')
                                                    ->hiddenLabel()
                                                    ->schema([
                                                        Grid::make(12)->schema([
                                                            TextEntry::make('name')->label('')->hiddenLabel()->columnSpan(9),
                                                            TextEntry::make('balance')
                                                                ->label('')
                                                                ->hiddenLabel()
                                                                ->columnSpan(3)
                                                                ->formatStateUsing(fn ($state) => $state < 0 ? '(Rp ' . number_format(abs($state), 2, ',', '.') . ')' : 'Rp ' . number_format($state, 2, ',', '.'))
                                                                ->alignEnd(),
                                                        ]),
                                                    ]),
                                                
                                                Grid::make(12)->schema([
                                                    TextEntry::make('total_current_liabilities')
                                                        ->label('Total Kewajiban Lancar')
                                                        ->columnSpan(12)
                                                        ->formatStateUsing(fn ($state) => $state < 0 ? '(Rp ' . number_format(abs($state), 2, ',', '.') . ')' : 'Rp ' . number_format($state, 2, ',', '.'))
                                                        ->alignEnd()
                                                        ->weight('bold')
                                                        ->color('warning'),
                                                ]),
                                            ]),

                                        // Kewajiban Jangka Panjang
                                        Section::make('Kewajiban Jangka Panjang')
                                            ->visible(fn () => !empty($this->reportData['long_term_liabilities']))
                                            ->collapsible()
                                            ->schema([
                                                RepeatableEntry::make('long_term_liabilities')
                                                    ->label('')
                                                    ->hiddenLabel()
                                                    ->schema([
                                                        Grid::make(12)->schema([
                                                            TextEntry::make('name')->label('')->hiddenLabel()->columnSpan(9),
                                                            TextEntry::make('balance')
                                                                ->label('')
                                                                ->hiddenLabel()
                                                                ->columnSpan(3)
                                                                ->formatStateUsing(fn ($state) => $state < 0 ? '(Rp ' . number_format(abs($state), 2, ',', '.') . ')' : 'Rp ' . number_format($state, 2, ',', '.'))
                                                                ->alignEnd(),
                                                        ]),
                                                    ]),
                                                
                                                Grid::make(12)->schema([
                                                    TextEntry::make('total_long_term_liabilities')
                                                        ->label('Total Kewajiban Jangka Panjang')
                                                        ->columnSpan(12)
                                                        ->formatStateUsing(fn ($state) => $state < 0 ? '(Rp ' . number_format(abs($state), 2, ',', '.') . ')' : 'Rp ' . number_format($state, 2, ',', '.'))
                                                        ->alignEnd()
                                                        ->weight('bold')
                                                        ->color('warning'),
                                                ]),
                                            ]),

                                        // Ekuitas
                                        Section::make('Ekuitas')
                                            ->collapsible()
                                            ->schema([
                                                RepeatableEntry::make('equity')
                                                    ->label('')
                                                    ->hiddenLabel()
                                                    ->schema([
                                                        Grid::make(12)->schema([
                                                            TextEntry::make('name')->label('')->hiddenLabel()->columnSpan(9),
                                                            TextEntry::make('balance')
                                                                ->label('')
                                                                ->hiddenLabel()
                                                                ->columnSpan(3)
                                                                ->formatStateUsing(fn ($state) => $state < 0 ? '(Rp ' . number_format(abs($state), 2, ',', '.') . ')' : 'Rp ' . number_format($state, 2, ',', '.'))
                                                                ->alignEnd(),
                                                        ]),
                                                    ]),
                                                
                                                Grid::make(12)->schema([
                                                    TextEntry::make('total_equity')
                                                        ->label('Total Ekuitas')
                                                        ->columnSpan(12)
                                                        ->formatStateUsing(fn ($state) => $state < 0 ? '(Rp ' . number_format(abs($state), 2, ',', '.') . ')' : 'Rp ' . number_format($state, 2, ',', '.'))
                                                        ->alignEnd()
                                                        ->weight('bold')
                                                        ->color('success'),
                                                ]),
                                            ]),

                                        // TOTAL KEWAJIBAN & EKUITAS CARD
                                        Card::make([
                                            Grid::make(12)->schema([
                                                TextEntry::make('total_liabilities_and_equity')
                                                    ->label('TOTAL KEWAJIBAN & EKUITAS')
                                                    ->columnSpan(12)
                                                    ->formatStateUsing(fn ($state) => $state < 0 ? '(Rp ' . number_format(abs($state), 2, ',', '.') . ')' : 'Rp ' . number_format($state, 2, ',', '.'))
                                                    ->alignEnd()
                                                    ->weight('black')
                                                    ->color('success')
                                                    ->size('lg'),
                                            ]),
                                        ]),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public function exportPdf()
    {
        if (empty($this->reportData)) {
            $this->generateReport();
        }

        $pdf = Pdf::loadView('reports.balance-sheet-pdf', ['data' => $this->reportData]);
        return response()->streamDownload(
            fn () => print($pdf->output()),
            "neraca-{$this->reportData['period_name']}.pdf"
        );
    }
}
