<?php

namespace App\Filament\Resources\DraftJournalResource\Pages;

use App\Filament\Resources\DraftJournalResource;
use Filament\Resources\Pages\ListRecords;

class ListDraftJournals extends ListRecords
{
    protected static string $resource = DraftJournalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
