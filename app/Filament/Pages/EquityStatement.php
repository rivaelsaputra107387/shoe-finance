<?php

namespace App\Filament\Pages;

use App\Models\FiscalPeriod;
use App\Services\EquityStatementService;
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
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Card;

class EquityStatement extends Page implements HasForms, HasInfolists
{
    use InteractsWithForms, InteractsWithInfolists;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-up';
    protected static ?string $navigationLabel = 'Laporan Perubahan Ekuitas';
    protected static ?string $navigationGroup = 'Laporan Keuangan';
    protected static ?string $title = 'Laporan Perubahan Ekuitas';
    protected static ?int $navigationSort = 5;
    protected static string $view = 'filament.pages.equity-statement';

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

        $service = new EquityStatementService();
        $result = $service->generate($this->fiscal_period_id);

        $this->reportData = [
            'period_name' => $result['period']->name,
            'beginning_capital' => (float)$result['beginning_capital'],
            'net_profit' => (float)$result['net_profit'],
            'prive' => (float)$result['prive'],
            'retained_earnings' => (float)$result['retained_earnings'],
            'ending_capital' => (float)$result['ending_capital'],
            'modal_account_name' => $result['modal_account_name'],
            'prive_account_name' => $result['prive_account_name'],
        ];
    }

    /**
     * Filament Native Infolist for the Equity Statement
     */
    public function reportInfolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->state($this->reportData)
            ->schema([
                Section::make('PERUBAHAN EKUITAS')
                    ->description(fn () => "Periode Laporan: " . ($this->reportData['period_name'] ?? ''))
                    ->schema([
                        // Row 1: Modal Awal
                        Grid::make(12)->schema([
                            TextEntry::make('modal_account_name')
                                ->label('')
                                ->hiddenLabel()
                                ->getStateUsing(fn () => ($this->reportData['modal_account_name'] ?? 'Modal') . ' (Awal Periode)')
                                ->columnSpan(9)
                                ->weight('medium'),
                            TextEntry::make('beginning_capital')
                                ->label('')
                                ->hiddenLabel()
                                ->columnSpan(3)
                                ->formatStateUsing(fn ($state) => $state < 0 ? '(Rp ' . number_format(abs($state), 2, ',', '.') . ')' : 'Rp ' . number_format($state, 2, ',', '.'))
                                ->alignEnd()
                                ->weight('bold'),
                        ]),

                        // Row 2: Laba/Rugi Periode Berjalan
                        Grid::make(12)->schema([
                            TextEntry::make('net_profit_label')
                                ->label('')
                                ->hiddenLabel()
                                ->getStateUsing(fn () => ($this->reportData['net_profit'] ?? 0) >= 0 ? 'Laba Bersih Periode Berjalan (+)' : 'Rugi Bersih Periode Berjalan (-)')
                                ->columnSpan(9)
                                ->color('gray'),
                            TextEntry::make('net_profit')
                                ->label('')
                                ->hiddenLabel()
                                ->columnSpan(3)
                                ->formatStateUsing(fn ($state) => $state < 0 ? '(Rp ' . number_format(abs($state), 2, ',', '.') . ')' : 'Rp ' . number_format($state, 2, ',', '.'))
                                ->alignEnd()
                                ->color(fn ($state) => $state >= 0 ? 'success' : 'danger')
                                ->weight('semibold'),
                        ]),

                        // Row 3: Prive (jika ada)
                        Grid::make(12)
                            ->visible(fn () => ($this->reportData['prive'] ?? 0) > 0)
                            ->schema([
                                TextEntry::make('prive_account_name')
                                    ->label('')
                                    ->hiddenLabel()
                                    ->getStateUsing(fn () => ($this->reportData['prive_account_name'] ?? 'Prive') . ' (-)')
                                    ->columnSpan(9)
                                    ->color('gray'),
                                TextEntry::make('prive')
                                    ->label('')
                                    ->hiddenLabel()
                                    ->columnSpan(3)
                                    ->formatStateUsing(fn ($state) => '(Rp ' . number_format(abs($state), 2, ',', '.') . ')')
                                    ->alignEnd()
                                    ->color('danger')
                                    ->weight('semibold'),
                            ]),

                        // Row 4: Laba Ditahan (jika ada)
                        Grid::make(12)
                            ->visible(fn () => ($this->reportData['retained_earnings'] ?? 0) != 0)
                            ->schema([
                                TextEntry::make('retained_earnings_label')
                                    ->label('')
                                    ->hiddenLabel()
                                    ->getStateUsing(fn () => 'Laba Ditahan')
                                    ->columnSpan(9)
                                    ->color('gray'),
                                TextEntry::make('retained_earnings')
                                    ->label('')
                                    ->hiddenLabel()
                                    ->columnSpan(3)
                                    ->formatStateUsing(fn ($state) => $state < 0 ? '(Rp ' . number_format(abs($state), 2, ',', '.') . ')' : 'Rp ' . number_format($state, 2, ',', '.'))
                                    ->alignEnd()
                                    ->weight('semibold'),
                            ]),

                        // Row 5: Modal Akhir Card
                        Card::make([
                            Grid::make(12)->schema([
                                TextEntry::make('ending_capital_label')
                                    ->label('')
                                    ->hiddenLabel()
                                    ->getStateUsing(fn () => 'Modal Akhir Periode')
                                    ->columnSpan(9)
                                    ->weight('bold')
                                    ->size('lg'),
                                TextEntry::make('ending_capital')
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
                    ]),
            ]);
    }

    public function exportPdf()
    {
        if (empty($this->reportData)) {
            $this->generateReport();
        }

        $pdf = Pdf::loadView('reports.equity-statement-pdf', ['data' => $this->reportData]);
        return response()->streamDownload(
            fn () => print($pdf->output()),
            "laporan-perubahan-ekuitas-{$this->reportData['period_name']}.pdf"
        );
    }
}
