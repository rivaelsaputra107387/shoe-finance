<?php

namespace App\Filament\Widgets;

use App\Models\BankMutation;
use App\Models\JournalEntry;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StaffTodoWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('staff') ?? false;
    }

    protected function getStats(): array
    {
        $userId = auth()->id();

        // 1. Mutasi Bank yang perlu diproses
        $pendingMutations = BankMutation::whereIn('status', ['pending', 'matched'])->count();

        // 2. Draft Jurnal (Belum Disubmit / Unapproved)
        $draftJournals = JournalEntry::where('status', 'draft')
            ->where('created_by', $userId)
            ->count();

        // 3. Jurnal Menunggu Approval (Milik dia)
        $unapprovedJournals = JournalEntry::where('status', 'unapproved')
            ->where('created_by', $userId)
            ->count();

        return [
            Stat::make('Mutasi Bank Baru', $pendingMutations)
                ->description('Mutasi yang belum dibuatkan draft jurnal')
                ->descriptionIcon('heroicon-m-arrows-right-left')
                ->color($pendingMutations > 0 ? 'warning' : 'success'),
            
            Stat::make('Draft Jurnal Saya', $draftJournals)
                ->description('Jurnal yang perlu dilengkapi akunnya')
                ->descriptionIcon('heroicon-m-pencil-square')
                ->color($draftJournals > 0 ? 'warning' : 'success'),

            Stat::make('Menunggu Approval', $unapprovedJournals)
                ->description('Jurnal yang sedang direview Finance')
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),
        ];
    }
}
