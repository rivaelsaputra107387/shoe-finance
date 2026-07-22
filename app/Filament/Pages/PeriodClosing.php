<?php

namespace App\Filament\Pages;

use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Services\ClosingEntryService;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class PeriodClosing extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';
    protected static ?string $navigationLabel = 'Penutupan Periode';
    protected static ?string $navigationGroup = 'Pengaturan';
    protected static ?string $title = 'Penutupan Periode';
    protected static ?int $navigationSort = 10;
    protected static string $view = 'filament.pages.period-closing';

    public ?array $activePeriodData = null;
    public array $closingResult = [];
    public bool $showConfirmation = false;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasRole('owner') ?? false;
    }

    public function mount(): void
    {
        if (!auth()->user()?->hasRole('owner')) {
            abort(403, 'Unauthorized access.');
        }

        $this->loadActivePeriod();
    }

    public function loadActivePeriod(): void
    {
        $period = FiscalPeriod::active();
        if ($period) {
            $journalCount = JournalEntry::forPeriod($period->id)->regular()->count();
            $this->activePeriodData = [
                'id' => $period->id,
                'name' => $period->name,
                'start_date' => $period->start_date->format('d M Y'),
                'end_date' => $period->end_date->format('d M Y'),
                'journal_count' => $journalCount,
            ];
        } else {
            $this->activePeriodData = null;
        }
    }

    public function confirmClose(): void
    {
        $this->showConfirmation = true;
    }

    public function cancelClose(): void
    {
        $this->showConfirmation = false;
    }

    public function closePeriod(): void
    {
        if (!$this->activePeriodData) return;

        try {
            $service = new ClosingEntryService();
            $result = $service->closePeriod($this->activePeriodData['id']);

            $this->closingResult = [
                'success' => true,
                'net_profit' => $result['net_profit'],
                'entries_count' => count($result['closing_entries']),
                'closed_period' => $result['closed_period']->name,
                'new_period' => $result['new_period']->name,
            ];

            $this->showConfirmation = false;
            $this->loadActivePeriod();

            Notification::make()
                ->title('Periode berhasil ditutup!')
                ->body("Jurnal penutup berhasil dibuat. Periode baru '{$result['new_period']->name}' telah dibuka.")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Gagal menutup periode')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
