<?php

namespace App\Filament\Pages;

use App\Models\FiscalPeriod;
use App\Services\CashFlowReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Card;

class CashFlowStatement extends Page implements HasForms, HasInfolists
{
    use InteractsWithForms, InteractsWithInfolists;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Laporan Arus Kas';
    protected static ?string $navigationGroup = 'Laporan Keuangan';
    protected static ?string $title = 'Laporan Arus Kas';
    protected static ?int $navigationSort = 6;
    protected static string $view = 'filament.pages.cash-flow-statement';

    public ?int $fiscal_period_id = null;
    public ?array $data = [];
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

    public function updatedFiscalPeriodId(): void
    {
        $this->generateReport();
    }

    public function getAvailablePeriodsProperty()
    {
        return FiscalPeriod::orderByDesc('start_date')->get();
    }

    public function generateReport(): void
    {
        if (!$this->fiscal_period_id) return;

        $service = new CashFlowReportService();
        $result = $service->generate($this->fiscal_period_id);
        
        $this->reportData = [
            'period_name' => $result['period']->name,
            'operating' => $result['operating'],
            'total_operating' => (float)$result['total_operating'],
            'investing' => $result['investing'],
            'total_investing' => (float)$result['total_investing'],
            'financing' => $result['financing'],
            'total_financing' => (float)$result['total_financing'],
            'net_increase' => (float)$result['net_increase'],
            'beginning_cash' => (float)$result['beginning_cash'],
            'ending_cash' => (float)$result['ending_cash'],
            'is_valid' => (bool)$result['is_valid'],
        ];
    }

    /**
     * Filament Native Infolist for the Cash Flow Statement
     */
    public function reportInfolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->state($this->reportData)
            ->schema([
                // 1. Validity Banner
                Card::make([
                    TextEntry::make('is_valid')
                        ->label('')
                        ->hiddenLabel()
                        ->formatStateUsing(fn ($state) => $state 
                            ? '✓ Laporan Arus Kas Valid — Saldo kas cocok dengan saldo buku besar.' 
                            : '⚠ PERINGATAN: Saldo kas akhir dari laporan ini tidak cocok dengan saldo aktual di buku besar!'
                        )
                        ->color(fn ($state) => $state ? 'success' : 'danger')
                        ->weight('bold')
                        ->size('md'),
                ]),

                // 2. Operating Cash Flow
                Section::make('ARUS KAS DARI AKTIVITAS OPERASI')
                    ->collapsible()
                    ->schema([
                        RepeatableEntry::make('operating')
                            ->label('')
                            ->hiddenLabel()
                            ->schema([
                                Grid::make(12)->schema([
                                    TextEntry::make('account_name')->label('')->hiddenLabel()->columnSpan(9),
                                    TextEntry::make('amount')
                                        ->label('')
                                        ->hiddenLabel()
                                        ->columnSpan(3)
                                        ->formatStateUsing(fn ($state) => $state < 0 ? '(Rp ' . number_format(abs($state), 2, ',', '.') . ')' : 'Rp ' . number_format($state, 2, ',', '.'))
                                        ->alignEnd(),
                                ]),
                            ]),
                        
                        Grid::make(12)->schema([
                            TextEntry::make('total_operating')
                                ->label('Arus Kas Bersih dari Aktivitas Operasi')
                                ->columnSpan(12)
                                ->formatStateUsing(fn ($state) => $state < 0 ? '(Rp ' . number_format(abs($state), 2, ',', '.') . ')' : 'Rp ' . number_format($state, 2, ',', '.'))
                                ->alignEnd()
                                ->weight('bold')
                                ->color(fn ($state) => $state >= 0 ? 'success' : 'danger'),
                        ]),
                    ]),

                // 3. Investing Cash Flow
                Section::make('ARUS KAS DARI AKTIVITAS INVESTASI')
                    ->collapsible()
                    ->schema([
                        RepeatableEntry::make('investing')
                            ->label('')
                            ->hiddenLabel()
                            ->schema([
                                Grid::make(12)->schema([
                                    TextEntry::make('account_name')->label('')->hiddenLabel()->columnSpan(9),
                                    TextEntry::make('amount')
                                        ->label('')
                                        ->hiddenLabel()
                                        ->columnSpan(3)
                                        ->formatStateUsing(fn ($state) => $state < 0 ? '(Rp ' . number_format(abs($state), 2, ',', '.') . ')' : 'Rp ' . number_format($state, 2, ',', '.'))
                                        ->alignEnd(),
                                ]),
                            ]),
                        
                        Grid::make(12)->schema([
                            TextEntry::make('total_investing')
                                ->label('Arus Kas Bersih dari Aktivitas Investasi')
                                ->columnSpan(12)
                                ->formatStateUsing(fn ($state) => $state < 0 ? '(Rp ' . number_format(abs($state), 2, ',', '.') . ')' : 'Rp ' . number_format($state, 2, ',', '.'))
                                ->alignEnd()
                                ->weight('bold')
                                ->color(fn ($state) => $state >= 0 ? 'success' : 'danger'),
                        ]),
                    ]),

                // 4. Financing Cash Flow
                Section::make('ARUS KAS DARI AKTIVITAS PENDANAAN')
                    ->collapsible()
                    ->schema([
                        RepeatableEntry::make('financing')
                            ->label('')
                            ->hiddenLabel()
                            ->schema([
                                Grid::make(12)->schema([
                                    TextEntry::make('account_name')->label('')->hiddenLabel()->columnSpan(9),
                                    TextEntry::make('amount')
                                        ->label('')
                                        ->hiddenLabel()
                                        ->columnSpan(3)
                                        ->formatStateUsing(fn ($state) => $state < 0 ? '(Rp ' . number_format(abs($state), 2, ',', '.') . ')' : 'Rp ' . number_format($state, 2, ',', '.'))
                                        ->alignEnd(),
                                ]),
                            ]),
                        
                        Grid::make(12)->schema([
                            TextEntry::make('total_financing')
                                ->label('Arus Kas Bersih dari Aktivitas Pendanaan')
                                ->columnSpan(12)
                                ->formatStateUsing(fn ($state) => $state < 0 ? '(Rp ' . number_format(abs($state), 2, ',', '.') . ')' : 'Rp ' . number_format($state, 2, ',', '.'))
                                ->alignEnd()
                                ->weight('bold')
                                ->color(fn ($state) => $state >= 0 ? 'success' : 'danger'),
                        ]),
                    ]),

                // 5. Final Summary Card
                Card::make([
                    // Kenaikan Kas Bersih
                    Grid::make(12)->schema([
                        TextEntry::make('net_increase_label')
                            ->label('')
                            ->hiddenLabel()
                            ->default('Kenaikan/(Penurunan) Kas Bersih')
                            ->columnSpan(9)
                            ->weight('semibold'),
                        TextEntry::make('net_increase')
                            ->label('')
                            ->hiddenLabel()
                            ->columnSpan(3)
                            ->formatStateUsing(fn ($state) => $state < 0 ? '(Rp ' . number_format(abs($state), 2, ',', '.') . ')' : 'Rp ' . number_format($state, 2, ',', '.'))
                            ->alignEnd()
                            ->weight('bold')
                            ->color(fn ($state) => $state >= 0 ? 'success' : 'danger'),
                    ]),

                    // Kas Awal
                    Grid::make(12)->schema([
                        TextEntry::make('beginning_cash_label')
                            ->label('')
                            ->hiddenLabel()
                            ->default('Saldo Kas Awal Periode')
                            ->columnSpan(9)
                            ->color('gray'),
                        TextEntry::make('beginning_cash')
                            ->label('')
                            ->hiddenLabel()
                            ->columnSpan(3)
                            ->formatStateUsing(fn ($state) => $state < 0 ? '(Rp ' . number_format(abs($state), 2, ',', '.') . ')' : 'Rp ' . number_format($state, 2, ',', '.'))
                            ->alignEnd()
                            ->weight('semibold'),
                    ]),

                    // Kas Akhir
                    Grid::make(12)->schema([
                        TextEntry::make('ending_cash_label')
                            ->label('')
                            ->hiddenLabel()
                            ->default('SALDO KAS AKHIR PERIODE')
                            ->columnSpan(9)
                            ->weight('bold')
                            ->size('lg'),
                        TextEntry::make('ending_cash')
                            ->label('')
                            ->hiddenLabel()
                            ->columnSpan(3)
                            ->formatStateUsing(fn ($state) => $state < 0 ? '(Rp ' . number_format(abs($state), 2, ',', '.') . ')' : 'Rp ' . number_format($state, 2, ',', '.'))
                            ->alignEnd()
                            ->weight('black')
                            ->color('primary')
                            ->size('lg'),
                    ]),
                ]),
            ]);
    }

    public function exportPdf()
    {
        if (empty($this->reportData)) {
            $this->generateReport();
        }

        $pdf = Pdf::loadView('reports.cash-flow-statement-pdf', ['data' => $this->reportData]);
        return response()->streamDownload(
            fn () => print($pdf->output()),
            "laporan-arus-kas-{$this->reportData['period_name']}.pdf"
        );
    }
}
