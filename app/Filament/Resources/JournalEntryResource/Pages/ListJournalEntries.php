<?php

namespace App\Filament\Resources\JournalEntryResource\Pages;

use App\Filament\Resources\JournalEntryResource;
use Filament\Resources\Pages\ListRecords;

class ListJournalEntries extends ListRecords
{
    protected static string $resource = JournalEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('create_journal')
                ->label('Input Jurnal Baru')
                ->icon('heroicon-o-plus')
                ->url(fn () => route('filament.admin.pages.create-journal-entry'))
                ->color('primary'),
        ];
    }
}
